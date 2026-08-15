<?php
/**
 * Plugin Name: MDO Staging TranslatePress Shop Guard
 * Description: Prevents TranslatePress's dynamic DOM translator from running on the English Shop archive, where WooCommerce mutations can make the browser unresponsive. Server-side TranslatePress translations remain enabled.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_staging_tp_shop_guard_applies() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    if ( 'dev.elmercadodeorigen.com' !== $host ) { return false; }

    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
    $path = '/' . ltrim( (string) $path, '/' );
    $path = rtrim( $path, '/' ) . '/';

    return '/en/tienda/' === $path;
}

function mdo_staging_tp_shop_guard_dequeue_dynamic_translator() {
    if ( ! mdo_staging_tp_shop_guard_applies() ) { return; }

    // TranslatePress has already rendered the initial English HTML server-side. On this
    // WooCommerce archive the dynamic MutationObserver can repeatedly react to product
    // UI mutations, so do not start it here. Other TranslatePress/frontend scripts stay.
    wp_dequeue_script( 'trp-dynamic-translator' );
    wp_deregister_script( 'trp-dynamic-translator' );
}

add_action( 'wp_enqueue_scripts', 'mdo_staging_tp_shop_guard_dequeue_dynamic_translator', 9999 );
add_action( 'wp_footer', 'mdo_staging_tp_shop_guard_dequeue_dynamic_translator', 1 );
