<?php
/**
 * Ajustes PSI finales de portada 0.10.149.
 *
 * Conserva únicamente prioridades responsivas, defer y recursos visuales.
 * El copy se gestiona desde una sola capa definitiva en 0.10.165.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * home-refresh solicita el mosaico con woocommerce_single, pero no añadía la
 * clase que usan las optimizaciones 0.10.146/147 para tamaños y prioridades.
 * La añadimos antes de que se ejecuten esos filtros.
 *
 * @param array<string,string>            $attributes Atributos de imagen.
 * @param WP_Post                         $attachment Adjunto.
 * @param string|array{0:int,1:int}|int[] $size Tamaño solicitado.
 * @return array<string,string>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes, WP_Post $attachment, $size ): array {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || 'woocommerce_single' !== $size ) {
			return $attributes;
		}

		$class = isset( $attributes['class'] ) ? trim( (string) $attributes['class'] ) : '';
		if ( ! str_contains( $class, 'emo-hero-product-image' ) ) {
			$attributes['class'] = trim( $class . ' emo-hero-product-image' );
		}

		return $attributes;
	},
	5,
	3
);

/**
 * Woostify imprime estos dos scripts en la ruta crítica aunque su trabajo se
 * realiza sobre un DOM ya disponible. `defer` mantiene el orden de ejecución y
 * evita bloquear el parseo inicial.
 */
add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || '' === $src ) {
			return $html;
		}

		$defer = str_contains( $src, '/themes/woostify/assets/js/general.min.js' )
			|| str_contains( $src, '/themes/woostify/assets/js/navigation.min.js' );

		if ( ! $defer || str_contains( $html, ' defer' ) || str_contains( $html, ' async' ) ) {
			return $html;
		}

		return str_replace( '<script ', '<script defer ', $html );
	},
	PHP_INT_MAX,
	3
);

/**
 * Las valoraciones de WooCommerce solo necesitan cinco estrellas visibles.
 * En Home usamos glifos del sistema para evitar que star.woff prolongue la
 * cadena crítica por un recurso de apenas 1,5 KiB.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}
		?>
		<style id="elmercado-home-native-stars">
			body.elmercado-premium-home .star-rating,
			body.elmercado-premium-home .star-rating::before,
			body.elmercado-premium-home .star-rating span::before {
				font-family: Arial, Helvetica, sans-serif !important;
				letter-spacing: .04em !important;
			}
			body.elmercado-premium-home .star-rating::before {
				content: "★★★★★" !important;
				color: #d8d7d0 !important;
			}
			body.elmercado-premium-home .star-rating span::before {
				content: "★★★★★" !important;
				color: #d7a84f !important;
			}
		</style>
		<?php
	}
);
