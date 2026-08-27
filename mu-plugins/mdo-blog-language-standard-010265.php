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
