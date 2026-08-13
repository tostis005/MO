<?php
/**
 * Keep public Home copy fresh across devices.
 *
 * The Home still uses the existing render/critical-CSS optimization pipeline,
 * but its generated HTML/transient is invalidated before every public Home
 * render. HTTP headers also prevent browsers and intermediate proxies from
 * reusing an older Home document.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}

		$version = defined( 'ELMERCADO_THEME_VERSION' ) ? (string) ELMERCADO_THEME_VERSION : '';
		if ( '' !== $version ) {
			$key = 'elmercado_home_' . md5( $version . '|' . home_url( '/' ) );
			delete_transient( $key );
		}

		$static_html = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
		if ( is_file( $static_html ) ) {
			@unlink( $static_html );
		}

		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
			header( 'Pragma: no-cache', true );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
			header( 'X-El-Mercado-Home-Fresh: BYPASS', true );
		}
	},
	-3000
);
