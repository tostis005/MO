<?php
/**
 * Plugin Name: MDO English Category Public Route Guard
 * Description: Keeps public /en/product-category/... URLs intact after the legacy SEO bootstrap so TranslatePress cannot canonicalise an internally rewritten Spanish route back onto the same public URL.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$GLOBALS['mdo_ec_public_uri_20260822'] = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

function mdo_ec_public_uri_20260822(): string {
    $uri = (string) ( $GLOBALS['mdoer_public_request_uri'] ?? $GLOBALS['mdo_ec_public_uri_20260822'] ?? '' );
    return $uri;
}

function mdo_ec_public_category_route_20260822(): bool {
    $path = (string) wp_parse_url( mdo_ec_public_uri_20260822(), PHP_URL_PATH );
    return 1 === preg_match( '#^/en/product-category/[^/]+(?:/page/[1-9][0-9]*)?/?$#i', $path );
}

/*
 * mdo-production-english-seo-routes.php rewrites REQUEST_URI immediately while
 * MU plugins are loading. Restore the original public English category route
 * before normal plugins (notably TranslatePress) bootstrap. The English island
 * router can resolve the public alias directly during parse_request.
 */
add_action( 'muplugins_loaded', static function (): void {
    if ( ! mdo_ec_public_category_route_20260822() ) { return; }
    $public = mdo_ec_public_uri_20260822();
    if ( '' !== $public ) {
        $_SERVER['REQUEST_URI'] = $public;
        $GLOBALS['mdo_ec_public_route_restored_20260822'] = true;
    }
}, PHP_INT_MAX );
