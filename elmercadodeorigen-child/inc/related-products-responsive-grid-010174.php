<?php
/**
 * Rejilla responsiva de productos relacionados 0.10.174.
 *
 * Compacta únicamente los productos relacionados de la ficha individual y
 * adapta la densidad a 6 → 3 → 2 → 1 columnas según el ancho disponible.
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
		<style id="elmercado-related-products-responsive-grid-010174">
			body.single-product.elmercado-child-theme .related.products ul.products {
				display: grid !important;
				grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
				gap: clamp(0.75rem, 1.2vw, 1rem) !important;
				margin: 0 !important;
			}

			body.single-product.elmercado-child-theme .related.products ul.products::before,
			body.single-product.elmercado-child-theme .related.products ul.products::after {
				display: none !important;
				content: none !important;
			}

			body.single-product.elmercado-child-theme .related.products ul.products > li.product {
				float: none !important;
				clear: none !important;
				width: auto !important;
				min-width: 0 !important;
				margin: 0 !important;
			}

			@media (min-width: 1200px) {
				body.single-product.elmercado-child-theme .related.products ul.products > li.product :is(
					.woocommerce-loop-product__title,
					.product-title,
					h2,
					h3
				) {
					padding-inline: 0.78rem !important;
					font-size: 0.82rem !important;
				}

				body.single-product.elmercado-child-theme .related.products ul.products > li.product .price {
					padding-inline: 0.78rem !important;
					font-size: 0.92rem !important;
				}

				body.single-product.elmercado-child-theme .related.products ul.products > li.product :is(
					.wcfmmp_sold_by_container,
					.wcfmmp_sold_by_container_advanced
				) {
					margin-inline: 0.78rem !important;
					font-size: 0.72rem !important;
				}

				body.single-product.elmercado-child-theme .related.products ul.products > li.product .button {
					width: calc(100% - 1.56rem) !important;
					min-height: 36px !important;
					margin-inline: 0.78rem !important;
					padding: 0.5rem 0.55rem !important;
					font-size: 0.68rem !important;
				}
			}

			@media (max-width: 1199px) {
				body.single-product.elmercado-child-theme .related.products ul.products {
					grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 767px) {
				body.single-product.elmercado-child-theme .related.products ul.products {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
					gap: 0.75rem !important;
				}
			}

			@media (max-width: 374px) {
				body.single-product.elmercado-child-theme .related.products ul.products {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);