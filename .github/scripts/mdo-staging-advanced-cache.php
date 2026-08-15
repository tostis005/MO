<?php
/**
 * El Mercado de Origen - staging-only early page cache.
 *
 * Scope is deliberately narrow: cache anonymous default Shop landing pages and
 * the four active 1957 pilot product pages in ES/EN. Dynamic WooCommerce/customer
 * requests are always bypassed.
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

// Keep Home, checkout/cart/account and all filtered/dynamic views uncached.
// Product caching is intentionally limited to the four 1957 pilot products requested
// for the multilingual test; it is not a blanket WooCommerce product cache.
$cacheable_paths = array(
    '/tienda/'                                                   => 'shop-es',
    '/en/tienda/'                                                => 'shop-en',
    '/producto/aceite-de-oliva-virgen-extra-5l/'                 => '1957-aove-5l-es',
    '/en/producto/aceite-de-oliva-virgen-extra-5l/'              => '1957-aove-5l-en',
    '/producto/aceite-de-oliva-virgen-extra-1l/'                 => '1957-aove-15x1l-es',
    '/en/producto/aceite-de-oliva-virgen-extra-1l/'              => '1957-aove-15x1l-en',
    '/producto/aceite-de-oliva-virgen-extra-500ml-pet/'          => '1957-aove-12x500-es',
    '/en/producto/aceite-de-oliva-virgen-extra-500ml-pet/'       => '1957-aove-12x500-en',
    '/producto/pack-aceite-a-tu-gusto/'                          => '1957-pack-4x5l-es',
    '/en/producto/pack-aceite-a-tu-gusto/'                       => '1957-pack-4x5l-en',
);
if ( ! isset( $cacheable_paths[ $path ] ) ) {
    return;
}

// Any query string can represent filters, sorting, variations, add-to-cart,
// diagnostics or other state. Never cache those requests.
$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
if ( '' !== $query ) {
    return;
}

// Never serve shared cached HTML to logged-in users or customers with cart/session state.
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

$cache_dir = __DIR__ . '/mdo-page-cache';
$cache_file = $cache_dir . '/' . $cacheable_paths[ $path ] . '.html';
$ttl = 300;

if ( is_readable( $cache_file ) && ( time() - (int) filemtime( $cache_file ) ) < $ttl ) {
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'X-MDO-Page-Cache: HIT' );
        if ( '/tienda/' === $path || '/en/tienda/' === $path ) {
            setcookie( 'total_page', '1', time() + 7200, '/' );
        }
    }
    readfile( $cache_file );
    exit;
}

if ( ! headers_sent() ) {
    header( 'X-MDO-Page-Cache: MISS' );
}

// Cache the fully rendered final HTML after WordPress/TranslatePress output buffers finish.
// We intentionally do not reject on PHP's late http_response_code() value here: on the
// translated WooCommerce product routes TranslatePress can leave an internal status value
// that differs from the final public 200 response. These are ten explicitly allow-listed,
// pre-verified staging routes, and we still require a complete HTML document with no DB error.
ob_start( static function ( $html ) use ( $cache_dir, $cache_file ) {
    if ( ! is_string( $html ) || '' === $html ) {
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
