<?php
/**
 * El Mercado de Origen - staging-only early page cache.
 *
 * Scope is deliberately narrow: cache only the anonymous default Shop landing
 * page in ES and EN. Dynamic WooCommerce/customer requests are always bypassed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
if ( 'dev.elmercadodeorigen.com' !== $host ) {
    return;
}

if ( PHP_SAPI === 'cli' ) {
    return;
}

$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
if ( 'GET' !== $method ) {
    return;
}

$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
$path = parse_url( $request_uri, PHP_URL_PATH );
$path = is_string( $path ) && '' !== $path ? '/' . ltrim( $path, '/' ) : '/';
if ( '/' !== $path ) {
    $path = rtrim( $path, '/' ) . '/';
}

// Keep Home and all dynamic/store flows uncached. Only the plain Shop landing is cached.
$cacheable_paths = array(
    '/tienda/'    => 'shop-es',
    '/en/tienda/' => 'shop-en',
);
if ( ! isset( $cacheable_paths[ $path ] ) ) {
    return;
}

// Any query string can represent filters, sorting, pagination, add-to-cart, diagnostics, etc.
$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
if ( '' !== $query ) {
    return;
}

// Never serve a shared cached page to logged-in users or customers with cart/session state.
$cookie_names = isset( $_COOKIE ) && is_array( $_COOKIE ) ? array_keys( $_COOKIE ) : array();
foreach ( $cookie_names as $cookie_name ) {
    $cookie_name = (string) $cookie_name;
    if (
        0 === strpos( $cookie_name, 'wordpress_logged_in_' ) ||
        0 === strpos( $cookie_name, 'wp_woocommerce_session_' ) ||
        in_array( $cookie_name, array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ), true )
    ) {
        return;
    }
}

// wp-content is owned by the staging site user. The generic wp-content/cache directory
// is owned by another account on this host, so keep this tiny staging cache in its own folder.
$cache_dir = __DIR__ . '/mdo-page-cache';
$cache_file = $cache_dir . '/' . $cacheable_paths[ $path ] . '.html';
$ttl = 300; // Five minutes on staging; freshness over aggressive caching.

if ( is_readable( $cache_file ) && ( time() - (int) filemtime( $cache_file ) ) < $ttl ) {
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'X-MDO-Page-Cache: HIT' );
        // The storefront sets this harmless paging helper cookie on the uncached response.
        // Keep equivalent behaviour on a cache hit for the plain shop landing.
        setcookie( 'total_page', '1', time() + 7200, '/' );
    }
    readfile( $cache_file );
    exit;
}

if ( ! headers_sent() ) {
    header( 'X-MDO-Page-Cache: MISS' );
}

// Cache the fully rendered final HTML after TranslatePress/custom output buffers finish.
ob_start( static function ( $html ) use ( $cache_dir, $cache_file ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    $status = http_response_code();
    if ( $status && 200 !== (int) $status ) {
        return $html;
    }

    if ( false === stripos( $html, '</html>' ) || false !== stripos( $html, 'WordPress database error' ) ) {
        return $html;
    }

    if ( ! is_dir( $cache_dir ) ) {
        @mkdir( $cache_dir, 0755, true );
    }

    if ( is_dir( $cache_dir ) && is_writable( $cache_dir ) ) {
        $tmp = $cache_file . '.tmp-' . getmypid();
        if ( false !== @file_put_contents( $tmp, $html, LOCK_EX ) ) {
            @rename( $tmp, $cache_file );
        } else {
            @unlink( $tmp );
        }
    }

    return $html;
} );
