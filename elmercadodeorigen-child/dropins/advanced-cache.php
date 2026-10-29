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
 * Product routes have a dedicated anonymous cache with strict state gates.
 * Cart, checkout, account, REST and admin requests are never inferred here. If
 * no validated static file exists, WordPress runs normally.
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
		'emdo_filter_pass', // Token firmado del filtro: no personaliza el HTML en caché.
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
 * Cache temprana y limitada para sugerencias anónimas de productos.
 *
 * El REST completo arranca WordPress y WooCommerce, incluso cuando los IDs
 * ya están cacheados. Las respuestas recién verificadas (máximo 25 segundos)
 * se guardan en ficheros con invalidación de cambios de catálogo.
 *
 * Los controles de método, cookies y host se han ejecutado antes de llegar
 * aquí. Cualquier búsqueda fuera del contrato vuelve al REST original.
 * Máximo 4096 buckets para impedir crecimiento ilimitado frente a bots.
 */
if ( '' !== $query && '/wp-json/elmercado/v1/quick-search/' === $path ) {
	$quick_params = array();
	parse_str( $query, $quick_params );
	$quick_allowed = is_array( $quick_params )
		&& isset( $quick_params['q'] )
		&& is_string( $quick_params['q'] )
		&& count( array_diff( array_keys( $quick_params ), array( 'q', 'lang' ) ) ) === 0
		&& ( ! isset( $quick_params['lang'] ) || is_string( $quick_params['lang'] ) );

	if ( $quick_allowed ) {
		$quick_term = trim( (string) preg_replace( '/\s+/u', ' ', $quick_params['q'] ) );
		$quick_lang = isset( $quick_params['lang'] ) && 'en' === $quick_params['lang'] ? 'en' : 'es';
		// No normalizar HTML/códigos de control antes de que WordPress los sanee.
		if ( 1 === preg_match( '/^[\p{L}\p{N} \x27\-]{2,50}$/u', $quick_term ) ) {
			$quick_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $quick_term, 'UTF-8' ) : strtolower( $quick_term );
			$quick_key   = hash( 'sha256', $quick_lang . '|' . $quick_lower );
			$quick_dir   = __DIR__ . '/uploads/elmercado-quick-search-cache-v1';
			$quick_file  = $quick_dir . '/b-' . substr( $quick_key, 0, 3 ) . '.json';
			$quick_epoch = $quick_dir . '/epoch.txt';
			$quick_time  = @filemtime( $quick_file );
			$quick_age   = false === $quick_time ? PHP_INT_MAX : max( 0, time() - (int) $quick_time );

			if ( $quick_age < 25 && is_readable( $quick_epoch ) && is_readable( $quick_file ) ) {
				$quick_epoch_value = trim( (string) @file_get_contents( $quick_epoch ) );
				$quick_raw = @file_get_contents( $quick_file );
				$quick_saved = is_string( $quick_raw ) && strlen( $quick_raw ) < 45000
					? json_decode( $quick_raw, true )
					: null;
				if ( is_array( $quick_saved )
					&& ( $quick_saved['key'] ?? '' ) === $quick_key
					&& '' !== $quick_epoch_value
					&& ( $quick_saved['epoch'] ?? '' ) === $quick_epoch_value
					&& isset( $quick_saved['products'] )
					&& is_array( $quick_saved['products'] )
					&& count( $quick_saved['products'] ) <= 6
				) {
					$quick_payload = json_encode(
						array( 'products' => $quick_saved['products'] ),
						JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
					);
					if ( is_string( $quick_payload ) ) {
						header( 'Content-Type: application/json; charset=UTF-8' );
						header( 'Cache-Control: private, no-store, max-age=0' );
						header( 'X-Robots-Tag: noindex, nofollow' );
						header( 'Vary: Cookie', false );
						header( 'X-EMDO-Quick-Search-Early-Cache: HIT' );
						echo $quick_payload;
						exit;
					}
				}
			}
		}
	}
}

// No hacer caché de otros endpoints, filtros, categorías ni búsquedas completas.
if ( '' !== $query ) {
	return;
}

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
	'/tienda/'                                               => 'es-shop-root',
);

