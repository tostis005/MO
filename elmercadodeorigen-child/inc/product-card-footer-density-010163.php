<?php
/**
 * Compacidad del pie de tarjetas de producto 0.10.163.
 *
 * Reduce el aire entre título, precio y productor en los listados sin
 * alterar imágenes, productos, enlaces ni comportamiento de compra.
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
		<style id="elmercado-product-card-footer-density-010163">
			body.elmercado-child-theme ul.products li.product {
				padding-bottom: 0.55rem !important;
			}

			body.elmercado-child-theme ul.products li.product a img {
				margin-bottom: 0.5rem !important;
			}

			body.elmercado-child-theme ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			) {
				line-height: 1.3 !important;
				margin-bottom: 0 !important;
			}

			body.elmercado-child-theme ul.products li.product .price {
				margin: 0 !important;
				padding-top: 0.25rem !important;
				line-height: 1.15 !important;
			}

			/* WCFM reserva margen propio en "Vendido por"; lo reducimos al ritmo real del contenido. */
			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced
			) {
				min-height: 0 !important;
				margin: 0.2rem 0 0 !important;
				padding: 0 !important;
				line-height: 1.2 !important;
			}

			body.elmercado-child-theme ul.products li.product .wcfmmp_sold_by_wrapper {
				display: flex !important;
				align-items: baseline !important;
				flex-wrap: wrap !important;
				gap: 0 0.25rem !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1.2 !important;
			}

			body.elmercado-child-theme ul.products li.product .wcfmmp_sold_by_wrapper > br,
			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced
			) .wcfmmp-store-rating {
				display: none !important;
			}

			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_label,
				.wcfmmp_sold_by_wrapper a,
				.wcfm_dashboard_item_title
			) {
				margin: 0 !important;
				line-height: 1.2 !important;
			}

			body.elmercado-child-theme ul.products li.product .button {
				margin-top: 0.4rem !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme ul.products li.product {
					padding-bottom: 0.45rem !important;
				}

				body.elmercado-child-theme ul.products li.product a img {
					margin-bottom: 0.4rem !important;
				}

				body.elmercado-child-theme ul.products li.product .price {
					padding-top: 0.2rem !important;
				}

				body.elmercado-child-theme ul.products li.product :is(
					.wcfmmp_sold_by_container,
					.wcfmmp_sold_by_container_advanced
				) {
					margin-top: 0.15rem !important;
				}

				body.elmercado-child-theme ul.products li.product .button {
					margin-top: 0.35rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
