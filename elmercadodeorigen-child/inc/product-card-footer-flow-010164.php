<?php
/**
 * Cierre compacto real de tarjetas de producto 0.10.164.
 *
 * Mantiene precio y productor en el flujo normal, elimina el separador y
 * reduce el espacio residual que WCFM deja al final de los listados.
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
		<style id="elmercado-product-card-footer-flow-010164">
			body.elmercado-child-theme ul.products li.product {
				height: auto !important;
				min-height: 0 !important;
				padding-bottom: 0.4rem !important;
				gap: 0 !important;
			}

			body.elmercado-child-theme ul.products li.product .price {
				flex: 0 0 auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0.15rem 0.85rem 0 !important;
				line-height: 1.15 !important;
			}

			/* El contenedor de WCFM queda inmediatamente después del precio. */
			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced
			) {
				position: static !important;
				inset: auto !important;
				display: flex !important;
				align-items: baseline !important;
				flex: 0 0 auto !important;
				flex-wrap: wrap !important;
				width: auto !important;
				min-width: 0 !important;
				min-height: 0 !important;
				clear: none !important;
				float: none !important;
				gap: 0 0.25rem !important;
				margin: 0.18rem 0.85rem 0 !important;
				padding: 0 !important;
				border: 0 !important;
				box-shadow: none !important;
				line-height: 1.15 !important;
			}

			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced
			)::before,
			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced
			)::after {
				display: none !important;
				content: none !important;
			}

			body.elmercado-child-theme ul.products li.product .wcfmmp_sold_by_wrapper {
				display: flex !important;
				align-items: baseline !important;
				flex: 1 1 auto !important;
				flex-wrap: wrap !important;
				min-width: 0 !important;
				min-height: 0 !important;
				gap: 0 0.25rem !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				line-height: 1.15 !important;
			}

			body.elmercado-child-theme ul.products li.product :is(
				.wcfmmp_sold_by_container,
				.wcfmmp_sold_by_container_advanced,
				.wcfmmp_sold_by_wrapper
			) br,
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
				display: inline !important;
				width: auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1.15 !important;
			}

			body.elmercado-child-theme ul.products li.product .button {
				margin-top: 0.3rem !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme ul.products li.product {
					padding-bottom: 0.32rem !important;
				}

				body.elmercado-child-theme ul.products li.product .price {
					padding: 0.12rem 0.72rem 0 !important;
				}

				body.elmercado-child-theme ul.products li.product :is(
					.wcfmmp_sold_by_container,
					.wcfmmp_sold_by_container_advanced
				) {
					margin: 0.12rem 0.72rem 0 !important;
				}

				body.elmercado-child-theme ul.products li.product .button {
					margin-top: 0.25rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
