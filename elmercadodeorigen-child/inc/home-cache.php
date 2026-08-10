<?php
/**
 * Caché breve y ruta crítica de la portada pública.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_home_cache_key(): string {
	return 'elmercado_home_' . md5( ELMERCADO_THEME_VERSION . '|' . home_url( '/' ) );
}

function elmercado_home_static_cache_file(): string {
	return WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
}

function elmercado_home_deferred_css_file(): string {
	return WP_CONTENT_DIR . '/uploads/elmercado-home-static/home-deferred-' . ELMERCADO_THEME_VERSION . '.css';
}

function elmercado_home_deferred_css_url(): string {
	return content_url( '/uploads/elmercado-home-static/home-deferred-' . ELMERCADO_THEME_VERSION . '.css' );
}

/**
 * Escritura atómica compartida por HTML y CSS de la portada.
 */
function elmercado_write_atomic_home_file( string $file, string $content ): bool {
	$directory = dirname( $file );
	if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
		return false;
	}

	$tmp = tempnam( $directory, 'home-' );
	if ( false === $tmp ) {
		return false;
	}

	$written = file_put_contents( $tmp, $content, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	if ( false === $written || $written < strlen( $content ) ) {
		@unlink( $tmp );
		return false;
	}

	@chmod( $tmp, 0644 );
	if ( ! @rename( $tmp, $file ) ) {
		@unlink( $tmp );
		return false;
	}

	return true;
}

function elmercado_write_home_static_cache( string $html ): void {
	if ( '' === $html || false === stripos( $html, '</html>' ) || false !== stripos( $html, 'wp-die-message' ) ) {
		return;
	}
	elmercado_write_atomic_home_file( elmercado_home_static_cache_file(), $html );
}

/**
 * Reduce la ruta de render de Home sin eliminar ninguna regla visual:
 * - conserva al inicio solo el CSS medido para el primer viewport;
 * - agrupa el CSS inline completo existente en una hoja asíncrona;
 * - omite en Home la sonda de título del blog que fuerza layout global.
 */
function elmercado_optimize_home_document_for_first_paint( string $html ): string {
	if ( '' === $html || false !== strpos( $html, 'id="elmercado-home-first-view-css"' ) ) {
		return $html;
	}

	$critical_file = ELMERCADO_THEME_PATH . '/assets/css/critical-woostify-home.min.css';
	$critical      = is_readable( $critical_file ) ? file_get_contents( $critical_file ) : false;
	if ( ! is_string( $critical ) || strlen( $critical ) < 12000 ) {
		return $html;
	}

	$head_start = stripos( $html, '<head' );
	$head_end   = stripos( $html, '</head>' );
	if ( false === $head_start || false === $head_end || $head_end <= $head_start ) {
		return $html;
	}
	$head_end += 7;
	$head      = substr( $html, $head_start, $head_end - $head_start );

	/* Protege JavaScript: algunos bundles contienen literales con <style>. */
	$scripts = array();
	$head_protected = preg_replace_callback(
		'~<script\b[^>]*>.*?</script\s*>~is',
		static function ( array $matches ) use ( &$scripts ): string {
			$key = '__ELMERCADO_HEAD_SCRIPT_' . count( $scripts ) . '__';
			$scripts[ $key ] = $matches[0];
			return $key;
		},
		$head
	);
	if ( ! is_string( $head_protected ) ) {
		return $html;
	}

	$css_parts = array();
	$head_protected = preg_replace_callback(
		'~<style\b[^>]*>(.*?)</style\s*>~is',
		static function ( array $matches ) use ( &$css_parts ): string {
			if ( isset( $matches[1] ) && '' !== trim( $matches[1] ) ) {
				$css_parts[] = $matches[1];
			}
			return '';
		},
		$head_protected
	);
	if ( ! is_string( $head_protected ) || empty( $css_parts ) ) {
		return $html;
	}

	$deferred_css = implode( "\n", $css_parts );
	if ( strlen( $deferred_css ) < 100000 || ! elmercado_write_atomic_home_file( elmercado_home_deferred_css_file(), $deferred_css ) ) {
		return $html;
	}

	$critical_tag = '<style id="elmercado-home-first-view-css">' . $critical . '</style>';
	$css_url      = esc_url( elmercado_home_deferred_css_url() );
	$deferred_tag = '<link id="elmercado-home-deferred-css" rel="preload" as="style" href="' . $css_url . '" onload="this.onload=null;this.rel=\'stylesheet\'">'
		. '<noscript><link rel="stylesheet" href="' . $css_url . '"></noscript>';

	$head_protected = preg_replace_callback(
		'~<head\b[^>]*>~i',
		static fn ( array $matches ): string => $matches[0] . $critical_tag,
		$head_protected,
		1
	) ?? $head_protected;
	$head_protected = preg_replace( '~</head>~i', $deferred_tag . '</head>', $head_protected, 1 ) ?? $head_protected;
	$optimized_head = strtr( $head_protected, $scripts );
	$html           = substr_replace( $html, $optimized_head, $head_start, $head_end - $head_start );

	/* Esta sonda pertenece a la corrección de títulos del blog, no a la Home. */
	$html = preg_replace_callback(
		'~<script\b[^>]*>.*?</script\s*>~is',
		static function ( array $matches ): string {
			$script = $matches[0];
			if ( str_contains( $script, 'function hasVisibleHeading()' ) && str_contains( $script, 'elmercado-blog-title' ) ) {
				return '';
			}
			return $script;
		},
		$html
	) ?? $html;

	return $html;
}

