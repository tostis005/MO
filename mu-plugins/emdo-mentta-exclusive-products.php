<?php
/**
 * Plugin Name: EMDO - Productos exclusivos para Mentta
 * Description: Oculta productos marcados para Mentta en toda la web publica, sin bloquear WooCommerce REST para su importacion.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_mentta_exclusive_ids() {
    $ids = get_option( 'emdo_mentta_exclusive_ids', array() );
    return array_values( array_filter( array_unique( array_map( 'absint', (array) $ids ) ) ) );
}

function emdo_mentta_exclusive_public_request() {
    if ( defined( 'WP_CLI' ) && WP_CLI ) { return false; }
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return false; }
    if ( is_admin() ) {
        if ( ! wp_doing_ajax() ) { return false; }
        $referer = (string) ( $_SERVER['HTTP_REFERER'] ?? '' );
        if ( false !== strpos( strtolower( (string) wp_parse_url( $referer, PHP_URL_PATH ) ), '/wp-admin/' ) ) { return false; }
    }
    return true;
}

/** Protect main shop, producer pages, archives, widgets and custom WP_Query grids. */
add_action( 'pre_get_posts', function ( $query ) {
    if ( ! emdo_mentta_exclusive_public_request() ) { return; }
    $post_type = $query->get( 'post_type' );
    if ( 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
        && ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( 'product_cat' )
        && ! $query->is_tax( 'product_tag' ) ) { return; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return; }
    $query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), $ids ) ) ) );
}, 100 );

/** Final guard for grids using hand-written product queries. */
add_filter( 'the_posts', function ( $posts ) {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_array( $posts ) ) { return $posts; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return $posts; }
    return array_values( array_filter( $posts, static function ( $post ) use ( $ids ) {
        return ! ( $post instanceof WP_Post && 'product' === $post->post_type && in_array( (int) $post->ID, $ids, true ) );
    } ) );
}, 100 );
add_filter( 'woocommerce_product_is_visible', function ( $visible, $product_id ) {
    return emdo_mentta_exclusive_public_request() && in_array( (int) $product_id, emdo_mentta_exclusive_ids(), true ) ? false : $visible;
}, 100, 2 );
add_filter( 'woocommerce_related_products', function ( $ids ) {
    return emdo_mentta_exclusive_public_request() ? array_values( array_diff( (array) $ids, emdo_mentta_exclusive_ids() ) ) : $ids;
}, 100 );

/** A direct browser URL must be 404, but authorized WooCommerce REST remains readable. */
add_action( 'template_redirect', function () {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_singular( 'product' ) ) { return; }
    $id = (int) get_queried_object_id();
    if ( ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) { return; }
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
}, 0 );

/** Keep these commercial-only duplicates outside WordPress and Yoast sitemaps. */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'product' !== $post_type ) { return $args; }
    $args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), emdo_mentta_exclusive_ids() ) ) );
    return $args;
}, 100, 2 );
add_filter( 'wpseo_sitemap_entry', function ( $url, $type, $post ) {
    if ( 'post' === $type && is_object( $post ) && in_array( (int) ( $post->ID ?? 0 ), emdo_mentta_exclusive_ids(), true ) ) { return false; }
    return $url;
}, 100, 3 );
