<?php
/**
 * Segunda pasada de rendimiento de la portada 0.10.147.
 *
 * Elimina los últimos bundles sin interfaz en Home, saca jQuery de la ruta
 * crítica conservando su orden de ejecución y ajusta la selección de imágenes
 * al tamaño que realmente ocupan en la portada.
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
		 * Tras retirar los inicializadores de plugins que no pertenecen a Home,
		 * jQuery y migrate pueden esperar al fin del parseo. Ambos conservan el
		 * orden del documento al compartir defer.
		 */
		if (
			( str_contains( $src, '/wp-includes/js/jquery/jquery.min.js' )
				|| str_contains( $src, '/wp-includes/js/jquery/jquery-migrate.min.js' ) )
			&& ! str_contains( $html, ' defer' )
		) {
			return str_replace( '<script ', '<script defer ', $html );
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * Ajusta las imágenes al tamaño real del carrusel/productos de la portada.
 *
 * @param array<string,string>           $attributes Atributos de imagen.
 * @param WP_Post                        $attachment Adjunto.
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
		unset( $attributes['srcset'], $attributes['sizes'], $attributes['data-srcset'], $attributes['data-sizes'] );

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
