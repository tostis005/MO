<?php
/**
 * Plugin Name: MDO English Store Canonical Guard
 * Description: Prevents WordPress redirect_canonical from looping clean English WCFM store URLs that are internally mapped to native WCFM routes.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdoesg_public_path_010261(): string {
	if ( function_exists( 'mdoer_public_path' ) ) {
		return (string) mdoer_public_path();
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	return (string) wp_parse_url( $uri, PHP_URL_PATH );
}

function mdoesg_is_clean_store_route_010261(): bool {
	$path = '/' . trim( mdoesg_public_path_010261(), '/' ) . '/';
	return 1 === preg_match( '#^/en/store/[^/]+/(?:about/|page/\d+/)?$#i', $path );
}

add_filter(
	'redirect_canonical',
	static function ( $redirect_url, $requested_url ) {
		return mdoesg_is_clean_store_route_010261() ? false : $redirect_url;
	},
	PHP_INT_MAX,
	2
);
