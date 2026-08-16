<?php
/**
 * Early anonymous Home cache for El Mercado de Origen.
 *
 * Loaded by WordPress before plugins/themes when WP_CACHE is enabled. It only
 * serves a fresh static copy of the public Spanish Home for anonymous GET
 * requests. Personalized, cart, logged-in, query-string and non-root requests
 * always fall through to normal WordPress execution.
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

$cache_file   = __DIR__ . '/uploads/elmercado-home-static/index.html';
$ttl          = 5 * 60;
$mtime        = is_file( $cache_file ) ? @filemtime( $cache_file ) : false;
$dropin_mtime = @filemtime( __FILE__ );
$theme_style  = __DIR__ . '/themes/elmercadodeorigen-child/style.css';
$theme_mtime  = is_file( $theme_style ) ? @filemtime( $theme_style ) : false;

if ( ! is_readable( $cache_file ) || false === $mtime ) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-NOFILE' );
	}
	return;
}

/* Never serve HTML generated before the current cache drop-in or child theme. */
if ( ( false !== $dropin_mtime && (int) $mtime < (int) $dropin_mtime ) || ( false !== $theme_mtime && (int) $mtime < (int) $theme_mtime ) ) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-RELEASE' );
	}
	return;
}

if ( ( time() - (int) $mtime ) > $ttl ) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-STALE' );
	}
	return;
}

/* Lightweight integrity guard for the current Spanish Home presentation. */
$cached_html = @file_get_contents( $cache_file );
if (
	! is_string( $cached_html ) ||
	strlen( $cached_html ) < 300000 ||
	false === stripos( $cached_html, '</html>' ) ||
	false === strpos( $cached_html, 'translatepress-es_ES' ) ||
	false === strpos( $cached_html, 'data-emo-vendor-count' )
) {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Early-Cache: MISS-INVALID' );
	}
	return;
}

if ( ! headers_sent() ) {
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=0, must-revalidate' );
	header( 'Vary: Cookie', false );
	header( 'X-El-Mercado-Early-Cache: HIT' );
}

echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
exit;
