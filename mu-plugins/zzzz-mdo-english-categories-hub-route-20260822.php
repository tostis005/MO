<?php
/**
 * Plugin Name: MDO English Categories Hub Route
 * Description: Resolves only /en/categories/ to the native categorias page while preserving the public English URL.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_echr_public_path_20260822(): string {
    $uri = isset( $GLOBALS['mdoer_public_request_uri'] )
        ? (string) $GLOBALS['mdoer_public_request_uri']
        : ( isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '' );
    return (string) wp_parse_url( $uri, PHP_URL_PATH );
}

function mdo_echr_is_hub_20260822(): bool {
    return 1 === preg_match( '#^/en/categories/?$#i', mdo_echr_public_path_20260822() );
}

/*
 * The legacy English SEO bootstrap may internally map the public alias before
 * WordPress and TranslatePress parse the request. For this one hub, restore the
 * requested English URI immediately; parse_request below supplies the native
 * page object directly, so no translated-slug inference is needed.
 */
if ( mdo_echr_is_hub_20260822() && isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
    $_SERVER['REQUEST_URI'] = (string) $GLOBALS['mdoer_public_request_uri'];
    $GLOBALS['mdo_echr_restored_public_uri_20260822'] = true;
}

add_action( 'parse_request', static function ( WP $wp ): void {
    if ( ! mdo_echr_is_hub_20260822() ) { return; }

    $page = get_page_by_path( 'categorias', OBJECT, 'page' );
    if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) { return; }

    foreach ( array(
        'error', 'name', 'pagename', 'attachment', 'attachment_id', 'p',
        'post_type', 'product', 'product_cat', 'product_tag', 'taxonomy', 'term',
        'category_name', 'cat', 'tag',
    ) as $key ) {
        unset( $wp->query_vars[ $key ] );
    }

    $wp->query_vars['page_id'] = (int) $page->ID;
    $GLOBALS['mdo_echr_page_id_20260822'] = (int) $page->ID;
}, PHP_INT_MAX );

/* The public alias itself is canonical. Never let core bounce it elsewhere. */
add_filter( 'redirect_canonical', static function ( $redirect ) {
    return mdo_echr_is_hub_20260822() ? false : $redirect;
}, PHP_INT_MAX );

/* Keep canonical helpers on the clean public English hub URL. */
add_filter( 'get_canonical_url', static function ( $url ) {
    return mdo_echr_is_hub_20260822() ? home_url( '/en/categories/' ) : $url;
}, PHP_INT_MAX );
add_filter( 'aioseo_canonical_url', static function ( $url ) {
    return mdo_echr_is_hub_20260822() ? home_url( '/en/categories/' ) : $url;
}, PHP_INT_MAX );
