<?php
/**
 * Plugin Name: MDO English Commerce Public URI Guard
 * Description: Restores clean browser-facing English shop/product URIs after internal routing has resolved the WordPress/WooCommerce query, preventing legacy canonical self-redirects.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * mdo-production-english-seo-routes.php intentionally maps clean public English
 * commerce URLs to native Spanish rewrite endpoints before WordPress parses the
 * request. By template_redirect the query is already resolved. The older island
 * router still reads REQUEST_URI at priority 1, so without this handoff it can
 * mistake the internal URI for a public legacy URL and redirect the clean URL
 * back to itself.
 *
 * Restore only the clean public shop and product URL families immediately before
 * canonical redirects run. Query vars and catalogue state are left untouched.
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

        $is_clean_shop = 1 === preg_match( '#^/en/shop(?:/page/[1-9][0-9]*)?/?$#i', $public_path );
        $is_clean_product = 1 === preg_match( '#^/en/product/[^/]+/?$#i', $public_path );

        if ( ! $is_clean_shop && ! $is_clean_product ) {
            return;
        }

        $_SERVER['REQUEST_URI'] = $public_uri;
        $GLOBALS['mdo_en_commerce_public_uri_restored_20260822'] = true;
    },
    0
);
