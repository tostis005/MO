<?php
/**
 * Coherencia visual final: blog, mini-carrito y resumen del carrito.
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
		<style id="elmercado-visual-coherence-01063">
			/* Blog y archivos: una sola línea de margen, igual que la cabecera editorial. */
			body.elmercado-child-theme.elmercado-editorial-content .emo-journal-listing > .emo-shell,
			body.elmercado-child-theme.blog .emo-journal-listing > .emo-shell,
			body.elmercado-child-theme.archive .emo-journal-listing > .emo-shell {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
				padding-inline: 0 !important;
				box-sizing: border-box !important;
			}

			body.elmercado-child-theme .emo-journal-toolbar,
			body.elmercado-child-theme .emo-journal-grid,
			body.elmercado-child-theme .emo-journal-pagination {
				min-width: 0;
				width: 100%;
			}

			/* Carrito completo: el bloque de totales usa todo el ancho de su columna. */
			body.elmercado-child-theme.woocommerce-cart .cart-collaterals {
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			/* Garantías bajo el CTA: compactas, legibles y con ritmo uniforme. */
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) !important;
				gap: 8px !important;
				margin: 12px 0 0 !important;
				padding: 12px 0 0 !important;
				border-top: 1px solid rgba(255, 255, 255, .12) !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance > span {
				display: flex !important;
				min-width: 0 !important;
				min-height: 0 !important;
				align-items: flex-start !important;
				gap: 8px !important;
				margin: 0 !important;
				padding: 0 !important;
				color: rgba(255, 255, 255, .76) !important;
				font-size: 12px !important;
				font-weight: 680 !important;
				line-height: 1.35 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance > span::before {
				display: block !important;
				width: 6px !important;
				height: 6px !important;
				min-width: 6px !important;
				flex: 0 0 6px !important;
				margin: 5px 0 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 50% !important;
				background: #d7a84f !important;
				content: "" !important;
			}

			@media (max-width: 767px) {
				/* Mini-carrito: imagen y nombre comparten fila; cantidad queda bajo el nombre. */
				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) {
					position: relative !important;
					display: grid !important;
					grid-template-columns: 78px minmax(0, 1fr) !important;
					align-items: start !important;
					column-gap: 12px !important;
					row-gap: 7px !important;
					width: 100% !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 14px 0 !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) > a:not(.remove):not(.remove_from_cart_button) {
					display: grid !important;
					grid-column: 1 / -1 !important;
					grid-template-columns: 78px minmax(0, 1fr) !important;
					align-items: start !important;
					gap: 12px !important;
					width: 100% !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 28px 0 0 !important;
					overflow: visible !important;
					color: #173f32 !important;
					font-family: Georgia, "Times New Roman", serif !important;
					font-size: 15px !important;
					font-weight: 700 !important;
					line-height: 1.32 !important;
					text-decoration: none !important;
					white-space: normal !important;
					word-break: normal !important;
					overflow-wrap: anywhere !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) > a:not(.remove):not(.remove_from_cart_button) > img,
				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) .product-thumbnail img {
					position: static !important;
					grid-column: 1 !important;
					grid-row: 1 !important;
					display: block !important;
					float: none !important;
					width: 78px !important;
					height: 92px !important;
					max-width: 78px !important;
					margin: 0 !important;
					padding: 0 !important;
					border-radius: 12px !important;
					object-fit: cover !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) :is(.product-name, .mini-cart-product-title, .woocommerce-mini-cart-item__title) {
					grid-column: 2 !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					color: #173f32 !important;
					font-family: Georgia, "Times New Roman", serif !important;
					font-size: 15px !important;
					font-weight: 700 !important;
					line-height: 1.32 !important;
					white-space: normal !important;
					overflow: visible !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) > :is(.quantity, .mini-cart-quantity) {
					grid-column: 2 !important;
					justify-self: start !important;
					margin: 0 !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar :is(.woocommerce-mini-cart-item, .mini_cart_item) > :is(.remove, .remove_from_cart_button) {
					position: absolute !important;
					top: 12px !important;
					right: 0 !important;
					z-index: 3 !important;
				}

				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
					gap: 7px !important;
					margin-top: 10px !important;
					padding-top: 10px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
