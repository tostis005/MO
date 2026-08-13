<?php
/**
 * Force fresh responses for public WCFM vendor/store pages.
 *
 * Installed as an MU plugin in production so vendor copy changes are never
 * reused from browser/proxy HTTP caches. Other site areas keep their caching.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'send_headers',
	static function () {
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! is_string( $path ) || 0 !== strpos( '/' . ltrim( $path, '/' ), '/tienda/' ) ) {
			return;
		}

		nocache_headers();

		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
			header( 'Pragma: no-cache', true );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
			header( 'X-El-Mercado-Vendor-Cache: BYPASS', true );
		}
	},
	999
);
