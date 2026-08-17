<?php
/**
 * Garantiza el scope editorial en todas las vistas reales del blog.
 *
 * Algunas rutas del índice no heredaban la clase histórica
 * `elmercado-editorial-content`, por lo que la capa visual 0.10.248 se
 * imprimía pero sus reglas de ancho no llegaban a aplicarse.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_home() || is_archive() || is_singular( 'post' ) ) {
			$classes[] = 'elmercado-editorial-content';
			$classes   = array_values( array_unique( $classes ) );
		}

		return $classes;
	},
	PHP_INT_MAX
);
