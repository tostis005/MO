<?php
/**
 * Densidad final de tarjetas de producto 0.10.162.
 *
 * Oculta las reseñas en los listados y compacta el cuerpo de las tarjetas
 * sin alterar imágenes, productos, enlaces ni comportamiento de compra.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

			body.elmercado-child-theme ul.products li.product a img {
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
