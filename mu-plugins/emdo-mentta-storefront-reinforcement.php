<?php
/**
 * Plugin Name: EMDO - Proteccion adicional de productos exclusivos Mentta
 * Description: Bloquea la exposicion publica por Store API y carrito de los productos exclusivos, aunque su catalog_visibility sea visible para integraciones.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Whether a product (or its variation) is reserved for Mentta. */
function emdo_mj_exclusive_or_variation( $product_id ) {
    $product_id = absint( $product_id );
    if ( ! $product_id || ! function_exists( 'emdo_mentta_exclusive_ids' ) ) { return false; }
    $ids = emdo_mentta_exclusive_ids();
    if ( in_array( $product_id, $ids, true ) ) { return true; }
    if ( 'product_variation' === get_post_type( $product_id ) ) {
        return in_array( (int) wp_get_post_parent_id( $product_id ), $ids, true );
    }
    return false;
}

/** Distinguish customer-facing Store API from private Mentta / WooCommerce REST. */
function emdo_mj_is_store_api_request() {
    $path = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
    $route = (string) ( $_GET['rest_route'] ?? '' );
    return (bool) preg_match( '~/(?:wp-json/)?wc/store(?:/v[0-9]+)?(?:/|\?|$)~i', $path )
        || (bool) preg_match( '~^/wc/store(?:/v[0-9]+)?(?:/|$)~i', $route );
}

/** Exclude the protected product from unauthenticated Store API product queries. */
add_action( 'pre_get_posts', static function ( $query ) {
    if ( ! emdo_mj_is_store_api_request() || ! function_exists( 'emdo_mentta_exclusive_ids' ) ) { return; }
    $type = $query->get( 'post_type' );
    if ( 'product' !== $type && 'product_variation' !== $type
        && ! ( is_array( $type ) && ( in_array( 'product', $type, true ) || in_array( 'product_variation', $type, true ) ) ) ) { return; }
    $ids = emdo_mentta_exclusive_ids();
    foreach ( $ids as $id ) {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
        if ( $product && $product->is_type( 'variable' ) ) {
            $ids = array_merge( $ids, array_map( 'absint', $product->get_children() ) );
        }
    }
    $query->set( 'post__not_in', array_values( array_unique( array_merge(
        (array) $query->get( 'post__not_in' ), $ids
    ) ) ) );
}, 999 );

/** Never return an exclusive product from a public Store API direct endpoint. */
add_filter( 'rest_pre_dispatch', static function ( $result, $server, $request ) {
    $route = $request instanceof WP_REST_Request ? (string) $request->get_route() : '';
    if ( preg_match( '#^/wc/store(?:/v[0-9]+)?/products/(\d+)(?:/|$)#', $route, $m )
        && emdo_mj_exclusive_or_variation( (int) $m[1] ) ) {
        return new WP_Error( 'rest_product_invalid_id', 'Producto no encontrado.', array( 'status' => 404 ) );
    }
    return $result;
}, 10, 3 );

/** A second safety net for Store API responses and public WordPress search REST. */
add_filter( 'rest_post_dispatch', static function ( $response, $server, $request ) {
    if ( ! ( $request instanceof WP_REST_Request ) || ! ( $response instanceof WP_REST_Response ) ) { return $response; }
    $route = (string) $request->get_route();
    if ( ! preg_match( '#^/(?:wc/store(?:/v[0-9]+)?/|wp/v2/search(?:/|$))#', $route ) ) { return $response; }
    $data = $response->get_data();
    if ( ! is_array( $data ) ) { return $response; }
    if ( isset( $data['id'] ) && emdo_mj_exclusive_or_variation( (int) $data['id'] ) ) {
        $response->set_status( 404 );
        $response->set_data( array( 'code' => 'rest_product_invalid_id', 'message' => 'Producto no encontrado.', 'data' => array( 'status' => 404 ) ) );
        return $response;
    }
    $filter = static function ( $item ) {
        return ! ( is_array( $item ) && isset( $item['id'] ) && emdo_mj_exclusive_or_variation( (int) $item['id'] ) );
    };
    if ( array_is_list( $data ) ) {
        $response->set_data( array_values( array_filter( $data, $filter ) ) );
    } elseif ( isset( $data['products'] ) && is_array( $data['products'] ) ) {
        $data['products'] = array_values( array_filter( $data['products'], $filter ) );
        $response->set_data( $data );
    }
    return $response;
}, 999, 3 );

/** No add-to-cart by visitors, even if somebody guesses an ID. Mentta REST is unaffected. */
add_filter( 'woocommerce_add_to_cart_validation', static function ( $passed, $product_id, $quantity, $variation_id = 0 ) {
    $is_public = function_exists( 'emdo_mentta_exclusive_public_request' )
        && emdo_mentta_exclusive_public_request();
    if ( $is_public || emdo_mj_is_store_api_request() ) {
        if ( emdo_mj_exclusive_or_variation( $product_id ) || emdo_mj_exclusive_or_variation( $variation_id ) ) {
            return false;
        }
    }
    return $passed;
}, 100, 4 );
