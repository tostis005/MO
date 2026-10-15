<?php
/** Read-only Latitudes content/taxonomy audit; private customer data never leaves WordPress. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$sup_table = MDO_Database::table( 'suppliers' );
$sup = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$sup_table} WHERE code=%s LIMIT 1", 'latitudes' ), ARRAY_A );
if ( ! $sup || (string) get_user_meta( (int) $sup['vendor_user_id'], '_disable_vendor', true ) !== '1' ) {
    throw new RuntimeException( 'Latitudes must remain disabled.' );
}
$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$tax = array();
foreach ( (array) $terms as $term ) {
    if ( ! $term instanceof WP_Term ) { continue; }
    $tax[] = array( 'id' => $term->term_id, 'slug' => $term->slug, 'name' => $term->name, 'parent' => $term->parent, 'count' => $term->count );
}
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,source_price,source_stock_status,source_payload,status FROM {$source_table} WHERE supplier_id=%d ORDER BY id", (int) $sup['id']
), ARRAY_A );
$out = array();
$hashes = array();
foreach ( $rows as $r ) {
    $p = json_decode( (string) $r['source_payload'], true );
    $p = is_array( $p ) ? $p : array();
    $wc = wc_get_product( (int) $r['wc_product_id'] );
    $original_desc = trim( wp_strip_all_tags( (string) ( $p['description'] ?? '' ) ) );
    $wc_desc = $wc ? trim( wp_strip_all_tags( $wc->get_description() ) ) : '';
    $hash = md5( mb_strtolower( $original_desc ) );
    $hashes[ $hash ] = ( $hashes[ $hash ] ?? 0 ) + 1;
    $category_ids = $wc ? $wc->get_category_ids() : array();
    $out[] = array(
        'source_id' => (int) $r['id'],
        'wc_id' => (int) $r['wc_product_id'],
        'title' => $p['title'] ?? $r['title'],
        'url' => $r['source_url'],
        'price_src' => (float) $r['source_price'],
        'price_wc' => $wc ? $wc->get_price() : null,
        'stock_src' => $r['source_stock_status'],
        'stock_wc' => $wc ? $wc->get_stock_status() : null,
        'state' => $wc ? $wc->get_status() : null,
        'visibility' => $wc ? $wc->get_catalog_visibility() : null,
        'image_src' => (array) ( $p['images'] ?? array() ),
        'image_wc' => $wc ? $wc->get_image_id() : null,
        'category_ids' => $category_ids,
        'desc_src' => mb_substr( $original_desc, 0, 650 ),
        'desc_source_length' => mb_strlen( $original_desc ),
        'desc_wc' => mb_substr( $wc_desc, 0, 650 ),
        'desc_wc_length' => mb_strlen( $wc_desc ),
        'desc_equal' => $original_desc === $wc_desc,
        'desc_hash' => $hash,
        'source_status' => $r['status'],
    );
}
echo 'LATITUDES_TAXONOMY=' . wp_json_encode( $tax, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
echo 'LATITUDES_EDITORIAL_CATALOG=' . wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
echo 'LATITUDES_EDITORIAL_SUMMARY=' . wp_json_encode( array(
    'total' => count( $out ),
    'duplicated_descriptions' => count( array_filter( $hashes, fn( $x ) => $x > 1 ) ),
    'missing_desc' => count( array_filter( $out, fn( $x ) => $x['desc_source_length'] < 40 ) ),
    'price_mismatch' => count( array_filter( $out, fn( $x ) => abs( (float) $x['price_src'] - (float) $x['price_wc'] ) > 0.02 ) ),
    'stock_mismatch' => count( array_filter( $out, fn( $x ) => $x['stock_src'] !== $x['stock_wc'] ) ),
    'missing_wc_image' => count( array_filter( $out, fn( $x ) => ! $x['image_wc'] ) ),
), JSON_UNESCAPED_UNICODE ) . PHP_EOL;
