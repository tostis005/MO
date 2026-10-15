<?php
/** Read-only, repeatable per-batch comparison of live Latitudes against EMDO/WooCommerce. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) !== '1' ) {
    throw new RuntimeException( 'Vendor must be disabled before verification.' );
}
$offset = (int) ( getenv( 'LATITUDES_OFFSET' ) ?: 0 );
$limit = 12;
if ( $offset < 0 || $offset > 137 ) { throw new RuntimeException( 'Invalid source offset.' ); }
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,source_price,source_stock_status,source_payload,source_hash,status FROM {$table} WHERE supplier_id=%d ORDER BY id LIMIT %d OFFSET %d",
    (int) $supplier['id'], $limit, $offset
), ARRAY_A );
$results = array();
$normalize = static function( string $value ): string {
    return trim( preg_replace( '/\s+/u', ' ', mb_strtolower( remove_accents( wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5 ) ) ) ) ) );
};
foreach ( $rows as $row ) {
    $id = (int) $row['id'];
    $payload = json_decode( (string) $row['source_payload'], true ) ?: array();
    $wc = wc_get_product( (int) $row['wc_product_id'] );
    $issues = array();
    $live = null;
    try { $live = MDO_Connector_Latitudes::scrape_product( (string) $row['source_url'] ); }
    catch ( Throwable $e ) { $issues[] = 'source_fetch:' . mb_substr( $e->getMessage(), 0, 90 ); }
    if ( ! $wc ) { $issues[] = 'woo_missing'; }
    else {
        if ( $wc->get_status() !== 'draft' || $wc->get_catalog_visibility() !== 'hidden' ) { $issues[] = 'public_status'; }
        if ( (int) get_post_field( 'post_author', $wc->get_id() ) !== (int) $supplier['vendor_user_id'] ) { $issues[] = 'wrong_vendor'; }
        if ( ! $wc->get_image_id() || 'attachment' !== get_post_type( $wc->get_image_id() ) ) { $issues[] = 'woo_missing_image'; }
        if ( abs( (float) $wc->get_price() - (float) $row['source_price'] ) > 0.02 ) { $issues[] = 'woo_price'; }
        if ( $wc->get_stock_status() !== $row['source_stock_status'] ) { $issues[] = 'woo_stock'; }
        if ( ! get_post_meta( $wc->get_id(), '_emdo_latitudes_category_v1', true ) ) { $issues[] = 'taxonomy_not_reviewed'; }
    }
    if ( $live ) {
        if ( $normalize( (string) $live['title'] ) !== $normalize( (string) $payload['title'] ) ) { $issues[] = 'live_title_changed'; }
        if ( abs( (float) $live['price'] - (float) $row['source_price'] ) > 0.02 ) { $issues[] = 'live_price_changed'; }
        if ( $live['stock_status'] !== $row['source_stock_status'] ) { $issues[] = 'live_stock_changed'; }
        $imgs_live = array_values( array_unique( (array) ( $live['images'] ?? array() ) ) );
        $imgs_stored = array_values( array_unique( (array) ( $payload['images'] ?? array() ) ) );
        if ( ! $imgs_live || ! $imgs_stored || ! array_intersect( $imgs_live, $imgs_stored ) ) { $issues[] = 'live_images_changed'; }
        $live_text = $normalize( (string) ( $live['description'] ?? '' ) );
        $stored_text = $normalize( (string) ( $payload['description'] ?? '' ) );
        if ( $live_text !== $stored_text ) { $issues[] = 'live_description_changed'; }
    }
    $results[] = array(
        'id' => $id,
        'wc' => (int) $row['wc_product_id'],
        'title' => $row['title'],
        'issues' => $issues,
        'price_wc' => $wc ? (float) $wc->get_price() : null,
        'price_src' => (float) $row['source_price'],
        'price_live' => $live ? (float) $live['price'] : null,
        'stock_wc' => $wc ? $wc->get_stock_status() : null,
        'stock_src' => $row['source_stock_status'],
        'stock_live' => $live['stock_status'] ?? null,
        'source_images' => count( (array) ( $payload['images'] ?? array() ) ),
        'live_images' => isset( $imgs_live ) ? count( $imgs_live ) : 0,
        'url' => $row['source_url'],
    );
}
$issues = array_values( array_filter( $results, fn( $r ) => $r['issues'] ) );
echo 'LATITUDES_LIVE_CHECK=' . wp_json_encode( array(
    'offset' => $offset,
    'reviewed' => count( $results ),
    'passing' => count( $results ) - count( $issues ),
    'issues' => $issues,
    'all_rows' => $results,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
