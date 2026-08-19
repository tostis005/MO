<?php
/**
 * Saca la pequeña cadena wp-hooks -> Google for WooCommerce gtag-events de la
 * ruta crítica de la portada. `defer` conserva el orden de los scripts y hace
 * que ambos se ejecuten tras terminar de parsear el documento.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || '' === $src ) {
			return $tag;
		}

		$is_hooks = str_contains( $src, '/wp-includes/js/dist/hooks.min.js' );
		$is_gla   = str_contains( $src, '/google-listings-and-ads/js/build/gtag-events.js' );

		if ( ! $is_hooks && ! $is_gla ) {
			return $tag;
		}

		/* No sustituimos una estrategia ya elegida por WordPress o el plugin. */
		if ( preg_match( '/\s(?:defer|async)(?:\s|=|>)/i', $tag ) ) {
			return $tag;
		}

		return preg_replace( '/^<script\b/i', '<script defer', $tag, 1 ) ?? $tag;
	},
	PHP_INT_MAX,
	3
);
