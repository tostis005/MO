<?php
/** Permanent redirects for EMDO blog SEO consolidations, batches 1-2. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'template_redirect', static function (): void {
	if ( is_admin() ) { return; }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$map = array(
		'verduras-mas-vitamina-c-comparativa' => 'que-verduras-tienen-mas-vitamina-c',
		'verduras-mas-hierro-comparativa' => 'que-verduras-tienen-mas-hierro',
		'verduras-mas-fibra-comparativa' => 'que-verduras-tienen-mas-fibra',
		'tomates-como-elegir-madurez-conservar-usar-segun-receta' => 'tomate-como-elegir-conservar-usar-cada-tipo',
		'hortalizas-de-temporada-como-elegir-mejor' => 'verduras-temporada-espana-calendario-meses-que-comprar',
		'aove-filtrado-vs-sin-filtrar-diferencias' => 'aove-filtrado-o-sin-filtrar-diferencias',
		'aove-pierde-propiedades-al-calentarlo-que-cambia-temperatura' => 'aove-pierde-propiedades-al-calentarlo-que-cambia-al-cocinar',
		'legumbres-secas-remojo-coccion-como-elegir' => 'guia-cocinar-legumbres-secas-remojo-coccion-errores',
		'100-75-50-iberico-que-significa-porcentaje' => '50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
		'diferencias-nutricionales-jamon-100-75-50-iberico' => '50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
		'jamon-iberico-o-paleta-iberica-diferencias-cual-elegir' => 'jamon-o-paleta-diferencias-cual-elegir',
		'jamon-vs-paleta-iberica-diferencias-nutricionales' => 'jamon-o-paleta-diferencias-cual-elegir',
		'jamon-iberico-vs-jamon-serrano-diferencias-nutricionales' => 'jamon-iberico-vs-jamon-serrano-diferencias-como-elegir',
		'jamon-iberico-bellota-vs-cebo-campo-vs-cebo-diferencias-nutricionales' => 'jamon-bellota-cebo-campo-cebo-diferencias-tipos-jamon-iberico',
		'jamon-pieza-entera-o-loncheado-como-elegir' => 'jamon-en-pieza-o-loncheado-al-vacio-ventajas-que-compensa',
		'bellota-cambia-grasa-jamon-iberico' => 'acido-oleico-jamon-iberico-que-es-de-donde-procede',
		'por-que-jamon-bellota-tiene-mas-acido-oleico' => 'acido-oleico-jamon-iberico-que-es-de-donde-procede',
	);
	if ( ! isset( $map[ $path ] ) ) { return; }
	wp_redirect( home_url( '/' . $map[ $path ] . '/' ), 301, 'EMDO Blog Consolidation' );
	exit;
}, 1 );
