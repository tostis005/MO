<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $wpdb;
$supplier = null;
foreach ( MDO_Supplier_Repository::all() as $candidate ) {
	if ( 'la-huerta-ana-mary' === (string) ( $candidate['connector'] ?? '' ) ) {
		$supplier = $candidate;
		break;
	}
}

$out = array(
	'plugin_version' => defined( 'MDO_SUPPLIER_SYNC_VERSION' ) ? MDO_SUPPLIER_SYNC_VERSION : 'unknown',
	'ok'             => false,
);

if ( ! $supplier ) {
	$out['error'] = 'supplier_not_found';
	echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	exit( 2 );
}

$supplier_id = (int) $supplier['id'];
$table       = MDO_Database::table( 'source_products' );
$row         = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM {$table} WHERE supplier_id = %d AND status = 'pending' ORDER BY CASE WHEN source_product_id = '32' THEN 0 ELSE 1 END, id ASC LIMIT 1",
		$supplier_id
	),
	ARRAY_A
);

if ( ! $row ) {
	$out['error'] = 'no_pending_product';
	$out['supplier_id'] = $supplier_id;
	echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	exit( 3 );
}

$out['source_before'] = array(
	'id'                => (int) $row['id'],
	'title'             => (string) $row['title'],
	'source_url'        => (string) $row['source_url'],
	'source_product_id' => (string) $row['source_product_id'],
	'source_price'      => $row['source_price'],
	'status'            => (string) $row['status'],
	'wc_product_id'     => $row['wc_product_id'],
);

try {
	$product_id = MDO_Woo_Importer::import_source_product( (int) $row['id'] );
	$after      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $row['id'] ), ARRAY_A );
	$product    = wc_get_product( $product_id );
	if ( ! $after || ! $product ) {
		throw new RuntimeException( 'La importación devolvió un ID pero no se pudo recuperar el producto creado.' );
	}
	$post = get_post( $product_id );
	$out['product'] = array(
		'id'               => $product_id,
		'name'             => $product->get_name(),
		'status'           => $product->get_status(),
		'catalog_visibility' => $product->get_catalog_visibility(),
		'price'            => $product->get_price(),
		'regular_price'    => $product->get_regular_price(),
		'stock_status'     => $product->get_stock_status(),
		'image_id'         => $product->get_image_id(),
		'gallery_count'    => count( $product->get_gallery_image_ids() ),
		'post_author'      => $post ? (int) $post->post_author : 0,
		'permalink'        => get_permalink( $product_id ),
	);
	$out['source_after'] = array(
		'id'            => (int) $after['id'],
		'status'        => (string) $after['status'],
		'wc_product_id' => (int) $after['wc_product_id'],
		'last_error'    => $after['last_error'],
	);
	$out['checks'] = array(
		'active_source'   => 'active' === (string) $after['status'],
		'wc_id_linked'    => $product_id === (int) $after['wc_product_id'],
		'published'       => 'publish' === $product->get_status(),
		'correct_vendor'  => (int) $supplier['vendor_user_id'] === ( $post ? (int) $post->post_author : 0 ),
		'has_price'       => '' !== (string) $product->get_price() && (float) $product->get_price() > 0,
	);
	$out['ok'] = ! in_array( false, $out['checks'], true );
} catch ( Throwable $error ) {
	$out['error'] = $error->getMessage();
	$wpdb->update( $table, array( 'last_error' => sanitize_textarea_field( $error->getMessage() ) ), array( 'id' => (int) $row['id'] ) );
}

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
exit( $out['ok'] ? 0 : 4 );
