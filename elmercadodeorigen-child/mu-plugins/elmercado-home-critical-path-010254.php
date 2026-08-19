<?php
/**
 * Final Home critical rendering path fixes 0.10.254.
 *
 * Home-only and deliberately isolated from WooCommerce, checkout and vendor
 * pages. It removes known render blockers that survived the theme optimisation
 * pipeline and repairs accessibility/dependency output at the final HTML layer.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_home_critical_is_front_010254(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

/**
 * Jetpack/WordPress CDN rewrites core jQuery to c0.wp.com. jQuery remains
 * intentionally synchronous when WordPress says the dependency tree requires
 * it, but serving it first-party reuses the document connection and avoids an
 * extra cross-origin critical request. The core version busts browser caches.
 */
add_filter(
	'script_loader_src',
	static function ( string $src, string $handle ): string {
		if ( ! elmercado_home_critical_is_front_010254() ) {
			return $src;
		}

		global $wp_version;
		$version = is_string( $wp_version ) && '' !== $wp_version ? $wp_version : null;

		if ( 'jquery-core' === $handle ) {
			$url = includes_url( 'js/jquery/jquery.min.js' );
			return $version ? add_query_arg( 'ver', $version, $url ) : $url;
		}

		if ( 'jquery-migrate' === $handle ) {
			$url = includes_url( 'js/jquery/jquery-migrate.min.js' );
			return $version ? add_query_arg( 'ver', $version, $url ) : $url;
		}

		return $src;
	},
	PHP_INT_MAX,
	2
);

/**
 * Ask WordPress itself to defer jQuery on the Home. Core evaluates the complete
 * dependency tree and falls back to blocking when a delayed strategy is unsafe.
 */
add_action(
	'wp_print_scripts',
	static function (): void {
		if ( ! elmercado_home_critical_is_front_010254() ) {
			return;
		}

		foreach ( array( 'jquery-core', 'jquery-migrate' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_script_add_data( $handle, 'strategy', 'defer' );
			}
		}
	},
	-20000
);

/**
 * TranslatePress' two tiny front-end scripts do not need to block first paint.
 * `defer` preserves document order, unlike async.
 */
add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src ): string {
		if ( ! elmercado_home_critical_is_front_010254() ) {
			return $tag;
		}

		if (
			str_contains( $src, 'trp-frontend-compatibility.js' )
			|| str_contains( $src, 'trp-frontend-language-switcher.js' )
		) {
			if ( ! preg_match( '~\sdefer(?:\s|=|>)~i', $tag ) ) {
				$tag = preg_replace( '~<script\b~i', '<script defer', $tag, 1 ) ?? $tag;
			}
		}

		return $tag;
	},
	PHP_INT_MAX,
	3
);

/**
 * Inline the 2–3 KiB TranslatePress language-switcher stylesheet so it stops
 * creating a render-blocking network round trip. Relative url() references are
 * expanded to absolute stylesheet-directory URLs if the plugin ever adds one.
 */
add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href, string $media ): string {
		if ( ! elmercado_home_critical_is_front_010254() || ! str_contains( $href, 'trp-language-switcher-v2.css' ) ) {
			return $html;
		}

		$url_path = (string) wp_parse_url( $href, PHP_URL_PATH );
		$needle   = '/wp-content/';
		$pos      = strpos( $url_path, $needle );
		if ( false === $pos ) {
			return $html;
		}

		$relative = ltrim( substr( $url_path, $pos + strlen( $needle ) ), '/' );
		$file     = WP_CONTENT_DIR . '/' . $relative;
		$real     = realpath( $file );
		$root     = realpath( WP_CONTENT_DIR );
		if ( ! $real || ! $root || ! str_starts_with( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $root ) ) ) || ! is_readable( $real ) ) {
			return $html;
		}

		$css = file_get_contents( $real );
		if ( false === $css || '' === trim( $css ) || strlen( $css ) > 65536 ) {
			return $html;
		}

		$base = trailingslashit( dirname( preg_replace( '~[?#].*$~', '', $href ) ) );
		$css  = preg_replace_callback(
			'~url\(\s*(["\']?)(?!data:|https?:|//|/|#)([^)"\']+)\1\s*\)~i',
			static function ( array $m ) use ( $base ): string {
				return 'url("' . esc_url_raw( $base . trim( $m[2] ) ) . '")';
			},
			$css
		) ?? $css;

		return '<style id="' . esc_attr( $handle ) . '-inline-010254" media="' . esc_attr( $media ?: 'all' ) . '">' . $css . '</style>';
	},
	PHP_INT_MAX,
	4
);

/**
 * Make wp-hooks an explicit dependency of Google Listings' gtag event bridge.
 * This protects it from the historical Home dequeue list.
 */
add_action(
	'wp_print_scripts',
	static function (): void {
		if ( ! elmercado_home_critical_is_front_010254() ) {
			return;
		}

		global $wp_scripts;
		if ( ! $wp_scripts instanceof WP_Scripts ) {
			return;
		}

		foreach ( $wp_scripts->registered as $handle => $asset ) {
			$src = isset( $asset->src ) ? (string) $asset->src : '';
			if ( str_contains( $src, '/google-listings-and-ads/js/build/gtag-events.js' ) ) {
				if ( ! in_array( 'wp-hooks', (array) $asset->deps, true ) ) {
					$asset->deps[] = 'wp-hooks';
				}
				wp_enqueue_script( 'wp-hooks' );
			}
		}
	},
	-10000
);

/**
 * Final HTML pass. This buffer starts before the legacy Home buffers, therefore
 * its callback receives their final output and can safely normalise it once.
 */
function elmercado_home_critical_output_010254( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	/* Accessibility: never disable pinch/double-tap zoom. */
	$html = preg_replace(
		'~<meta\b[^>]*\bname=(["\'])viewport\1[^>]*>~i',
		'<meta name="viewport" content="width=device-width, initial-scale=1">',
		$html,
		1
	) ?? $html;

	/* Hustle has no visible module on the custom Home; remove its huge inline CSS. */
	$html = preg_replace(
		'~<style\b[^>]*\bid=(["\'])hustle_inline_styles_front-inline-css\1[^>]*>.*?</style\s*>~is',
		'',
		$html
	) ?? $html;

	/* Avoid duplicating the final critical CSS across nested buffers/caches. */
	if ( ! str_contains( $html, 'id="elmercado-home-critical-010254"' ) ) {
		$critical = <<<'CSS'
<style id="elmercado-home-critical-010254">
@font-face{font-family:star;src:url('/wp-content/plugins/woocommerce/assets/fonts/star.woff') format('woff');font-weight:400;font-style:normal;font-display:swap}
body.home .emo-hero__copy,body.home .emo-hero__copy>p,body.home .emo-hero__copy h1,body.home .emo-hero__copy .emo-kicker{opacity:1!important;visibility:visible!important;transform:none!important;animation:none!important;transition:none!important}
</style>
CSS;
		$head_end = stripos( $html, '</head>' );
		if ( false !== $head_end ) {
			$html = substr_replace( $html, $critical, $head_end, 0 );
		}
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( elmercado_home_critical_is_front_010254() ) {
			ob_start( 'elmercado_home_critical_output_010254' );
		}
	},
	-1000000
);
