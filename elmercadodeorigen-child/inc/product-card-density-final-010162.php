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
 */
add_action(
	'after_setup_theme',
	static function (): void {
		add_image_size( 'elmercado_catalog_card_010241', 480, 480, false );
		add_image_size( 'elmercado_catalog_card_2x_010241', 800, 800, false );
	},
	5
);

/**
 * Devuelve un derivado acotado. Si por cualquier motivo el tamaño específico
 * no existe todavía, cae al thumbnail de WooCommerce antes que al original.
 *
 * @return array<int,mixed>|false
 */
function elmercado_catalog_card_source_010241( int $attachment_id, string $size, int $limit ) {
	$source = wp_get_attachment_image_src( $attachment_id, $size );
	if ( is_array( $source ) && (int) $source[1] <= $limit && (int) $source[2] <= $limit ) {
		return $source;
	}

	$fallback = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );
	if ( is_array( $fallback ) && (int) $fallback[1] <= $limit && (int) $fallback[2] <= $limit ) {
		return $fallback;
	}

	return false;
}

/*
 * Woostify escribe explícitamente `woocommerce_thumbnail` en su loop y no usa
 * el filtro estándar de WooCommerce para elegir el tamaño. Detectamos las dos
 * clases de imagen del loop (principal y hover) y sustituimos sus atributos por
 * derivados acotados cuyo srcset nunca contiene el original.
 *
 * @param array<string,mixed> $attr       Atributos de imagen.
 * @param WP_Post             $attachment Adjunto.
 * @param string|int[]        $size       Tamaño solicitado por el tema.
 * @return array<string,mixed>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr, $attachment, $size ): array {
		if ( is_admin() || ! $attachment instanceof WP_Post ) {
			return $attr;
		}

		$class = (string) ( $attr['class'] ?? '' );
		if ( ! str_contains( $class, 'product-loop-image' ) && ! str_contains( $class, 'product-loop-hover-image' ) ) {
			return $attr;
		}

		$attachment_id = (int) $attachment->ID;
		$base          = elmercado_catalog_card_source_010241( $attachment_id, 'elmercado_catalog_card_010241', 480 );
		$retina        = elmercado_catalog_card_source_010241( $attachment_id, 'elmercado_catalog_card_2x_010241', 800 );
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

		/* Reserva el cuadrado antes de cargar la fotografía y evita saltos. */
		$attr['width']    = '480';
		$attr['height']   = '480';
		$attr['decoding'] = 'async';
		$attr['class']    = trim( $class . ' elmercado-catalog-card-image-010241' );

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

			body.elmercado-child-theme ul.products li.product {
				height: auto !important;
				align-self: start !important;
				padding-bottom: 0.8rem !important;
			}

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

/*
 * Esta regla se imprime en footer para quedar físicamente después de las capas
 * históricas 3:4/4:5 del tema. El lienzo visual es cuadrado; la fotografía se
 * contiene entera dentro de él, sin recortes ni deformaciones.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-product-card-square-final-010241">
			html body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product .product-loop-image-wrapper {
				position:relative !important;
				box-sizing:border-box !important;
				width:100% !important;
				height:auto !important;
				aspect-ratio:1 / 1 !important;
				overflow:hidden !important;
			}

			html body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper > a,
			html body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper .woocommerce-LoopProduct-link,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product .product-loop-image-wrapper > a {
				display:block !important;
				position:static !important;
				width:100% !important;
				height:100% !important;
			}

			html body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			html body.elmercado-child-theme ul.products li.product img.product-loop-image,
			html body.elmercado-child-theme ul.products li.product img.product-loop-hover-image,
			html body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product .product-loop-image-wrapper img,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product img.product-loop-image,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product img.product-loop-hover-image {
				display:block !important;
				position:absolute !important;
				inset:0 !important;
				box-sizing:border-box !important;
				width:100% !important;
				height:100% !important;
				max-width:100% !important;
				max-height:100% !important;
				aspect-ratio:auto !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				object-fit:contain !important;
				object-position:center center !important;
				transform:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
