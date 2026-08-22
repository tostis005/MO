<?php
/**
 * Plugin Name: MDO English Shop Public URI Guard
 * Description: Restores the browser-facing /en/shop/ URI after internal routing has resolved the WooCommerce query, preventing the legacy English router from redirecting the clean shop URL to itself.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * mdo-production-english-seo-routes.php intentionally rewrites /en/shop/ to
 * /en/tienda/ very early so WordPress/WooCommerce can resolve the native shop.
 * By template_redirect the query is already resolved. The older island router,
 * however, reads REQUEST_URI at priority 1 and otherwise mistakes that internal
 * URI for a public legacy URL, producing a 301 from /en/shop/ back to itself.
 *
 * Restore only the public English shop URI immediately before canonical
 * redirects run. This does not change query vars, products, ranking, shipping,
 * ordering, vendor state or Spanish routes.
 */
add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        if ( ! isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
            return;
        }

        $public_uri  = (string) $GLOBALS['mdoer_public_request_uri'];
        $public_path = (string) wp_parse_url( $public_uri, PHP_URL_PATH );

        if ( 1 !== preg_match( '#^/en/shop(?:/page/[1-9][0-9]*)?/?$#i', $public_path ) ) {
            return;
        }

        $_SERVER['REQUEST_URI'] = $public_uri;
        $GLOBALS['mdo_en_shop_public_uri_restored_20260822'] = true;
    },
    0
);
