<?php
/**
 * Permanent redirects for EMDO blog SEO consolidations.
 * Batch 1: vegetables, olive oil and legumes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = trim( $path, '/' );

		if ( '' === $path ) {
			return;
		}

		$redirects = array(
			'verduras-mas-vitamina-c-comparativa' => 'que-verduras-tienen-mas-vitamina-c',
			'verduras-mas-hierro-comparativa' => 'que-verduras-tienen-mas-hierro',
			'verduras-mas-fibra-comparativa' => 'que-verduras-tienen-mas-fibra',
			'tomates-como-elegir-madurez-conservar-usar-segun-receta' => 'tomate-como-elegir-conservar-usar-cada-tipo',
			'hortalizas-de-temporada-como-elegir-mejor' => 'verduras-temporada-espana-calendario-meses-que-comprar',
			'aove-filtrado-vs-sin-filtrar-diferencias' => 'aove-filtrado-o-sin-filtrar-diferencias',
			'aove-pierde-propiedades-al-calentarlo-que-cambia-temperatura' => 'aove-pierde-propiedades-al-calentarlo-que-cambia-al-cocinar',
			'legumbres-secas-remojo-coccion-como-elegir' => 'guia-cocinar-legumbres-secas-remojo-coccion-errores',
		);

		if ( ! isset( $redirects[ $path ] ) ) {
			return;
		}

		$destination = home_url( '/' . $redirects[ $path ] . '/' );
		wp_redirect( $destination, 301, 'EMDO Blog Consolidation' );
		exit;
	},
	1
);
