<?php
/**
 * Plugin Name: EMDO - Mentta-only API visibility for Montjam
 * Description: Mentta's custom product endpoint receives IsVisible=true for the explicitly protected Montjam duplicate, while the WooCommerce catalogue and public website remain hidden.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Match only the Mentta products REST endpoint. Never affect public WooCommerce
 * Store API, WooCommerce REST, frontend rendering, or ordinary WP product queries.
 */
function emdo_montjam_mentta_product_export_request() {
    if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) { return false; }

    $route = isset( $_GET['rest_route'] ) ? trim( (string) wp_unslash( $_GET['rest_route'] ), '/' ) : '';
    if ( 'mentta_marketplace/products' === $route ) { return true; }

    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    return (bool) preg_match( '~(?:^|/)wp-json/mentta_marketplace/products/?$~i', $path );
}

/**
 * The Mentta plugin serializes $producto->is_visible() as IsVisible in its
 * product response. This is a narrow integration-only exception, never a
 * change to the WooCommerce product_visibility taxonomy or catalog settings.
 */
add_filter( 'woocommerce_product_is_visible', static function ( $visible, $product_id ) {
    if ( (int) $product_id !== 17129 || ! emdo_montjam_mentta_product_export_request() ) {
        return $visible;
    }
    if ( ! function_exists( 'emdo_mentta_exclusive_ids' )
        || ! in_array( 17129, emdo_mentta_exclusive_ids(), true )
        || get_post_status( 17129 ) !== 'publish'
        || (int) get_post_meta( 17129, '_emdo_mentta_source_id', true ) !== 14264 ) {
        return $visible;
    }
    return true;
}, 10000, 2 );
