<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$ids = array( 1375, 1586, 4188, 5080 );
$out = array(
    'generated_at' => gmdate( 'c' ),
    'products' => array(),
);

foreach ( $ids as $id ) {
    $post = get_post( $id );
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
    $terms = wp_get_post_terms( $id, 'product_visibility', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $terms ) ) $terms = array();

    $out['products'][] = array(
        'id' => $id,
        'exists' => $post instanceof WP_Post,
        'title' => $post instanceof WP_Post ? $post->post_title : '',
        'post_status' => $post instanceof WP_Post ? $post->post_status : '',
        'author' => $post instanceof WP_Post ? (int) $post->post_author : 0,
        'permalink' => $post instanceof WP_Post ? get_permalink( $post ) : '',
        'catalog_visibility' => $product ? $product->get_catalog_visibility() : '',
        'stock_status' => $product ? $product->get_stock_status() : '',
        'is_visible' => $product ? (bool) $product->is_visible() : false,
        'is_purchasable' => $product ? (bool) $product->is_purchasable() : false,
        'product_visibility_terms' => array_values( (array) $terms ),
        'legacy_visibility_meta' => (string) get_post_meta( $id, '_visibility', true ),
        'disabled_vendor' => function_exists( 'elmercado_wcfm_product_is_from_disabled_vendor_010210' )
            ? (bool) elmercado_wcfm_product_is_from_disabled_vendor_010210( $id )
            : null,
        'en_published' => (string) get_post_meta( $id, '_en_US_published', true ),
        'en_ready' => (string) get_post_meta( $id, '_en_US_ready', true ),
    );
}

echo "EMDO HIDALGO SITEMAP GAP AUDIT 2026-08-21\n";
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n";
