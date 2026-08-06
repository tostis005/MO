<?php
/**
 * Integra la capa editorial en la entrega optimizada de la portada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		$parent_handle = wp_style_is( 'woostify-parent-style', 'registered' )
			? 'woostify-parent-style'
			: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : '' );
		$stylesheet    = ELMERCADO_THEME_PATH . '/assets/css/editorial.css';

		if ( '' !== $parent_handle && is_readable( $stylesheet ) ) {
			$content = file_get_contents( $stylesheet );

			if ( false !== $content && '' !== trim( $content ) ) {
				wp_add_inline_style( $parent_handle, (string) preg_replace( '!/\*.*?\*/!s', '', $content ) );
			}
		}

		wp_dequeue_style( 'elmercado-editorial' );
	},
	PHP_INT_MAX
);

add_action(
	'wp_print_styles',
	static function (): void {
		if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() ) {
			wp_dequeue_style( 'elmercado-editorial' );
		}
	},
	PHP_INT_MAX
);
