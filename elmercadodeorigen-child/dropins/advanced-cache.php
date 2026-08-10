<?php
/**
 * Early anonymous Home cache for El Mercado de Origen.
 *
 * Loaded by WordPress before plugins/themes when WP_CACHE is enabled. It only
 * serves a fresh static copy of the public Home for anonymous, cookie-free GET
 * requests. Every personalized, cart, logged-in or query-string request falls
 * through to normal WordPress execution.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ELMERCADO_EARLY_HOME_CACHE' ) ) {
	define( 'ELMERCADO_EARLY_HOME_CACHE', true );
}

$method      = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
$parts       = parse_url( $request_uri );
$path        = is_array( $parts ) && isset( $parts['path'] ) ? (string) $parts['path'] : '/';
$query       = is_array( $parts ) && isset( $parts['query'] ) ? (string) $parts['query'] : '';

if ( 'GET' !== $method || '/' !== $path || '' !== $query ) {
	return;
}

$cookie_header = isset( $_SERVER['HTTP_COOKIE'] ) ? (string) $_SERVER['HTTP_COOKIE'] : '';
$sensitive     = array(
	'wordpress_logged_in_',
	'wp_woocommerce_session_',
	'woocommerce_items_in_cart=',
	'woocommerce_cart_hash=',
	'wp-postpass_',
);

foreach ( $sensitive as $needle ) {
	if ( false !== stripos( $cookie_header, $needle ) ) {
		return;
	}
}

/* Uploads is writable by the WordPress PHP user and is available pre-bootstrap. */
$cache_file = __DIR__ . '/uploads/elmercado-home-static/index.html';
$ttl        = 10 * 60;
$mtime      = is_file( $cache_file ) ? @filemtime( $cache_file ) : false;

if ( ! is_readable( $cache_file ) || false === $mtime ) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-NOFILE' );
	}
	return;
}

if ( ( time() - (int) $mtime ) > $ttl ) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-STALE' );
	}
	return;
}

if ( ! headers_sent() ) {
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=0, must-revalidate' );
	header( 'Vary: Cookie', false );
	header( 'X-El-Mercado-Early-Cache: HIT' );
}

readfile( $cache_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
exit;
