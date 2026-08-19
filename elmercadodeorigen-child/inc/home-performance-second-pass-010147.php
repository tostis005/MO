<?php
/**
 * Segunda pasada de rendimiento de la portada 0.10.147.
 *
 * Elimina bundles sin interfaz en Home y ajusta la selección de imágenes al
 * tamaño que realmente ocupan en la portada. jQuery se mantiene síncrono como
 * archivo externo para preservar compatibilidad sin inflar el HTML inicial.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Los bundles comunes de Advanced Coupons no tienen interfaz activa en Home.
 */
add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		if ( str_contains( $src, '/plugins/advanced-coupons-for-woocommerce-free/' ) ) {
			return '';
		}

		/*
		 * No diferimos jquery-core ni jquery-migrate. Algunos plugins históricos
		 * conservan inicializadores inline que esperan jQuery durante el parseo.
		 * Se sirven como archivos externos para que puedan comprimirse y cachearse
		 * independientemente del documento HTML.
		 */
		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * Ajusta las imágenes al tamaño real del carrusel/productos de la portada sin
 * eliminar los candidatos responsive que genera WordPress.
 *
 * @param array<string,string>            $attributes Atributos de imagen.
 * @param WP_Post                         $attachment Adjunto.
 * @param string|array{0:int,1:int}|int[] $size Tamaño solicitado.
 * @return array<string,string>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes, WP_Post $attachment, $size ): array {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $attributes;
		}

		$class = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';

		if ( str_contains( $class, 'emo-hero-product-image' ) ) {
			$sizes = '(max-width: 767px) 41vw, (max-width: 991px) 48vw, 360px';
			$attributes['sizes'] = $sizes;
			if ( isset( $attributes['data-sizes'] ) ) {
				$attributes['data-sizes'] = $sizes;
			}
			return $attributes;
		}

		if ( ! str_contains( $class, 'attachment-woocommerce_thumbnail' ) ) {
			return $attributes;
		}

		$image = wp_get_attachment_image_src( $attachment->ID, 'woocommerce_thumbnail' );
		if ( ! is_array( $image ) ) {
			return $attributes;
		}

		$attributes['src']    = (string) $image[0];
		$attributes['width']  = (string) $image[1];
		$attributes['height'] = (string) $image[2];

		$sizes = '(max-width: 767px) calc(100vw - 32px), (max-width: 1100px) calc(50vw - 32px), 360px';
		$attributes['sizes'] = $sizes;
		if ( isset( $attributes['data-sizes'] ) ) {
			$attributes['data-sizes'] = $sizes;
		}

		/*
		 * `srcset` y `data-srcset` se conservan. WordPress/Smush pueden así elegir
		 * la variante más pequeña disponible en vez de descargar siempre 600x800.
		 */
		if ( isset( $attributes['data-src'] ) ) {
			$attributes['data-src'] = (string) $image[0];
		}

		return $attributes;
	},
	PHP_INT_MAX,
	3
);

/**
 * Compacta solo la gran capa CSS inline que ya genera performance.php.
 * Se colapsa whitespace, sin reordenar ni eliminar declaraciones.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		$styles = wp_styles();
		foreach ( array( 'woostify-parent-style', 'woostify-parent' ) as $handle ) {
			if ( ! isset( $styles->registered[ $handle ] ) ) {
				continue;
			}

			$after = $styles->get_data( $handle, 'after' );
			if ( ! is_array( $after ) || empty( $after ) ) {
				continue;
			}

			$after = array_map(
				static function ( $css ) {
					if ( ! is_string( $css ) ) {
						return $css;
					}
					$compact = preg_replace( '/\s+/', ' ', trim( $css ) );
					return is_string( $compact ) ? $compact : $css;
				},
				$after
			);

			$styles->add_data( $handle, 'after', $after );
		}
	},
	PHP_INT_MAX
);
