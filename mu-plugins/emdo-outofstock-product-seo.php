<?php
/**
 * Plugin Name: EMDO Out-of-stock Product SEO
 * Description: Keeps published, non-hidden products from active vendors accessible on their singular URL while out of stock.
 * Version: 2026.08.21.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_oos_flag_on( $value ): bool {
    if ( is_bool( $value ) ) return $value;
    if ( is_int( $value ) || is_float( $value ) ) return (int) $value !== 0;
    if ( is_string( $value ) ) {
        $v = strtolower( trim( $value ) );
        return ! in_array( $v, array( '', '0', 'no', 'false', 'off', 'none' ), true );
    }
    return ! empty( $value );
}

function emdo_oos_vendor_disabled( int $user_id ): bool {
    if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) ) {
        return (bool) elmercado_wcfm_vendor_is_disabled_010210( $user_id );
    }
    $user = get_userdata( $user_id );
    if ( ! $user instanceof WP_User ) return false;
    if ( in_array( 'disable_vendor', array_map( 'sanitize_key', (array) $user->roles ), true ) ) return true;
    if ( emdo_oos_flag_on( get_user_meta( $user_id, '_disable_vendor', true ) ) ) return true;
    return emdo_oos_flag_on( get_user_meta( $user_id, '_wcfm_store_offline', true ) );
}

add_filter( 'woocommerce_product_is_visible', static function ( bool $visible, int $product_id ): bool {
    if ( $visible ) return true;

    // Do not change catalogue/archive visibility. This exception exists only so
    // the canonical singular product URL can remain a valid page while stock is temporary zero.
    if ( ! function_exists( 'is_product' ) || ! is_product() ) return $visible;
    if ( (int) get_queried_object_id() !== $product_id ) return $visible;

    $post = get_post( $product_id );
    if ( ! $post instanceof WP_Post || $post->post_type !== 'product' || $post->post_status !== 'publish' ) return $visible;
    if ( emdo_oos_vendor_disabled( (int) $post->post_author ) ) return false;

    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
    if ( ! $product ) return $visible;
    if ( (string) $product->get_catalog_visibility() === 'hidden' ) return $visible;
    if ( (string) $product->get_stock_status() !== 'outofstock' ) return $visible;

    return true;
}, PHP_INT_MAX, 2 );
