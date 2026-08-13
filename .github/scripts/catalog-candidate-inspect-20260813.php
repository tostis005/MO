<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_product' ) ) {
	throw new RuntimeException( 'WooCommerce no está disponible.' );
}

global $wpdb;
$ids = array( 5064, 5065, 5066, 5067 );
$rows = array();

foreach ( $ids as $id ) {
	$product = wc_get_product( $id );
	if ( ! $product instanceof WC_Product ) {
		$rows[] = array( 'id' => $id, 'missing' => true );
		continue;
	}

	$all_meta = get_post_meta( $id );
	$meta_keys = array_keys( $all_meta );
	sort( $meta_keys, SORT_STRING );
	$interesting_meta = array();
	foreach ( $all_meta as $key => $values ) {
		if ( ! preg_match( '/(?:price|stock|catalog|visibility|bundle|addon|extra|yith|wcfm|upsell|crosssell|virtual|download|sold|thumbnail|gallery)/i', (string) $key ) ) {
			continue;
		}
		$clean = array();
		foreach ( (array) $values as $value ) {
			$value = maybe_unserialize( $value );
			if ( is_scalar( $value ) || null === $value ) {
				$clean[] = (string) $value;
			} else {
				$clean[] = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}
		}
		$interesting_meta[ (string) $key ] = $clean;
	}

	$references = array();
	$like = '%' . $wpdb->esc_like( (string) $id ) . '%';
	$found = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id <> %d AND meta_value LIKE %s ORDER BY post_id ASC LIMIT 60",
			$id,
			$like
		),
		ARRAY_A
	);
	foreach ( (array) $found as $ref ) {
		$value = (string) $ref['meta_value'];
		if ( function_exists( 'mb_substr' ) ) {
			$value = mb_substr( $value, 0, 1200, 'UTF-8' );
		} else {
			$value = substr( $value, 0, 1200 );
		}
		$references[] = array(
			'post_id'    => (int) $ref['post_id'],
			'post_type'  => (string) get_post_type( (int) $ref['post_id'] ),
			'post_title' => (string) get_the_title( (int) $ref['post_id'] ),
			'meta_key'   => (string) $ref['meta_key'],
			'meta_value' => $value,
		);
	}

	$date_created = $product->get_date_created();
	$date_modified = $product->get_date_modified();
	$rows[] = array(
		'id'                 => $id,
		'name'               => (string) $product->get_name(),
		'status'             => (string) $product->get_status(),
		'type'               => (string) $product->get_type(),
		'catalog_visibility' => (string) $product->get_catalog_visibility(),
		'price'              => (string) $product->get_price(),
		'regular_price'      => (string) $product->get_regular_price(),
		'sale_price'         => (string) $product->get_sale_price(),
		'stock_status'       => (string) $product->get_stock_status(),
		'manage_stock'       => (bool) $product->get_manage_stock(),
		'stock_quantity'     => $product->get_stock_quantity(),
		'virtual'            => (bool) $product->get_virtual(),
		'downloadable'       => (bool) $product->get_downloadable(),
		'sold_individually'  => (bool) $product->get_sold_individually(),
		'featured'           => (bool) $product->get_featured(),
		'date_created'       => $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : null,
		'date_modified'      => $date_modified ? $date_modified->date( 'Y-m-d H:i:s' ) : null,
		'upsell_ids'         => array_map( 'intval', $product->get_upsell_ids() ),
		'cross_sell_ids'     => array_map( 'intval', $product->get_cross_sell_ids() ),
		'categories'         => wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) ),
		'visibility_terms'   => taxonomy_exists( 'product_visibility' ) ? wp_get_post_terms( $id, 'product_visibility', array( 'fields' => 'names' ) ) : array(),
		'meta_keys'          => $meta_keys,
		'interesting_meta'   => $interesting_meta,
		'references'         => $references,
	);
}

echo wp_json_encode( array( 'site_url' => get_option( 'siteurl' ), 'products' => $rows ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
