<?php
/**
 * Densidad final de tarjetas de producto 0.10.162.
 *
 * Oculta las reseñas en los listados, compacta el cuerpo de las tarjetas y
 * normaliza la imagen de catálogo a un lienzo cuadrado sin descargar el original.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Derivados específicos para tarjetas. Se conservan sin recorte para que una
 * foto vertical u horizontal se vea completa dentro del cuadrado visual.
 * 480 sirve como base y 800 como variante de alta densidad, evitando usar el
 * original de varios megapíxeles en listados.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		add_image_size( 'elmercado_catalog_card_010241', 480, 480, false );
		add_image_size( 'elmercado_catalog_card_2x_010241', 800, 800, false );
	},
	5
);

/* WooCommerce usa este tamaño para las imágenes de cualquier loop de producto. */
add_filter(
	'single_product_archive_thumbnail_size',
	static function ( $size ) {
		if ( is_admin() ) {
			return $size;
		}
		return 'elmercado_catalog_card_010241';
	},
	PHP_INT_MAX
);

/*
 * Srcset deliberadamente limitado a los dos derivados de catálogo. Así un
 * móvil retina obtiene como máximo el derivado de 800 px y nunca salta al
 * fichero original por estar presente en el srcset general de WordPress.
 *
 * @param array<string,mixed> $attr       Atributos de imagen.
 * @param WP_Post             $attachment Adjunto.
 * @param string|int[]        $size       Tamaño solicitado.
 * @return array<string,mixed>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr, $attachment, $size ): array {
		if ( is_admin() || 'elmercado_catalog_card_010241' !== $size || ! $attachment instanceof WP_Post ) {
			return $attr;
		}

		$attachment_id = (int) $attachment->ID;
		$base          = wp_get_attachment_image_src( $attachment_id, 'elmercado_catalog_card_010241' );
		$retina        = wp_get_attachment_image_src( $attachment_id, 'elmercado_catalog_card_2x_010241' );
		$sources       = array();

		foreach ( array( $base, $retina ) as $source ) {
			if ( ! is_array( $source ) || empty( $source[0] ) || empty( $source[1] ) ) {
				continue;
			}
			$width = max( 1, (int) $source[1] );
			$sources[ $width ] = esc_url( (string) $source[0] ) . ' ' . $width . 'w';
		}

		if ( is_array( $base ) && ! empty( $base[0] ) ) {
			$attr['src'] = esc_url( (string) $base[0] );
		}
		if ( $sources ) {
			ksort( $sources, SORT_NUMERIC );
			$attr['srcset'] = implode( ', ', array_values( $sources ) );
			$attr['sizes']  = '(max-width: 767px) calc(100vw - 32px), (max-width: 1100px) calc(50vw - 32px), 280px';
		} else {
			unset( $attr['srcset'], $attr['sizes'] );
		}

		/* Reserva desde el HTML el mismo cuadrado que usa CSS y evita saltos. */
		$attr['width']    = '480';
		$attr['height']   = '480';
		$attr['decoding'] = 'async';

		return $attr;
	},
	PHP_INT_MAX,
	3
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-product-card-density-final-010162">
			/* Las reseñas siguen disponibles en la ficha de producto, pero no ocupan espacio en las tarjetas. */
			body.elmercado-child-theme ul.products li.product :is(
				.star-rating,
				.woocommerce-product-rating,
				.woocommerce-review-link,
				.review-count,
				.wc-block-components-product-rating
			) {
				display: none !important;
			}

			/* Evita que la rejilla estire cada tarjeta hasta la altura de la más alta de la fila. */
			body.elmercado-child-theme ul.products li.product {
				height: auto !important;
				align-self: start !important;
				padding-bottom: 0.8rem !important;
			}

			/*
			 * El área visual de producto es siempre cuadrada. La fotografía se
			 * contiene completa y centrada: una imagen vertical ya no alarga la
			 * tarjeta ni se deforma, y una horizontal mantiene el mismo encuadre.
			 */
			body.elmercado-child-theme ul.products li.product a img {
				display:block !important;
				width:100% !important;
				height:auto !important;
				aspect-ratio:1 / 1 !important;
				object-fit:contain !important;
				object-position:center center !important;
				margin-bottom: 0.65rem !important;
			}

			/* Mantiene el límite de dos líneas, sin reservar una segunda línea vacía. */
			body.elmercado-child-theme ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			),
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			) {
				min-height: 0 !important;
				height: auto !important;
				max-height: 2.7em !important;
				margin-bottom: 0 !important;
				line-height: 1.35 !important;
			}

			body.elmercadodeorigen-child-theme ul.products li.product .price,
			body.elmercado-child-theme ul.products li.product .price {
				margin-top: 0 !important;
				padding-top: 0.45rem !important;
				line-height: 1.25 !important;
			}

			body.elmercado-child-theme ul.products li.product .button {
				margin-top: 0.6rem !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme ul.products li.product {
					padding-bottom: 0.65rem !important;
				}

				body.elmercado-child-theme ul.products li.product a img {
					margin-bottom: 0.5rem !important;
				}

				body.elmercado-child-theme ul.products li.product .price {
					padding-top: 0.35rem !important;
				}

				body.elmercado-child-theme ul.products li.product .button {
					margin-top: 0.5rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
