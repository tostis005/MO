<?php

foreach ( array(
	'mdo_ps_safe_filter_ids_20260821',
	'elmercado_public_store_fix_valid_ids_010261',
) as $function ) {
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

$vendor_ids = get_users(
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
	'allowed_pairs'    => 0,
	'blocked_pairs'    => 0,
	'ranked_pairs'     => 0,
	'ownership_errors' => array(),
	'ranking_errors'   => array(),
	'blocking_errors'  => array(),
	'sample_vendor'    => 0,
	'sample_slug'      => '',
	'blocked_sample'   => array(),
	'countries'        => $countries,
);

foreach ( array_values( array_unique( array_filter( array_map( 'absint', (array) $vendor_ids ) ) ) ) as $vendor_id ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) elmercado_public_store_fix_valid_ids_010261( $vendor_id ) ) ) ) );
	if ( ! $ids ) {
		continue;
	}
	$stats['vendors_checked']++;
	$stats['products_checked'] += count( $ids );

	if ( ! $stats['sample_vendor'] ) {
		$stats['sample_vendor'] = $vendor_id;
		if ( function_exists( 'wcfmmp_get_store_url' ) ) {
			$path = (string) wp_parse_url( wcfmmp_get_store_url( $vendor_id ), PHP_URL_PATH );
			$stats['sample_slug'] = sanitize_title( basename( untrailingslashit( $path ) ) );
		}
	}

	foreach ( $countries as $country ) {
		$country = (string) $country;
		$can_ship = MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, $country, '' );
		$actual   = mdo_ps_safe_filter_ids_20260821( $ids, $vendor_id, $country, '', true );

		if ( ! $can_ship ) {
			$stats['blocked_pairs']++;
			if ( $actual ) {
				$stats['blocking_errors'][] = array( 'vendor_id' => $vendor_id, 'country' => $country, 'ids' => array_slice( $actual, 0, 10 ) );
			}
			if ( ! $stats['blocked_sample'] ) {
				$stats['blocked_sample'] = array( 'vendor_id' => $vendor_id, 'country' => $country );
			}
			continue;
		}

		$stats['allowed_pairs']++;
		$expected = MDO_Catalog_Ranking::rank_products(
			$ids,
			array(
				'rotation_seed'      => gmdate( 'Y-m-d' ),
				'diversify_vendors' => true,
			)
		);
		$expected = array_values( array_unique( array_filter( array_map( 'absint', (array) $expected ) ) ) );
		if ( $actual !== $expected ) {
			$stats['ranking_errors'][] = array(
				'vendor_id' => $vendor_id,
				'country'   => $country,
				'expected'  => array_slice( $expected, 0, 12 ),
				'actual'    => array_slice( $actual, 0, 12 ),
			);
		} else {
			$stats['ranked_pairs']++;
		}

		foreach ( $actual as $product_id ) {
			if ( (int) get_post_field( 'post_author', $product_id ) !== $vendor_id ) {
				$stats['ownership_errors'][] = array( 'vendor_id' => $vendor_id, 'product_id' => $product_id );
			}
		}
	}
}

$errors = count( $stats['ownership_errors'] ) + count( $stats['ranking_errors'] ) + count( $stats['blocking_errors'] );
$ok = $stats['vendors_checked'] > 0
	&& $stats['products_checked'] > 0
	&& $stats['allowed_pairs'] > 0
	&& $stats['blocked_pairs'] > 0
	&& 0 === $errors;

$stats['ok'] = $ok;
echo 'producer_store_safe_audit_' . ( $ok ? 'ok ' : 'failed ' ) . wp_json_encode( $stats, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
exit( $ok ? 0 : 2 );