function elmercado_can_cache_home_request(): bool {
	if ( ! elmercado_is_optimized_home() || is_user_logged_in() || is_preview() || is_customize_preview() ) {
		return false;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	if ( 'GET' !== $method || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
		$name = (string) $cookie_name;
		if ( str_starts_with( $name, 'wordpress_logged_in_' ) || str_starts_with( $name, 'wp_woocommerce_session_' ) || in_array( $name, array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ), true ) ) {
			return false;
		}
	}
	return true;
}

function elmercado_home_cache_header( string $status ): void {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Cache: ' . $status );
		header( 'Vary: Cookie', false );
	}
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_can_cache_home_request() ) {
			return;
		}

		$key    = elmercado_home_cache_key();
		$cached = get_transient( $key );

		if ( is_string( $cached ) && '' !== $cached ) {
			if ( false === strpos( $cached, 'id="elmercado-home-first-view-css"' ) ) {
				$cached = elmercado_optimize_home_document_for_first_paint( $cached );
				set_transient( $key, $cached, 10 * MINUTE_IN_SECONDS );
			}

			$file         = elmercado_home_static_cache_file();
			$mtime        = is_file( $file ) ? @filemtime( $file ) : false;
			$dropin       = WP_CONTENT_DIR . '/advanced-cache.php';
			$dropin_mtime = is_file( $dropin ) ? @filemtime( $dropin ) : false;
			$needs_refresh = ! is_readable( $file ) || false === $mtime || ( time() - (int) $mtime ) > 10 * MINUTE_IN_SECONDS || ( false !== $dropin_mtime && ( false === $mtime || (int) $mtime < (int) $dropin_mtime ) );
			if ( $needs_refresh ) {
				elmercado_write_home_static_cache( $cached );
			}

			elmercado_home_cache_header( 'HIT' );
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		elmercado_home_cache_header( 'MISS' );
		ob_start(
			static function ( string $html ) use ( $key ): string {
				if ( '' !== $html && false !== stripos( $html, '</html>' ) && false === stripos( $html, 'wp-die-message' ) ) {
					$html = elmercado_optimize_home_document_for_first_paint( $html );
					set_transient( $key, $html, 10 * MINUTE_IN_SECONDS );
					elmercado_write_home_static_cache( $html );
				}
				return $html;
			}
		);
	},
	-2000
);

function elmercado_flush_home_cache(): void {
	delete_transient( elmercado_home_cache_key() );
	foreach ( array( elmercado_home_static_cache_file(), elmercado_home_deferred_css_file() ) as $file ) {
		if ( is_file( $file ) ) {
			@unlink( $file );
		}
	}
}

add_action( 'save_post_product', 'elmercado_flush_home_cache' );
add_action( 'before_delete_post', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_product_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_variation_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_order_status_changed', 'elmercado_flush_home_cache' );
add_action( 'created_product_cat', 'elmercado_flush_home_cache' );
add_action( 'edited_product_cat', 'elmercado_flush_home_cache' );
add_action( 'delete_product_cat', 'elmercado_flush_home_cache' );
