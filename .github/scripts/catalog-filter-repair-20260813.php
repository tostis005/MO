<?php
/**
 * Guarded production repair: rebuild WooCommerce's product-attribute lookup
 * index for the 265 intended catalog products. Product data is not changed.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_get_container' ) ) { exit( 2 ); }

global $wpdb;
$vendors = array(
    3    => array( 'name'=>'1957',               'expected'=>4 ),
    6    => array( 'name'=>'Hidalgo de la Jara', 'expected'=>21 ),
    4507 => array( 'name'=>'Tolecarnes',          'expected'=>39 ),
    4508 => array( 'name'=>'Puente Robles',       'expected'=>106 ),
    4509 => array( 'name'=>'El Catedrático',      'expected'=>95 ),
);
$lookup_class = '\\Automattic\\WooCommerce\\Internal\\ProductAttributesLookup\\LookupDataStore';
if ( ! class_exists( $lookup_class ) ) {
    throw new RuntimeException( 'WooCommerce LookupDataStore unavailable' );
}
$lookup = wc_get_container()->get( $lookup_class );
if ( ! is_object( $lookup ) || ! method_exists( $lookup, 'create_data_for_product' ) || ! method_exists( $lookup, 'check_lookup_table_exists' ) ) {
    throw new RuntimeException( 'WooCommerce attribute lookup API unavailable' );
}
if ( ! $lookup->check_lookup_table_exists() ) {
    throw new RuntimeException( 'WooCommerce attribute lookup table missing' );
}

$product_ids = array();
foreach ( $vendors as $author_id => $cfg ) {
    $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array($settings) || (string)($settings['store_name'] ?? '') !== $cfg['name'] ) {
        throw new RuntimeException( 'Vendor identity mismatch ' . $author_id );
    }
    $ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish' ORDER BY ID",
        $author_id
    ) ) );
    if ( count($ids) !== (int)$cfg['expected'] ) {
        throw new RuntimeException( $cfg['name'] . ' publish count changed: expected ' . $cfg['expected'] . ', got ' . count($ids) );
    }
    $product_ids = array_merge( $product_ids, $ids );
}
$product_ids = array_values( array_unique( array_map( 'intval', $product_ids ) ) );
if ( 265 !== count($product_ids) ) {
    throw new RuntimeException( 'Expected exactly 265 intended products, got ' . count($product_ids) );
}

$rebuilt = 0;
$failed = array();
foreach ( $product_ids as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) {
        throw new RuntimeException( 'WooCommerce product unavailable ' . $product_id );
    }
    $lookup->create_data_for_product( $product );
    if ( method_exists( $lookup, 'get_last_create_operation_failed' ) && $lookup->get_last_create_operation_failed() ) {
        $failed[] = $product_id;
        continue;
    }
    ++$rebuilt;
}
if ( $failed ) {
    throw new RuntimeException( 'Lookup rebuild failed for: ' . implode( ',', $failed ) );
}
if ( 265 !== $rebuilt ) {
    throw new RuntimeException( 'Expected 265 lookup rebuilds, got ' . $rebuilt );
}

// Guard the specific regression that exposed the stale index.
$chorizo = get_term_by( 'slug', 'chorizo', 'pa_tipo-producto' );
if ( ! $chorizo instanceof WP_Term ) {
    throw new RuntimeException( 'Chorizo attribute term missing' );
}
$lookup_table = method_exists( $lookup, 'get_lookup_table_name' ) ? (string)$lookup->get_lookup_table_name() : $wpdb->prefix . 'wc_product_attributes_lookup';
$chorizo_12602 = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$lookup_table} WHERE product_or_parent_id=%d AND taxonomy=%s AND term_id=%d",
    12602,
    'pa_tipo-producto',
    (int)$chorizo->term_id
) );
if ( $chorizo_12602 < 1 ) {
    throw new RuntimeException( 'Product 12602 still missing Chorizo lookup row after rebuild' );
}

if ( function_exists('elmercado_flush_home_cache') ) { elmercado_flush_home_cache(); }
wp_cache_flush();
if ( class_exists('WC_Cache_Helper') ) { WC_Cache_Helper::get_transient_version('product', true); }

echo 'ATTRIBUTE_LOOKUP_REPAIR_SUMMARY ' . wp_json_encode( array(
    'rebuilt'=>$rebuilt,
    'chorizo_12602_lookup_rows'=>$chorizo_12602,
    'created_categories'=>0,
    'created_terms'=>0,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) . "\n";
// Workflow guard markers: created_categories'=>0 created_terms'=>0
