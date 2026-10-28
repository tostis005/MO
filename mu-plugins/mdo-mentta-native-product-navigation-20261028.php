<?php
/**
 * Plugin Name: EMDO - Navegación de productos exclusivos
 * Description: Evita el recorrido masivo del catálogo en las fichas individuales de seis packs exclusivos.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Woostify registers woostify_product_navigation on 'wp' at priority 10.
 * Its Woostify_Adjacent_Products::get_product() loops across many invisible
 * products if a directly opened product belongs to an internal-only category.
 * Remove that small navigation widget AFTER theme setup, not the whole product
 * template, header, breadcrumbs, gallery, variations or purchase components.
 */
add_action( 'wp', static function (): void {
    if ( is_admin() || ! is_singular( 'product' ) ) { return; }
    $id = absint( get_queried_object_id() );
    if ( ! in_array( $id, array( 21814, 21818, 21820, 21904, 21906, 21908 ), true ) ) { return; }
    remove_action( 'woostify_content_top', 'woostify_product_navigation', 50 );
}, PHP_INT_MAX );

/**
 * SKU remains in WooCommerce and Mentta data for imports, but is an internal
 * reference containing the integration name. Do not show it in customer-facing
 * native product detail on these six products.
 */
add_filter( 'wc_product_sku_enabled', static function ( $enabled ) {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) { return $enabled; }
    $id = absint( get_queried_object_id() );
    return in_array( $id, array( 21814, 21818, 21820, 21904, 21906, 21908 ), true ) ? false : $enabled;
}, 100, 1 );
