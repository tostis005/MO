<?php
/**
 * Entrega síncrona local de jQuery en Home 0.10.148.
 *
 * Conserva el orden de ejecución que requieren los inicializadores inline, pero
 * evita que el primer pintado espere una petición adicional al CDN de WordPress.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convierte jquery-core y jquery-migrate en scripts inline síncronos únicamente
 * en la portada pública. Los handles y su árbol de dependencias se conservan.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		$scripts = wp_scripts();
		$files   = array(
			'jquery-core'    => ABSPATH . WPINC . '/js/jquery/jquery.min.js',
			'jquery-migrate' => ABSPATH . WPINC . '/js/jquery/jquery-migrate.min.js',
		);

		foreach ( $files as $handle => $path ) {
			if ( ! isset( $scripts->registered[ $handle ] ) || ! is_readable( $path ) ) {
				continue;
			}

			$source = file_get_contents( $path );
			if ( false === $source || '' === trim( $source ) ) {
				continue;
			}

			/* Evita una petición externa conservando el handle/dependencias. */
			$scripts->registered[ $handle ]->src = false;
			wp_add_inline_script( $handle, $source, 'before' );
		}
	},
	PHP_INT_MAX
);
