<?php
/**
 * Carrito: recomendaciones bajo el listado y totales en la columna derecha.
 *
 * WebKit/Safari no siempre distribuye bien display: contents en rejillas.
 * La recomendacion se mueve fuera de .cart-collaterals tras cargar el DOM;
 * sin JavaScript los totales y recomendaciones siguen siendo visibles.
 *
 * 0.10.315: en escritorio las tarjetas recomendadas usan tres columnas en
 * lugar de dos, reduciendo cada producto a aproximadamente dos tercios del
 * ancho anterior sin estrechar el bloque completo de recomendaciones.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<style id="elmercado-cart-cross-sells-layout-010314">
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
				grid-template-areas:
					"cart totals"
					"cross totals";
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .woocommerce-cart-form {
				grid-area: cart !important;
				min-width: 0 !important;
			}

			/* Conservar una caja real evita los fallos de grid/display:contents en WebKit. */
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
				grid-area: totals !important;
				display: flex !important;
				flex-direction: column-reverse !important;
				gap: 1rem !important;
				box-sizing: border-box !important;
				min-width: 0 !important;
				max-width: 100% !important;
				align-self: start !important;
				position: sticky !important;
				top: 110px !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells {
				grid-area: cross !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: both !important;
				margin: clamp(1.35rem, 2.5vw, 2rem) 0 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells > h2 {
				margin: 0 0 1rem !important;
				color: #0d211b !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(1.65rem, 2.7vw, 2.35rem) !important;
				font-weight: 700 !important;
				line-height: 1.08 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells > ul.products {
				display: grid !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(.85rem, 1.8vw, 1.25rem) !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells > ul.products > li.product {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: none !important;
				margin: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cart_totals {
				grid-area: totals !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: none !important;
				align-self: start !important;
			}


			/* El cupón siempre ocupa espacio real dentro de la tabla del carrito. */
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > form.woocommerce-cart-form,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: 100% !important;
				min-width: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions {
				box-sizing: border-box !important;
				max-width: 100% !important;
				min-width: 0 !important;
				clear: both !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon {
				display: flex !important;
				visibility: visible !important;
				float: none !important;
				clear: both !important;
				flex-wrap: wrap !important;
				align-items: center !important;
				box-sizing: border-box !important;
				width: 100% !important;
				min-width: 0 !important;
				gap: .65rem !important;
				margin: 0 0 .8rem !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon #coupon_code {
				box-sizing: border-box !important;
				flex: 1 1 180px !important;
				width: auto !important;
				min-width: 0 !important;
				max-width: 100% !important;
				min-height: 44px !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon .button {
				flex: 0 1 auto !important;
				float: none !important;
				max-width: 100% !important;
				white-space: normal !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
					position: static !important;
				}
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
					grid-template-areas:
						"cart"
						"totals"
						"cross";
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells {
					margin-top: .4rem !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells > ul.products {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table_responsive,
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table_responsive > tbody {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table_responsive > tbody > tr {
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table_responsive td.actions {
					display: block !important;
					width: 100% !important;
					padding: 1rem .5rem !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon {
					flex-direction: column !important;
					align-items: stretch !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon #coupon_code,
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon .button,
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions > .button {
					flex: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
					max-width: 100% !important;
				}
			}

			@media (max-width: 640px) {
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cross-sells > ul.products {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);


/**
 * Se mueve solamente el nodo de recomendaciones para no depender de
 * display:contents ni alterar el formulario, cupones o totales de WooCommerce.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script id="elmercado-cart-cross-browser-layout-20261118">
		(() => {
			'use strict';
			const placeCrossSells = () => {
				const layout = document.querySelector('.emo-cart-layout');
				if (!layout) return;
				const form = layout.querySelector('form.woocommerce-cart-form');
				const collaterals = layout.querySelector('.cart-collaterals');
				const crossSells = collaterals?.querySelector('.cross-sells');
				if (!form || !crossSells) return;
				form.insertAdjacentElement('afterend', crossSells);
			};
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', placeCrossSells, { once: true });
			} else {
				placeCrossSells();
			}
			window.addEventListener('pageshow', placeCrossSells);
			if (window.jQuery) {
				window.jQuery(document.body).on('updated_wc_div updated_cart_totals', placeCrossSells);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
