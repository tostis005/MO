<?php
/**
 * Densidad final de tarjetas de producto 0.10.162.
 *
 * Oculta las reseñas en los listados, compacta el cuerpo de las tarjetas y
 * mantiene la entrega de imágenes de catálogo acotada sin alterar el encuadre
 * visual histórico de las fichas de producto.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Derivados específicos para tarjetas. Se conservan sin recorte para limitar
 * el peso descargado aunque el original tenga varios megapíxeles.
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
 * Devuelve un derivado acotado. Si el tamaño específico no existe todavía,
 * cae al thumbnail de WooCommerce antes que al original.
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
 * Woostify escribe explícitamente `woocommerce_thumbnail` en su loop. Detectamos
 * sus imágenes principal/hover y sustituimos sólo los recursos descargados por
 * derivados acotados. La geometría y el object-fit quedan de nuevo en manos de
 * las reglas históricas de las tarjetas para conservar el aspecto anterior.
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
			$attr['src']    = esc_url( (string) $base[0] );
			$attr['width']  = (string) max( 1, (int) $base[1] );
			$attr['height'] = (string) max( 1, (int) $base[2] );
		}

		if ( $sources ) {
			ksort( $sources, SORT_NUMERIC );
			$attr['srcset'] = implode( ', ', array_values( $sources ) );
			$attr['sizes']  = '(max-width: 767px) calc(100vw - 32px), (max-width: 1100px) calc(50vw - 32px), 280px';
		} else {
			unset( $attr['srcset'], $attr['sizes'] );
		}

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

			/* 0.10.246: compactamos la zona de texto real de Woostify/WCFM. */
			body.elmercado-child-theme ul.products li.product .product-loop-content {
				padding-bottom: 6px !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content > .woocommerce-loop-product__title {
				min-height: 0 !important;
				height: auto !important;
				max-height: none !important;
				margin: 0 0 5px !important;
				padding: 0 !important;
				line-height: 1.27 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-meta,
			body.elmercado-child-theme ul.products li.product .product-loop-meta .animated-meta {
				min-height: 0 !important;
				height: auto !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-meta .animated-meta > .price,
			body.elmercado-child-theme ul.products li.product .product-loop-meta .price {
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1.18 !important;
			}
			/* 0.10.247: punto medio entre la ficha original y la compactación 0.10.246. */
			body.elmercado-child-theme ul.products li.product .product-loop-wrapper > .wcfmmp_sold_by_container {
				min-height: 0 !important;
				height: auto !important;
				margin: 10px 6px 0 !important;
				padding: 6px 0 0 !important;
				line-height: 1.12 !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme ul.products li.product {
					padding-bottom: 0.65rem !important;
				}
				body.elmercado-child-theme ul.products li.product .price {
					padding-top: 0 !important;
				}
				body.elmercado-child-theme ul.products li.product .product-loop-content {
					padding-bottom: 5px !important;
				}
				body.elmercado-child-theme ul.products li.product .product-loop-content > .woocommerce-loop-product__title {
					margin-bottom: 4px !important;
					line-height: 1.23 !important;
				}
				body.elmercado-child-theme ul.products li.product .product-loop-wrapper > .wcfmmp_sold_by_container {
					margin: 9px 5px 0 !important;
					padding-top: 5px !important;
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
 * 0.10.266: los shortcodes/bloques de WooCommerce embebidos en una entrada
 * pasan por las mismas acciones de loop que la tienda. Algunas combinaciones
 * históricas de Woostify + lazy-load pueden dejar la tarjeta completa pero sin
 * un <img src="..."> utilizable. Capturamos únicamente la zona visual del loop
 * en entradas y garantizamos una imagen principal real como fallback.
 */
add_action(
	'woocommerce_before_shop_loop_item_title',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}

		ob_start();
	},
	-9999
);

add_action(
	'woocommerce_before_shop_loop_item_title',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) || 0 === ob_get_level() ) {
			return;
		}

		$markup = (string) ob_get_clean();
		$has_real_src = 1 === preg_match(
			'/<img\b[^>]*\bsrc=(?:"|\')(?!data:|about:blank)(?:https?:|\/\/|\/)[^"\']+(?:"|\')/i',
			$markup
		);

		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $has_real_src ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$image = $product->get_image(
			'woocommerce_thumbnail',
			array(
				'class'    => 'elmercado-blog-product-image-fallback',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( '' === trim( (string) $image ) ) {
			return;
		}

		printf(
			'<a class="elmercado-blog-product-image-fallback-link" href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $product->get_permalink() ),
			esc_attr( $product->get_name() ),
			$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-product-image-repair-010266">
			body.single-post .emo-article-content ul.products li.product :is(
				img.product-loop-image,
				img.attachment-woocommerce_thumbnail,
				img.elmercado-blog-product-image-fallback
			) {
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
				max-width: 100% !important;
			}

			body.single-post .emo-article-content ul.products li.product .elmercado-blog-product-image-fallback-link {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				overflow: hidden !important;
			}

			body.single-post .emo-article-content ul.products li.product .elmercado-blog-product-image-fallback {
				width: 100% !important;
				height: auto !important;
				aspect-ratio: 1 / 1 !important;
				object-fit: cover !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<script id="elmercado-blog-product-image-repair-010266-js">
		(() => {
			'use strict';
			const repair = (root = document) => {
				root.querySelectorAll?.('.emo-article-content ul.products li.product img').forEach((img) => {
					const src = (img.getAttribute('src') || '').trim();
					if (src && !src.startsWith('data:') && src !== 'about:blank') return;
					const candidate = img.getAttribute('data-src') || img.getAttribute('data-lazy-src') || img.getAttribute('data-original');
					if (!candidate) return;
					img.setAttribute('src', candidate);
					const srcset = img.getAttribute('data-srcset') || img.getAttribute('data-lazy-srcset');
					if (srcset) img.setAttribute('srcset', srcset);
				});
			};

			repair();
			new MutationObserver((mutations) => {
				mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
					if (node.nodeType === Node.ELEMENT_NODE) repair(node);
				}));
			}).observe(document.querySelector('.emo-article-content') || document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
