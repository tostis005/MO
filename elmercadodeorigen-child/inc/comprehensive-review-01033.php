<?php
/**
 * Revisión integral 0.10.34: estabilidad visual de carrito y títulos de catálogo.
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
		<style id="elmercado-comprehensive-review-01034">
			/*
			 * El aviso de WooCommerce puede quedar dentro de .emo-cart-layout.
			 * Si ocupa una celda normal desplaza el formulario a la derecha y
			 * manda los totales a la fila siguiente. El aviso debe abarcar la rejilla.
			 */
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > :is(
				.woocommerce-notices-wrapper,
				.woocommerce-message,
				.woocommerce-info,
				.woocommerce-error
			) {
				grid-column: 1 / -1 !important;
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .woocommerce-cart-form {
				min-width: 0 !important;
				grid-column: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
				min-width: 0 !important;
				grid-column: 2 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart-collaterals .cart_totals {
				float: none !important;
				width: 100% !important;
				max-width: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart table.shop_table {
				width: 100% !important;
				table-layout: auto !important;
			}

			html body.elmercado-child-theme.woocommerce-cart table.shop_table .product-remove {
				width: 42px !important;
			}

			html body.elmercado-child-theme.woocommerce-cart table.shop_table .product-name {
				min-width: 220px !important;
			}

			/* Dos líneas reales y completas en catálogo de escritorio, sin recorte vertical. */
			@media (min-width: 992px) {
				html body.elmercado-child-theme .woocommerce ul.products li.product :is(
					.woocommerce-loop-product__title,
					.woostify-loop-product__title,
					.product-title,
					h2,
					h3
				) {
					display: -webkit-box !important;
					-webkit-box-orient: vertical !important;
					-webkit-line-clamp: 2 !important;
					height: auto !important;
					min-height: calc(2 * 1.35em + 4px) !important;
					max-height: none !important;
					padding-bottom: 4px !important;
					line-height: 1.35 !important;
					overflow: hidden !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
					display: block !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
					margin-top: 18px !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item {
					position: relative !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item .product-remove {
					position: absolute !important;
					top: 10px !important;
					right: 10px !important;
					z-index: 4 !important;
					width: 34px !important;
					padding: 0 !important;
					border: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item .product-remove a.remove {
					display: grid !important;
					width: 34px !important;
					height: 34px !important;
					place-items: center !important;
					margin: 0 !important;
					border-radius: 999px !important;
					font-size: 22px !important;
					line-height: 1 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item .product-name {
					min-width: 0 !important;
					padding-right: 50px !important;
				}

				/* Acciones del carrito: una composición limpia y sin fondos heredados. */
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions {
					display: grid !important;
					grid-template-columns: 1fr !important;
					gap: 10px !important;
					width: 100% !important;
					padding: 14px 10px 10px !important;
					background: transparent !important;
					border: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) auto !important;
					gap: 8px !important;
					width: 100% !important;
					padding: 0 !important;
					background: transparent !important;
					border: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon input.input-text {
					width: 100% !important;
					min-width: 0 !important;
					height: 44px !important;
					margin: 0 !important;
					padding: 0 14px !important;
					background: #faf8f2 !important;
					border: 1px solid rgba(13, 33, 27, 0.13) !important;
					border-radius: 999px !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions .coupon button.button {
					min-width: 92px !important;
					height: 44px !important;
					min-height: 44px !important;
					margin: 0 !important;
					padding: 0 14px !important;
					background: #173f32 !important;
					border-color: #173f32 !important;
					color: #fff !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions > button.button[name="update_cart"] {
					width: 100% !important;
					height: 44px !important;
					min-height: 44px !important;
					margin: 0 !important;
					padding: 0 14px !important;
					background: #173f32 !important;
					border: 1px solid #173f32 !important;
					border-radius: 999px !important;
					color: #fff !important;
					opacity: 1 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions > button.button[name="update_cart"]:disabled {
					background: #e7e4dc !important;
					border-color: #e7e4dc !important;
					color: #6b736f !important;
					cursor: default !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
