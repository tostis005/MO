<?php

$required_functions = array(
	'mdo_producer_store_catalog_ids_for_destination_20260821',
	'mdo_producer_store_catalog_owned_ids_20260821',
	'mdo_producer_store_catalog_render_destination_20260821',
	'elmercado_public_store_fix_valid_ids_010261',
);
foreach ( $required_functions as $function ) {
	if ( ! function_exists( $function ) ) {
		fwrite( STDERR, "ERROR missing function: {$function}\n" );
		exit( 20 );
	}
}
foreach ( array( 'MDO_Shipping_Destinations', 'MDO_Catalog_Ranking', 'MDO_Catalog_Destination_Frontend' ) as $class ) {
	if ( ! class_exists( $class ) ) {
		fwrite( STDERR, "ERROR missing class: {$class}\n" );
		exit( 21 );
	}
}

$vendors = get_users(
	array(
		'role__in' => array( 'wcfm_vendor', 'vendor' ),
		'fields'   => 'ids',
	)
);
$countries = array_keys( MDO_Shipping_Destinations::supported_countries( true ) );
if ( ! $countries ) {
	$countries = array( 'ES' );
}

$stats = array(
	'vendors_checked'  => 0,
	'products_checked' => 0,
	'countries'        => $countries,
	'allowed_pairs'    => 0,
	'blocked_pairs'    => 0,
	'ranked_pairs'     => 0,
	'ownership_errors' => array(),
	'ranking_errors'   => array(),
	'blocking_errors'  => array(),
	'live_es'          => false,
	'live_en'          => false,
	'sample_es_url'    => '',
	'sample_en_url'    => '',
	'blocked_sample'   => array(),
);

$sample_vendor = 0;
$sample_slug = '';
foreach ( array_map( 'absint', (array) $vendors ) as $vendor_id ) {
	if ( ! $vendor_id || ! mdo_producer_store_catalog_vendor_allowed_20260821( $vendor_id ) ) {
		continue;
	}
	$owned = mdo_producer_store_catalog_owned_ids_20260821( $vendor_id );
	if ( ! $owned ) {
		continue;
	}

	$stats['vendors_checked']++;
	$stats['products_checked'] += count( $owned );
	if ( ! $sample_vendor ) {
		$sample_vendor = $vendor_id;
		if ( function_exists( 'wcfmmp_get_store_url' ) ) {
			$sample_path = (string) wp_parse_url( wcfmmp_get_store_url( $vendor_id ), PHP_URL_PATH );
			$sample_slug = sanitize_title( basename( untrailingslashit( $sample_path ) ) );
		}
	}

	foreach ( $countries as $country ) {
		$can_ship = MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, (string) $country, '' );
		$resolved = mdo_producer_store_catalog_ids_for_destination_20260821( $vendor_id, (string) $country, '', true );

		if ( ! $can_ship ) {
			$stats['blocked_pairs']++;
			if ( $resolved ) {
				$stats['blocking_errors'][] = array( 'vendor_id' => $vendor_id, 'country' => $country, 'ids' => $resolved );
			}
			if ( ! $stats['blocked_sample'] ) {
				$stats['blocked_sample'] = array( 'vendor_id' => $vendor_id, 'country' => $country );
			}
			continue;
		}

		$stats['allowed_pairs']++;
		$expected = MDO_Catalog_Ranking::rank_products(
			$owned,
			array(
				'rotation_seed'      => gmdate( 'Y-m-d' ),
				'diversify_vendors' => true,
			)
		);
		$expected = array_values( array_unique( array_filter( array_map( 'absint', (array) $expected ) ) ) );
		if ( $resolved !== $expected ) {
			$stats['ranking_errors'][] = array(
				'vendor_id' => $vendor_id,
				'country'   => $country,
				'expected'  => array_slice( $expected, 0, 12 ),
				'actual'    => array_slice( $resolved, 0, 12 ),
			);
		} else {
			$stats['ranked_pairs']++;
		}

		foreach ( $resolved as $product_id ) {
			if ( (int) get_post_field( 'post_author', $product_id ) !== $vendor_id ) {
				$stats['ownership_errors'][] = array( 'vendor_id' => $vendor_id, 'product_id' => $product_id );
			}
		}
	}
}

if ( $sample_vendor && function_exists( 'wcfmmp_get_store_url' ) ) {
	$es_url = add_query_arg( 'mdo_store_rules_verify', time(), wcfmmp_get_store_url( $sample_vendor ) );
	$stats['sample_es_url'] = $es_url;
	$en_url = '';
	if ( $sample_slug && function_exists( 'mdoev_english_store_url_010260' ) ) {
		$en_url = add_query_arg( 'mdo_store_rules_verify', time(), mdoev_english_store_url_010260( $sample_slug ) );
		$stats['sample_en_url'] = $en_url;
	}

	$headers = array(
		'Cookie'        => 'mdo_shipping_country=ES; mdo_shipping_postcode=',
		'Cache-Control' => 'no-cache',
	);
	$es_response = wp_remote_get( $es_url, array( 'timeout' => 30, 'redirection' => 5, 'headers' => $headers ) );
	if ( ! is_wp_error( $es_response ) && (int) wp_remote_retrieve_response_code( $es_response ) < 400 ) {
		$body = (string) wp_remote_retrieve_body( $es_response );
		$stats['live_es'] = false !== strpos( $body, 'data-mdo-destination-control' ) && false !== strpos( $body, 'Envío a' );
	}

	if ( $en_url ) {
		$en_response = wp_remote_get( $en_url, array( 'timeout' => 30, 'redirection' => 5, 'headers' => $headers ) );
		if ( ! is_wp_error( $en_response ) && (int) wp_remote_retrieve_response_code( $en_response ) < 400 ) {
			$body = (string) wp_remote_retrieve_body( $en_response );
			$stats['live_en'] = false !== strpos( $body, 'data-mdo-destination-control' ) && false !== strpos( $body, 'Shipping to' );
		}
	}
}

$errors = count( $stats['ownership_errors'] ) + count( $stats['ranking_errors'] ) + count( $stats['blocking_errors'] );
$ok = $stats['vendors_checked'] > 0
	&& $stats['allowed_pairs'] > 0
	&& $stats['blocked_pairs'] > 0
	&& 0 === $errors
	&& true === $stats['live_es']
	&& true === $stats['live_en'];

$stats['ok'] = $ok;
echo 'producer_store_catalog_rules_' . ( $ok ? 'ok ' : 'failed ' ) . wp_json_encode( $stats, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
exit( $ok ? 0 : 2 );
