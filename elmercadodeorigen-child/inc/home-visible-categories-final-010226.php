<?php
/**
 * Hace que Home use todas las categorías raíz realmente visibles del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() || ! function_exists( 'elmercado_home_categories_visible_html_010226' ) ) {
			return $content;
		}

		$replacement = elmercado_home_categories_visible_html_010226();
		$start       = strpos( $content, '<section class="emo-section emo-categories"' );
		if ( '' === $replacement || false === $start ) {
			return $content;
		}

		$end = strpos( $content, '</section>', $start );
		if ( false === $end ) {
			return $content;
		}
		$end += strlen( '</section>' );

		return substr_replace( $content, $replacement, $start, $end - $start );
	},
	PHP_INT_MAX
);
