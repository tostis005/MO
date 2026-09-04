<?php
/**
 * Production adjustment for the Montjam green-label ham only.
 *
 * The producer table supplies a single commercial weight band for this SKU:
 * 8–8.5 kg, with a final EMDO price (with margin) of 219.78 EUR.
 * Existing lower-weight variations are retained internally but disabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( "WordPress not loaded\n" );
}

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "WooCommerce unavailable\n" );
    exit( 2 );
}

const MDO_MONTJAM_GREEN_HAM_ID = 14294;
const MDO_MONTJAM_GREEN_KEEP_VARIATION_ID = 14298;
const MDO_MONTJAM_GREEN_VENDOR_ID = 4723;
const MDO_MONTJAM_GREEN_SIZE_SLUG = '8-85-kg';
const MDO_MONTJAM_GREEN_PRICE = '219.78';

$product = wc_get_product( MDO_MONTJAM_GREEN_HAM_ID );
if ( ! $product || ! $product->is_type( 'variable' ) ) {
    fwrite( STDERR, "Expected Montjam green-label variable ham #" . MDO_MONTJAM_GREEN_HAM_ID . "\n" );
    exit( 3 );
}

if ( MDO_MONTJAM_GREEN_VENDOR_ID !== (int) get_post_field( 'post_author', MDO_MONTJAM_GREEN_HAM_ID ) ) {
    fwrite( STDERR, "Unexpected vendor for Montjam green-label ham\n" );
    exit( 4 );
}

$title = $product->get_name();
if ( false === stripos( $title, 'cebo de campo' ) || false === stripos( $title, 'brida verde' ) || false === stripos( $title, 'Montjam' ) ) {
    fwrite( STDERR, "Product identity guard failed: {$title}\n" );
    exit( 5 );
}

$taxonomy = 'pa_tamano';
if ( ! taxonomy_exists( $taxonomy ) ) {
    fwrite( STDERR, "Missing product size taxonomy {$taxonomy}\n" );
    exit( 6 );
}

$size_term = get_term_by( 'slug', MDO_MONTJAM_GREEN_SIZE_SLUG, $taxonomy );
if ( ! $size_term || is_wp_error( $size_term ) ) {
    fwrite( STDERR, "Expected existing 8-8.5 kg size term was not found\n" );
    exit( 7 );
}

$keep = wc_get_product( MDO_MONTJAM_GREEN_KEEP_VARIATION_ID );
if ( ! $keep || ! $keep->is_type( 'variation' ) || MDO_MONTJAM_GREEN_HAM_ID !== (int) $keep->get_parent_id() ) {
    fwrite( STDERR, "Expected reusable variation #" . MDO_MONTJAM_GREEN_KEEP_VARIATION_ID . " was not found\n" );
    exit( 8 );
}

// Keep the previously-associated variation ID so dynamic YITH format rules remain intact.
$existing_sku_owner = wc_get_product_id_by_sku( 'MONTJAM-JV-800-850' );
if ( $existing_sku_owner && MDO_MONTJAM_GREEN_KEEP_VARIATION_ID !== (int) $existing_sku_owner ) {
    fwrite( STDERR, "SKU MONTJAM-JV-800-850 already belongs to product {$existing_sku_owner}\n" );
    exit( 9 );
}

foreach ( [14295, 14296, 14297] as $variation_id ) {
    $variation = wc_get_product( $variation_id );
    if ( ! $variation || ! $variation->is_type( 'variation' ) || MDO_MONTJAM_GREEN_HAM_ID !== (int) $variation->get_parent_id() ) {
        fwrite( STDERR, "Unexpected variation structure at {$variation_id}\n" );
        exit( 10 );
    }
    $variation->set_status( 'private' );
    $variation->set_stock_status( 'outofstock' );
    $variation->save();
}

$keep->set_status( 'publish' );
$keep->set_attributes( [ $taxonomy => MDO_MONTJAM_GREEN_SIZE_SLUG ] );
$keep->set_sku( 'MONTJAM-JV-800-850' );
$keep->set_regular_price( MDO_MONTJAM_GREEN_PRICE );
$keep->set_sale_price( '' );
$keep->set_stock_status( 'instock' );
$keep->save();

// Make 8–8.5 kg the only size exposed by the parent product.
wp_set_object_terms( MDO_MONTJAM_GREEN_HAM_ID, [ (int) $size_term->term_id ], $taxonomy, false );

$attributes = $product->get_attributes();
if ( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ) {
    $attributes[ $taxonomy ]->set_options( [ (int) $size_term->term_id ] );
    $attributes[ $taxonomy ]->set_variation( true );
    $attributes[ $taxonomy ]->set_visible( true );
} else {
    $attribute = new WC_Product_Attribute();
    $attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
    $attribute->set_name( $taxonomy );
    $attribute->set_options( [ (int) $size_term->term_id ] );
    $attribute->set_position( 0 );
    $attribute->set_visible( true );
    $attribute->set_variation( true );
    $attributes[ $taxonomy ] = $attribute;
}
$product->set_attributes( $attributes );

$defaults = $product->get_default_attributes();
if ( isset( $defaults[ $taxonomy ] ) ) {
    $defaults[ $taxonomy ] = MDO_MONTJAM_GREEN_SIZE_SLUG;
    $product->set_default_attributes( $defaults );
}
$product->save();

WC_Product_Variable::sync( MDO_MONTJAM_GREEN_HAM_ID );
wc_delete_product_transients( MDO_MONTJAM_GREEN_HAM_ID );
clean_post_cache( MDO_MONTJAM_GREEN_HAM_ID );
wp_cache_flush();

// Strong verification: one published child, correct weight and exact final price.
$published_children = get_posts( [
    'post_type'      => 'product_variation',
    'post_parent'    => MDO_MONTJAM_GREEN_HAM_ID,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'ID',
    'order'          => 'ASC',
] );

$verify_product = wc_get_product( MDO_MONTJAM_GREEN_HAM_ID );
$verify_keep    = wc_get_product( MDO_MONTJAM_GREEN_KEEP_VARIATION_ID );
$verify_attrs   = $verify_keep ? $verify_keep->get_attributes() : [];
$parent_terms   = wp_get_object_terms( MDO_MONTJAM_GREEN_HAM_ID, $taxonomy, [ 'fields' => 'slugs' ] );

$result = [
    'product_id'          => MDO_MONTJAM_GREEN_HAM_ID,
    'title'               => $verify_product ? $verify_product->get_name() : '',
    'published_children'  => array_map( 'intval', $published_children ),
    'kept_variation_id'   => MDO_MONTJAM_GREEN_KEEP_VARIATION_ID,
    'kept_status'         => $verify_keep ? $verify_keep->get_status() : '',
    'kept_size'           => $verify_attrs[ $taxonomy ] ?? '',
    'kept_price'          => $verify_keep ? $verify_keep->get_price() : '',
    'kept_regular_price'  => $verify_keep ? $verify_keep->get_regular_price() : '',
    'kept_sku'            => $verify_keep ? $verify_keep->get_sku() : '',
    'parent_price'        => $verify_product ? $verify_product->get_price() : '',
    'parent_size_terms'   => is_wp_error( $parent_terms ) ? [] : array_values( $parent_terms ),
    'disabled_variations' => [
        14295 => get_post_status( 14295 ),
        14296 => get_post_status( 14296 ),
        14297 => get_post_status( 14297 ),
    ],
];

echo 'MONTJAM_GREEN_HAM_RESULT: ' . wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

$ok = [ MDO_MONTJAM_GREEN_KEEP_VARIATION_ID ] === array_map( 'intval', $published_children )
    && $verify_keep
    && 'publish' === $verify_keep->get_status()
    && MDO_MONTJAM_GREEN_SIZE_SLUG === ( $verify_attrs[ $taxonomy ] ?? '' )
    && abs( (float) $verify_keep->get_price() - 219.78 ) < 0.001
    && 'MONTJAM-JV-800-850' === $verify_keep->get_sku()
    && ! is_wp_error( $parent_terms )
    && [ MDO_MONTJAM_GREEN_SIZE_SLUG ] === array_values( $parent_terms )
    && 'private' === get_post_status( 14295 )
    && 'private' === get_post_status( 14296 )
    && 'private' === get_post_status( 14297 );

if ( ! $ok ) {
    fwrite( STDERR, "Montjam green-label ham verification failed\n" );
    exit( 11 );
}

echo "MONTJAM_GREEN_HAM_SUCCESS\n";
