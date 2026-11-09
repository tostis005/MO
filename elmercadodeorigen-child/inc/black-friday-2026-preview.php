<?php
/**
 * Black Friday 2026 — vista previa aislada y no indexable.
 * No crea cupones, modifica precios ni activa regalos.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_bf2026_is_preview(): bool {
	if ( is_admin() ) { return false; }
	$raw  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $raw, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	return 'vista-previa-black-friday-2026' === $path;
}

add_action( 'template_redirect', static function (): void {
	if ( ! emdo_bf2026_is_preview() ) { return; }
	$template = get_stylesheet_directory() . '/templates/black-friday-2026-preview.php';
	if ( ! is_readable( $template ) ) { return; }
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) { $wp_query->is_404 = false; }
	status_header( 200 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	require $template;
	exit;
}, 0 );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! emdo_bf2026_is_preview() ) { return; }
	$css = '/assets/css/black-friday-2026-preview.css';
	$js  = '/assets/js/black-friday-2026-preview.js';
	wp_enqueue_style(
		'emdo-bf2026-preview',
		get_stylesheet_directory_uri() . $css,
		array( 'elmercado-theme' ),
		elmercado_asset_version( $css )
	);
	wp_enqueue_script(
		'emdo-bf2026-preview',
		get_stylesheet_directory_uri() . $js,
		array(),
		elmercado_asset_version( $js ),
		true
	);
}, 60 );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( emdo_bf2026_is_preview() ) { $classes[] = 'emdo-bf2026-page'; }
	return $classes;
} );

add_filter( 'pre_get_document_title', static function ( $title ) {
	return emdo_bf2026_is_preview() ? 'Black Friday 2026 | El Mercado de Origen' : $title;
}, 120 );

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( emdo_bf2026_is_preview() ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		$robots['noarchive'] = true;
		unset( $robots['index'], $robots['follow'] );
	}
	return $robots;
}, 100 );
