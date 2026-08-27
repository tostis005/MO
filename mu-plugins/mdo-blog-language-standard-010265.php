<?php
/**
 * Plugin Name: MDO - Blog language standard 0.10.265
 * Description: Keeps single-post interface copy aligned with the Blog / articles / categories vocabulary.
 * Version: 0.10.265
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'gettext',
	static function ( string $translated, string $text, string $domain ): string {
		if ( 'elmercadodeorigen' !== $domain || ! is_singular( 'post' ) ) {
			return $translated;
		}

		$is_english = function_exists( 'elmercado_is_english_request_010245' ) && elmercado_is_english_request_010245();
		$copy = array(
			'min de lectura'            => array( 'min de lectura', 'min read' ),
			'Volver al blog'            => array( 'Volver al blog', 'Back to the blog' ),
			'Otras entradas'            => array( 'Otros artículos', 'Other articles' ),
			'Seguir descubriendo'       => array( 'Artículos relacionados', 'Related articles' ),
			'Más historias del mercado' => array( 'También te puede interesar', 'You may also be interested' ),
			'Ver todos los artículos'   => array( 'Ver todos los artículos', 'View all articles' ),
		);

		if ( ! isset( $copy[ $text ] ) ) {
			return $translated;
		}

		return $is_english ? $copy[ $text ][1] : $copy[ $text ][0];
	},
	PHP_INT_MAX,
	3
);

/*
 * Some legacy theme button rules use !important and were overriding the blog
 * chip palette. Keep this tiny override last in <head> so only selected chips
 * share the dark primary-button colour.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! ( is_home() || is_category() ) ) {
			return;
		}
		?>
		<style id="mdo-blog-chip-palette-010265">
			body .emo-blog-discovery button.emo-blog-chip:not(.is-active) {
				background: #e4eee8 !important;
				background-color: #e4eee8 !important;
				color: #17362e !important;
				border-color: rgba(23, 54, 46, 0.18) !important;
				box-shadow: none !important;
			}
			body .emo-blog-discovery button.emo-blog-chip:not(.is-active):hover {
				background: #d8e7de !important;
				background-color: #d8e7de !important;
				color: #17362e !important;
				border-color: rgba(23, 54, 46, 0.32) !important;
			}
			body .emo-blog-discovery button.emo-blog-chip.is-active {
				background: #17362e !important;
				background-color: #17362e !important;
				color: #ffffff !important;
				border-color: #17362e !important;
				box-shadow: none !important;
			}
			body .emo-blog-discovery button.emo-blog-chip.is-active:hover {
				background: #21483d !important;
				background-color: #21483d !important;
				border-color: #21483d !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
