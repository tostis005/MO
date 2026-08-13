<?php
/**
 * Inserta la lista completa y exacta de categorías de Home antes del antiguo
 * corrector de counts, para que éste no pueda limitar la portada a seis terms.
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
		$html = elmercado_home_categories_visible_html_010226();
		if ( '' === $html ) {
			return $content;
		}
		$result = preg_replace( '~<section class="emo-section emo-categories"[^>]*>.*?</section>~s', $html, $content, 1 );
		return is_string( $result ) ? $result : $content;
	},
	999
);
