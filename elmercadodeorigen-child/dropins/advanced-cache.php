<?php
/**
 * El Mercado de Origen early cache drop-in.
 *
 * Scope intentionally narrow:
 * - anonymous cookie-free GET requests only;
 * - no query string;
 * - only files previously generated and validated by the blog theme runtime;
 * - five minute TTL.
 *
 * Shop, product, cart, checkout, account, REST, admin and untranslated routes
 * are never inferred here. If no validated static file exists, WordPress runs
 * normally.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( PHP_SAPI === 'cli' ) {
	return;
}

$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
if ( 'GET' !== $method ) {
	return;
}

$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
if ( '' !== $query ) {
	return;
}

// Cookies analíticas/consentimiento observadas no cambian el HTML editorial.
// Cualquier cookie desconocida o de estado real mantiene el bypass seguro.
if ( ! empty( $_COOKIE ) ) {
	$harmless_prefixes = array(
		'_ga',
		'_gid',
		'_gat',
		'_gcl_',
		'_fbp',
		'_fbc',
		'_pin_unauth',
		'cookielawinfo-',
		'sbjs_',
		'tk_',
	);
	$harmless_exact = array(
		'CookieLawInfoConsent',
		'viewed_cookie_policy',
		'total_page',
	);

	foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
		$cookie_name = (string) $cookie_name;

		if ( in_array( $cookie_name, $harmless_exact, true ) ) {
			continue;
		}

		$harmless = false;
		foreach ( $harmless_prefixes as $prefix ) {
			if ( 0 === strpos( $cookie_name, $prefix ) ) {
				$harmless = true;
				break;
			}
		}

		if ( ! $harmless ) {
			return;
		}
	}
}

$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
$host = (string) preg_replace( '/:\d+$/', '', $host );
if ( ! in_array( $host, array( 'www.elmercadodeorigen.com', 'elmercadodeorigen.com' ), true ) ) {
	return;
}

$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
$path        = parse_url( $request_uri, PHP_URL_PATH );
$path        = is_string( $path ) && '' !== $path ? '/' . ltrim( $path, '/' ) : '/';
$path        = '/' !== $path ? rtrim( $path, '/' ) . '/' : '/';

if ( '/' === $path || 0 === strpos( $path, '/en/' ) ) {
	return;
}

$cache_dir  = __DIR__ . '/uploads/elmercado-blog-static-v1';
$cache_file = $cache_dir . '/' . hash( 'sha256', $host . '|' . $path ) . '.html';
$ttl        = 300;

if ( ! is_readable( $cache_file ) ) {
	return;
}

$mtime = @filemtime( $cache_file );
if ( false === $mtime || ( time() - (int) $mtime ) > $ttl ) {
	@unlink( $cache_file );
	return;
}

$size = @filesize( $cache_file );
if ( false === $size || $size < 10000 ) {
	return;
}

if ( ! headers_sent() ) {
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Cache-Control: private, no-store, max-age=0' );
	header( 'Vary: Cookie', false );
	header( 'X-El-Mercado-Blog-Early-Cache: HIT' );
}

readfile( $cache_file );
exit;
