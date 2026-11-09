<?php
/**
 * Plugin Name: EMDO — vista previa Black Friday 2026 (producción)
 * Description: Publica exclusivamente una landing no indexable para revisión. No modifica precios, cupones ni carrito.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 *
 * Copia independiente del tema para no sincronizar ni sustituir files de producción.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * La URL se mantiene sin enlazar y se sirve como ruta virtual.
 * A diferencia de la campaña futura, no se añade cortinilla al resto del sitio.
 */
function emdo_bf2026_production_preview_route(): bool {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	return 'vista-previa-black-friday-2026' === $path;
}

add_action( 'template_redirect', static function (): void {
	if ( ! emdo_bf2026_production_preview_route() ) {
		return;
	}
	$file = __DIR__ . '/emdo-black-friday-2026-preview/landing.php';
	if ( ! is_readable( $file ) ) {
		status_header( 503 );
		wp_die( esc_html__( 'Vista previa temporalmente no disponible.', 'elmercadodeorigen' ), '', array( 'response' => 503 ) );
	}
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}
	status_header( 200 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	require $file;
	exit;
}, -100 );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! emdo_bf2026_production_preview_route() ) {
		return;
	}
	$base_url = content_url( '/mu-plugins/emdo-black-friday-2026-preview/' );
	$base_dir = __DIR__ . '/emdo-black-friday-2026-preview/';

	// Reemplaza los handles equivalentes si el módulo del tema está instalado.
	// Nunca carga dos scripts de cortinilla a la vez.
	wp_dequeue_style( 'emdo-bf2026-preview' );
	wp_deregister_style( 'emdo-bf2026-preview' );
	wp_enqueue_style(
		'emdo-bf2026-preview',
		$base_url . 'preview.css',
		array(),
		is_readable( $base_dir . 'preview.css' ) ? (string) filemtime( $base_dir . 'preview.css' ) : '1.0'
	);
	wp_dequeue_script( 'emdo-bf2026-preview' );
	wp_deregister_script( 'emdo-bf2026-preview' );
	wp_enqueue_script(
		'emdo-bf2026-preview',
		$base_url . 'preview.js',
		array(),
		is_readable( $base_dir . 'preview.js' ) ? (string) filemtime( $base_dir . 'preview.js' ) : '1.0',
		true
	);
}, 999 );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( emdo_bf2026_production_preview_route() ) {
		$classes[] = 'emdo-bf2026-page';
	}
	return array_unique( $classes );
}, 999 );

add_filter( 'pre_get_document_title', static function ( $title ) {
	return emdo_bf2026_production_preview_route()
		? 'Vista previa Black Friday 2026 | El Mercado de Origen'
		: $title;
}, 999 );

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( emdo_bf2026_production_preview_route() ) {
		unset( $robots['index'], $robots['follow'] );
		$robots['noindex']   = true;
		$robots['nofollow']  = true;
		$robots['noarchive'] = true;
	}
	return $robots;
}, 999 );
