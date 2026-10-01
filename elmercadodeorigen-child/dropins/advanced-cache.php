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

/**
 * Hot commerce cache.
 *
 * These exact anonymous landing pages are currently receiving sustained
 * distributed crawler traffic. Keep the scope intentionally narrow:
 * - exact allow-list only;
 * - GET only, no query string;
 * - cookie gate above already bypasses logged-in/cart/session state;
 * - short 5 minute TTL;
 * - cache is generated only from a complete rendered HTML response.
 */
$hot_cache_paths = array(
	'/en/store/hidalgo-de-la-jara/'                         => 'en-store-hidalgo-de-la-jara',
	'/en/store/montjam/'                                    => 'en-store-montjam',
	'/en/product-category/hams-and-shoulders/'              => 'en-cat-hams-and-shoulders',
	'/en/product-category/hams-and-shoulders/page/2/'       => 'en-cat-hams-and-shoulders-p2',
	'/en/product-category/cured-meats/'                     => 'en-cat-cured-meats',
	'/categoria-producto/jamones-paletas/'                  => 'es-cat-jamones-paletas',
	'/categoria-producto/embutidos-y-curados/'              => 'es-cat-embutidos-curados',
);

if ( isset( $hot_cache_paths[ $path ] ) ) {
	$hot_cache_dir  = __DIR__ . '/uploads/elmercado-hot-static-v1';
	$hot_cache_file = $hot_cache_dir . '/' . $hot_cache_paths[ $path ] . '.html';
	$hot_lock_file  = $hot_cache_file . '.lock';
	$hot_ttl        = 300;
	$hot_stale_ttl  = 3600;
	$hot_age        = null;
	$hot_valid      = false;

	if (
		is_readable( $hot_cache_file ) &&
		(int) @filesize( $hot_cache_file ) > 50000
	) {
		$hot_mtime = @filemtime( $hot_cache_file );
		if ( false !== $hot_mtime ) {
			$hot_age   = max( 0, time() - (int) $hot_mtime );
			$hot_valid = true;
		}
	}

	$serve_hot_cache = static function ( $status ) use ( $hot_cache_file ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'Cache-Control: private, no-store, max-age=0' );
			header( 'Vary: Cookie', false );
			header( 'X-El-Mercado-Hot-Early-Cache: ' . $status );
			setcookie( 'total_page', '1', time() + 7200, '/' );
		}
		readfile( $hot_cache_file );
		exit;
	};

	if ( $hot_valid && null !== $hot_age && $hot_age < $hot_ttl ) {
		$serve_hot_cache( 'HIT' );
	}

	if ( ! is_dir( $hot_cache_dir ) ) {
		@mkdir( $hot_cache_dir, 0755, true );
	}

	$hot_lock_handle = is_dir( $hot_cache_dir ) ? @fopen( $hot_lock_file, 'c' ) : false;
	$hot_have_lock   = $hot_lock_handle && @flock( $hot_lock_handle, LOCK_EX | LOCK_NB );

	// If another request is already regenerating, serve the last complete copy.
	// This prevents a thundering herd from exhausting PHP-FPM/MySQL every 5 minutes.
	if ( ! $hot_have_lock && $hot_valid && null !== $hot_age && $hot_age < $hot_stale_ttl ) {
		if ( is_resource( $hot_lock_handle ) ) {
			@fclose( $hot_lock_handle );
		}
		$serve_hot_cache( 'STALE' );
	}

	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Hot-Early-Cache: ' . ( $hot_have_lock ? 'REVALIDATE' : 'MISS' ) );
	}

	ob_start(
		static function ( $html ) use ( $hot_cache_dir, $hot_cache_file, $hot_lock_handle, $hot_have_lock ) {
			$can_store =
				$hot_have_lock &&
				is_string( $html ) &&
				strlen( $html ) >= 50000 &&
				false !== stripos( $html, '</html>' ) &&
				false === stripos( $html, 'WordPress database error' ) &&
				false === stripos( $html, 'There has been a critical error' );

			if ( $can_store && is_dir( $hot_cache_dir ) && is_writable( $hot_cache_dir ) ) {
				$tmp = $hot_cache_file . '.tmp-' . getmypid();
				if ( false !== @file_put_contents( $tmp, $html, LOCK_EX ) ) {
					@rename( $tmp, $hot_cache_file );
				} else {
					@unlink( $tmp );
				}
			}

			if ( $hot_have_lock && is_resource( $hot_lock_handle ) ) {
				@flock( $hot_lock_handle, LOCK_UN );
				@fclose( $hot_lock_handle );
			}

			return $html;
		}
	);

	// The lock holder refreshes the cache. Concurrent requests receive stale HTML.
	return;
}

if ( '/' === $path || 0 === strpos( $path, '/en/' ) ) {
	return;
}

$cache_dir  = __DIR__ . '/uploads/elmercado-blog-static-v2';
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
