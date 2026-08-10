<?php
/**
 * Cierre visual de Home y carrito 0.10.135.
 *
 * Elimina el hueco intermedio entre la última sección de la portada y el footer
 * en escritorio, y reduce bordes/divisores del carrito para una lectura más
 * limpia sin alterar su estructura ni la lógica de WooCommerce.
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
		<style id="elmercado-home-cart-visual-cleanup-010135">
			/* Home desktop: la última sección enlaza directamente con el footer. */
			@media (min-width: 992px) {
				html body.home.elmercado-child-theme #content.site-content,
				html body.home.elmercado-child-theme .site-content,
				html body.home.elmercado-child-theme #primary,
				html body.home.elmercado-child-theme #main,
				html body.home.elmercado-child-theme .site-main,
				html body.home.elmercado-child-theme .emo-home {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
				}

				html body.home.elmercado-child-theme .emo-home > .emo-vendor-cta:last-child {
					margin-bottom: 0 !important;
				}
			}

			/* Carrito: el encabezado no necesita una línea decorativa adicional. */
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-intro {
				border-bottom: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-intro::after {
				display: none !important;
				content: none !important;
			}

			/* El formulario funciona como lienzo, no como otra caja alrededor de las cajas. */
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form {
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				outline: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.cart {
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				outline: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table :is(thead,tbody,tfoot,tr,th,td),
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.cart :is(thead,tbody,tfoot,tr,th,td) {
				border-color: transparent !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.shop_table :is(th,td),
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form table.cart :is(th,td) {
				border-left: 0 !important;
				border-right: 0 !important;
				border-top: 0 !important;
				border-bottom: 0 !important;
			}

			/* El producto conserva una sola superficie suave, sin doble contorno. */
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form tr.cart_item,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item {
				border: 0 !important;
				outline: 0 !important;
				box-shadow: 0 12px 30px rgba(23, 63, 50, .06) !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form tr.cart_item > td,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item > td {
				border: 0 !important;
			}

			/* Cupón y acciones: controles claros, sin marco de tabla alrededor. */
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form td.actions,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .actions {
				border: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .coupon :is(input.input-text,.input-text) {
				border-color: rgba(23, 63, 50, .14) !important;
				box-shadow: none !important;
			}

			/* Totales: una única tarjeta oscura; la jerarquía la dan espacio, peso y color. */
			html body.elmercado-child-theme.woocommerce-cart .cart_totals,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table :is(thead,tbody,tfoot,tr,th,td) {
				border-color: transparent !important;
				outline: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table {
				border: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table :is(th,td),
			html body.elmercado-child-theme.woocommerce-cart .cart_totals tr.woocommerce-shipping-totals.shipping {
				border: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table :is(th,td) {
				padding-top: 9px !important;
				padding-bottom: 9px !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.order-total :is(th,td) {
				padding-top: 15px !important;
				padding-bottom: 5px !important;
			}

			/* La tarifa sigue siendo reconocible, pero sin otro borde blanco dentro del resumen. */
			html body.elmercado-child-theme.woocommerce-cart .cart_totals ul#shipping_method > li,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals ul.woocommerce-shipping-methods > li {
				border: 0 !important;
				background: rgba(255, 255, 255, .045) !important;
				box-shadow: none !important;
			}

			/* Garantías: sin separador horizontal; los puntos dorados ya estructuran el bloque. */
			html body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
				border: 0 !important;
				border-top: 0 !important;
				padding-top: 8px !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals .wc-proceed-to-checkout,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals .wc-proceed-to-checkout::before,
			html body.elmercado-child-theme.woocommerce-cart .cart_totals .wc-proceed-to-checkout::after {
				border: 0 !important;
				box-shadow: none !important;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form {
					padding-inline: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form tr.cart_item,
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form .cart_item {
					box-shadow: 0 10px 24px rgba(23, 63, 50, .055) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