/**
 * Five-minute early cache for canonical producer storefront HTML, including
 * each real /page/N/ URL fetched by the progressive infinite-scroll loader.
 * Query strings are excluded above: product sorting, faceted filters and
 * destination/session state must never share an anonymous HTML cache entry.
 * The file name is keyed by the full normalized path so two pages or two
 * vendors cannot share markup. Keep existing English hot keys untouched.
 */
$is_vendor_store_hot_path = 1 === preg_match(
	'#^/(?:tienda|en/store)/[a-z0-9][a-z0-9-]{0,119}/(?:page/[1-9][0-9]{0,3}/)?$#i',
	$path
);
$is_new_vendor_store_hot_path = $is_vendor_store_hot_path && ! isset( $hot_cache_paths[ $path ] );
if ( $is_new_vendor_store_hot_path ) {
	$hot_cache_paths[ $path ] = 'vendor-page-' . hash( 'sha256', $path );
}

if ( isset( $hot_cache_paths[ $path ] ) ) {
	$hot_cache_dir  = __DIR__ . '/uploads/elmercado-hot-static-v1';
	$hot_cache_file = $hot_cache_dir . '/' . $hot_cache_paths[ $path ] . '.html';
	$hot_lock_file  = $hot_cache_file . '.lock';
	$hot_ttl        = ( $is_new_vendor_store_hot_path || '/tienda/' === $path ) ? 60 : 300;
	// Generic vendor pages additionally use a four-minute Nginx cache.
	// A one-minute PHP fallback keeps the combined normal freshness at five minutes.
	// Other existing exact-route hot caches preserve their original cadence.
	$hot_stale_ttl  = ( $is_new_vendor_store_hot_path || '/tienda/' === $path ) ? 180 : 3600;
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

	$serve_hot_cache = static function ( $status ) use ( $hot_cache_file, $is_new_vendor_store_hot_path, $path ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'Cache-Control: private, no-store, max-age=0' );
			header( 'Vary: Cookie', false );
			header( 'X-El-Mercado-Hot-Early-Cache: ' . $status );
			// A cached paginated store page must not reset the scroll state.
			if ( ! $is_new_vendor_store_hot_path && '/tienda/' !== $path ) {
				setcookie( 'total_page', '1', time() + 7200, '/' );
			}
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
		static function ( $html ) use ( $hot_cache_dir, $hot_cache_file, $hot_lock_handle, $hot_have_lock, $path, $is_new_vendor_store_hot_path ) {
			$status = http_response_code();
			$status = false === $status ? 200 : (int) $status;
			$can_store =
				$hot_have_lock &&
				200 === $status &&
				is_string( $html ) &&
				strlen( $html ) >= 50000 &&
				false !== stripos( $html, '</html>' ) &&
				false === stripos( $html, 'WordPress database error' ) &&
				false === stripos( $html, 'There has been a critical error' );

			// A 404, redirect, blocked or partial producer page must never become
			// a cache hit. Both the store container and continuous catalogue script
			// must be present, otherwise the request runs uncached for safety.
			if ( $can_store && $is_new_vendor_store_hot_path ) {
				$can_store = false !== strpos( $html, 'id="wcfmmp-store"' )
					&& false !== strpos( $html, 'emo-continuous-catalog' );
			}

			// Cache only the complete anonymous canonical shop, never partial markup.
			if ( $can_store && '/tienda/' === $path ) {
				$can_store = false !== strpos( $html, 'emo-shop-title-block' )
					&& false !== strpos( $html, 'data-emo-early-filter="1"' )
					&& false !== strpos( $html, 'emdo-navidad-promo--shop' );
			}

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


/**
 * Anonymous product-page cache.
 *
 * Product pages are one of the most expensive uncached WooCommerce requests on
 * this site. A burst across many products can exhaust the 20 PHP-FPM workers,
 * which makes an otherwise healthy product intermittently take 10-60 seconds or
 * return 504. Cache only canonical public product routes and keep every stateful
 * request on the normal WordPress/WooCommerce path.
 *
 * Safety gates already applied above:
 * - GET only;
 * - no query string;
 * - no unknown cookies (therefore no login, cart or WooCommerce session);
 * - production hosts only.
 *
 * Fresh HTML lives for 10-20 minutes using deterministic per-path jitter, so
 * a bulk warm cannot make the whole catalogue expire at once. A complete stale
 * copy may be served for at most one hour while one request refreshes it.
 */
$is_product_path = 1 === preg_match( '#^/(?:producto|en/product)/[^/]+/$#i', $path );

if ( $is_product_path ) {
	$product_cache_dir   = __DIR__ . '/uploads/elmercado-product-static-v1';
	$product_cache_file  = $product_cache_dir . '/' . hash( 'sha256', $path ) . '.html';
	$product_lock_file   = $product_cache_file . '.lock';
	// Spread refreshes across a ten-minute window. Without jitter, warming a
	// catalogue in bulk synchronizes expirations and recreates PHP-FPM spikes.
	$product_ttl         = 600 + ( (int) sprintf( '%u', crc32( $path ) ) % 601 );
	$product_stale_ttl   = 3600;
	$product_age         = null;
	$product_valid       = false;

	if (
		is_readable( $product_cache_file ) &&
		(int) @filesize( $product_cache_file ) > 50000
	) {
		$product_mtime = @filemtime( $product_cache_file );
		if ( false !== $product_mtime ) {
			$product_age   = max( 0, time() - (int) $product_mtime );
			$product_valid = true;
		}
	}

	$serve_product_cache = static function ( string $status ) use ( $product_cache_file ): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'Cache-Control: private, no-store, max-age=0' );
			header( 'Vary: Cookie', false );
			header( 'X-El-Mercado-Product-Early-Cache: ' . $status );
		}
		readfile( $product_cache_file );
		exit;
	};

	if ( $product_valid && null !== $product_age && $product_age < $product_ttl ) {
		$serve_product_cache( 'HIT' );
	}

	if ( ! is_dir( $product_cache_dir ) ) {
		@mkdir( $product_cache_dir, 0755, true );
	}

	$product_lock_handle = is_dir( $product_cache_dir ) ? @fopen( $product_lock_file, 'c' ) : false;
	$product_have_lock   = $product_lock_handle && @flock( $product_lock_handle, LOCK_EX | LOCK_NB );

	if ( ! $product_have_lock && $product_valid && null !== $product_age && $product_age < $product_stale_ttl ) {
		if ( is_resource( $product_lock_handle ) ) {
			@fclose( $product_lock_handle );
		}
		$serve_product_cache( 'STALE' );
	}

	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Product-Early-Cache: ' . ( $product_have_lock ? 'REVALIDATE' : 'MISS' ) );
	}

	ob_start(
		static function ( $html ) use ( $product_cache_dir, $product_cache_file, $product_lock_handle, $product_have_lock ) {
			$status = http_response_code();
			$status = false === $status ? 200 : (int) $status;

			$looks_like_product = is_string( $html ) && 1 === preg_match(
				'/<body\\b[^>]*class=(["\\\'])[^"\\\']*\\bsingle-product\\b/i',
				$html
			);

			$can_store =
				$product_have_lock &&
				200 === $status &&
				is_string( $html ) &&
				strlen( $html ) >= 50000 &&
				$looks_like_product &&
				false !== stripos( $html, '</html>' ) &&
				false === stripos( $html, 'WordPress database error' ) &&
				false === stripos( $html, 'There has been a critical error' ) &&
				false === stripos( $html, 'Gateway Timeout' );

			if ( $can_store && is_dir( $product_cache_dir ) && is_writable( $product_cache_dir ) ) {
				$tmp = $product_cache_file . '.tmp-' . getmypid();
				if ( false !== @file_put_contents( $tmp, $html, LOCK_EX ) ) {
					@rename( $tmp, $product_cache_file );
				} else {
					@unlink( $tmp );
				}
			}

			if ( $product_have_lock && is_resource( $product_lock_handle ) ) {
				@flock( $product_lock_handle, LOCK_UN );
				@fclose( $product_lock_handle );
			}

			return $html;
		}
	);

	return;
}

