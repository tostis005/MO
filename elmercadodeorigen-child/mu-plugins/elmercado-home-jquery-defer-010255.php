<?php
/**
 * Defer jQuery on the custom Home after the rest of its head dependencies have
 * already been moved to deferred execution. Home-only: cart/checkout/products
 * keep WordPress' default loading behaviour.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src ): string {
		if ( is_admin() || ! is_front_page() || wp_doing_ajax() ) {
			return $tag;
		}

		if ( ! in_array( $handle, array( 'jquery-core', 'jquery-migrate' ), true ) ) {
			return $tag;
		}

		if ( ! preg_match( '~\sdefer(?:\s|=|>)~i', $tag ) ) {
			$tag = preg_replace( '~<script\b~i', '<script defer', $tag, 1 ) ?? $tag;
		}

		return $tag;
	},
	PHP_INT_MAX,
	3
);
