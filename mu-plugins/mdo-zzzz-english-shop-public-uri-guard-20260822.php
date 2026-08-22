<?php
/**
 * Plugin Name: MDO English Public URI Guard
 * Description: Resolves internally mapped English commerce routes and restores clean browser-facing English URIs before legacy canonical handlers can self-redirect.
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mdo_ecg_public_uri_20260822(): string {
    if ( isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
        return (string) $GLOBALS['mdoer_public_request_uri'];
    }
    return isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
}

function mdo_ecg_public_path_20260822(): string {
    return (string) wp_parse_url( mdo_ecg_public_uri_20260822(), PHP_URL_PATH );
}

/**
 * The production English router maps /en/product/<english-slug>/ to the native
 * /en/producto/<spanish-slug>/ path before parse_request. The legacy island
 * router only resolves product/<english-slug>, so the internally mapped native
 * product could otherwise remain a 404 and then canonicalise back to itself.
 * Resolve that native product explicitly while preserving the public clean URL.
 */
add_action(
    'parse_request',
    static function ( WP $wp ): void {
        if ( 1 !== preg_match( '#^/en/product/[^/]+/?$#i', mdo_ecg_public_path_20260822() ) ) {
            return;
        }

        $internal_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        $internal_path = (string) wp_parse_url( $internal_uri, PHP_URL_PATH );
        if ( 1 !== preg_match( '#^/en/producto/([^/]+)/?$#i', $internal_path, $match ) ) {
            return;
        }

        $native_slug = sanitize_title( rawurldecode( (string) $match[1] ) );
        if ( '' === $native_slug ) {
            return;
        }

        $product = get_page_by_path( $native_slug, OBJECT, 'product' );
        if ( ! $product instanceof WP_Post || 'publish' !== $product->post_status ) {
            return;
        }

        foreach ( array( 'error', 'name', 'pagename', 'page_id', 'attachment', 'product', 'product_cat', 'product_tag', 'category_name' ) as $key ) {
            unset( $wp->query_vars[ $key ] );
        }
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['p'] = (int) $product->ID;
        $GLOBALS['mdo_en_commerce_product_resolved_20260822'] = (int) $product->ID;
    },
    PHP_INT_MAX
);

/**
 * Return true only for a published English page/post alias. This deliberately
 * excludes taxonomy, vendor and commerce-base routes, which have their own
 * resolvers and canonical guards.
 */
function mdo_ecg_is_clean_content_alias_20260822( string $public_path ): bool {
    if ( 1 !== preg_match( '#^/en/([^/]+)/?$#i', $public_path, $match ) ) {
        return false;
    }

    $slug = sanitize_title( rawurldecode( (string) $match[1] ) );
    if ( '' === $slug || in_array( $slug, array( 'shop', 'store', 'product', 'product-category', 'product-tag' ), true ) ) {
        return false;
    }

    if ( function_exists( 'mdoer_post_row_by_en_slug' ) ) {
        return is_array( mdoer_post_row_by_en_slug( $slug, array( 'page', 'post' ) ) );
    }

    if ( function_exists( 'mdo_en_find_by_slug' ) ) {
        return (bool) ( mdo_en_find_by_slug( 'page', $slug ) || mdo_en_find_by_slug( 'post', $slug ) );
    }

    return false;
}

/**
 * By template_redirect WordPress has already parsed the internally mapped query.
 * Put the clean browser-facing URI back so legacy canonical handlers see the URL
 * the visitor actually requested instead of the internal Spanish rewrite target.
 */
add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        $public_uri  = mdo_ecg_public_uri_20260822();
        $public_path = (string) wp_parse_url( $public_uri, PHP_URL_PATH );
        $is_clean_shop   = 1 === preg_match( '#^/en/shop(?:/page/[1-9][0-9]*)?/?$#i', $public_path );
        $is_clean_product = 1 === preg_match( '#^/en/product/[^/]+/?$#i', $public_path );
        $is_clean_content = mdo_ecg_is_clean_content_alias_20260822( $public_path );

        if ( ! $is_clean_shop && ! $is_clean_product && ! $is_clean_content ) {
            return;
        }

        $_SERVER['REQUEST_URI'] = $public_uri;
        $GLOBALS['mdo_en_public_uri_restored_20260822'] = true;
    },
    0
);