/**
 * Anonymous category archives (including hierarchical categories and pagination).
 *
 * Nginx caches complete anonymous responses for 240 seconds. On a cold category
 * WooCommerce may send a harmless page-count cookie, which correctly prevents
 * the reverse proxy from storing that first response. This early file cache
 * saves the verified *public HTML only*, so the next anonymous request can
 * reach Nginx without a Set-Cookie header. PHP fallback TTL is 60 seconds:
 * maximum normal freshness is approximately 240 + 60 = 300 seconds.
 *
 * Never store filter/sort/query requests, customer cookies, partial/404/error
 * pages, or responses setting private/session cookies.
 */
$is_public_category_path = 1 === preg_match(
	'#^/(?:categoria-producto|en/product-category)/(?:[a-z0-9][a-z0-9-]*/)*[a-z0-9][a-z0-9-]*(?:/page/[1-9][0-9]*)?/$#i',
	$path
);
if ( $is_public_category_path ) {
	$category_cache_dir  = __DIR__ . '/uploads/elmercado-category-static-v1';
	$category_cache_file = $category_cache_dir . '/' . hash( 'sha256', $host . '|' . $path ) . '.html';
	$category_lock_file  = $category_cache_file . '.lock';
	$category_ttl        = 60;
	$category_stale_ttl  = 600;
	$category_mtime      = is_readable( $category_cache_file ) && (int) @filesize( $category_cache_file ) > 25000
		? @filemtime( $category_cache_file ) : false;
	$category_age        = false !== $category_mtime ? max( 0, time() - (int) $category_mtime ) : PHP_INT_MAX;

	$serve_category_cache = static function ( string $status ) use ( $category_cache_file ): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'Cache-Control: private, no-store, max-age=0' );
			header( 'Vary: Cookie', false );
			header( 'X-El-Mercado-Category-Early-Cache: ' . $status );
		}
		readfile( $category_cache_file );
		exit;
	};
	if ( $category_age < $category_ttl ) {
		$serve_category_cache( 'HIT' );
	}
	if ( ! is_dir( $category_cache_dir ) ) {
		@mkdir( $category_cache_dir, 0755, true );
	}
	$category_lock_handle = is_dir( $category_cache_dir ) ? @fopen( $category_lock_file, 'c' ) : false;
	$category_have_lock   = $category_lock_handle && @flock( $category_lock_handle, LOCK_EX | LOCK_NB );

	if ( ! $category_have_lock && $category_age < $category_stale_ttl ) {
		if ( is_resource( $category_lock_handle ) ) {
			@fclose( $category_lock_handle );
		}
		$serve_category_cache( 'STALE' );
	}
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Category-Early-Cache: ' . ( $category_have_lock ? 'REVALIDATE' : 'MISS' ) );
	}
	ob_start(
		static function ( $html ) use ( $category_cache_dir, $category_cache_file, $category_lock_handle, $category_have_lock ) {
			$status = http_response_code();
			$status = false === $status ? 200 : (int) $status;

			// Only a benign existing pagination cookie may accompany a cold
			// anonymous response. Never save HTML that issued WooCommerce or
			// WordPress login, session, authorization or other unknown cookies.
			$only_public_response_cookies = true;
			foreach ( headers_list() as $response_header ) {
				if ( 0 !== stripos( $response_header, 'Set-Cookie:' ) ) {
					continue;
				}
				if ( 1 !== preg_match( '/^Set-Cookie:\s*total_page=/i', $response_header ) ) {
					$only_public_response_cookies = false;
					break;
				}
			}

			$looks_like_category = is_string( $html )
				&& 1 === preg_match( '/<body\b[^>]*\btax-product_cat\b/i', $html );
			$can_store = $category_have_lock
				&& 200 === $status
				&& $only_public_response_cookies
				&& is_string( $html )
				&& strlen( $html ) >= 25000
				&& $looks_like_category
				&& false !== stripos( $html, '</html>' )
				&& false === stripos( $html, 'WordPress database error' )
				&& false === stripos( $html, 'There has been a critical error' )
				&& false === stripos( $html, 'Gateway Timeout' )
				&& false === stripos( $html, 'wp-die-message' );

			if ( $can_store && is_dir( $category_cache_dir ) && is_writable( $category_cache_dir ) ) {
				$tmp = $category_cache_file . '.tmp-' . getmypid();
				if ( false !== @file_put_contents( $tmp, $html, LOCK_EX ) ) {
					@rename( $tmp, $category_cache_file );
				} else {
					@unlink( $tmp );
				}
			}

			if ( $category_have_lock && is_resource( $category_lock_handle ) ) {
				@flock( $category_lock_handle, LOCK_UN );
				@fclose( $category_lock_handle );
			}
			return $html;
		}
	);
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
