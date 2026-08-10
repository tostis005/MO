<?php
/**
 * Calculadora de envío de escritorio final 0.10.145.
 *
 * En carrito de escritorio elimina la media celda heredada de WooCommerce:
 * ENVÍO queda a la izquierda, el disparador al extremo derecho y el formulario
 * abierto ocupa todo el ancho útil de la tarjeta de totales.
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
		<style id="elmercado-desktop-shipping-calculator-final-010145">
			@media (min-width: 768px) {
				/* La calculadora no debe vivir dentro de la media columna derecha de la tabla. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button),
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 2px 0 8px !important;
					border: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) > th,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) > th {
					display: none !important;
					width: 0 !important;
					min-width: 0 !important;
					max-width: 0 !important;
					padding: 0 !important;
					border: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) > td,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) > td {
					display: block !important;
					float: none !important;
					clear: both !important;
					position: relative !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 9px 0 10px !important;
					border: 0 !important;
					text-align: right !important;
				}

				/* Etiqueta visual a la izquierda, sin reservar una segunda celda. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) > td::before,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) > td::before {
					display: block !important;
					content: "ENVÍO" !important;
					float: none !important;
					position: absolute !important;
					top: 12px !important;
					left: 0 !important;
					right: auto !important;
					width: auto !important;
					margin: 0 !important;
					padding: 0 !important;
					color: rgba(255,253,248,.68) !important;
					font-size: 11px !important;
					font-weight: 850 !important;
					letter-spacing: .045em !important;
					line-height: 1.35 !important;
					text-align: left !important;
					text-transform: uppercase !important;
					pointer-events: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) .woocommerce-shipping-calculator,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) .woocommerce-shipping-calculator {
					display: block !important;
					float: none !important;
					clear: both !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					text-align: right !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) .shipping-calculator-button {
					display: inline-flex !important;
					float: none !important;
					width: auto !important;
					max-width: calc(100% - 86px) !important;
					align-items: center !important;
					justify-content: flex-end !important;
					margin: 0 0 0 auto !important;
					padding: 0 !important;
					color: #f1d59c !important;
					font-size: 13px !important;
					font-weight: 850 !important;
					line-height: 1.35 !important;
					text-align: right !important;
					text-decoration: underline !important;
					text-decoration-thickness: 1px !important;
					text-underline-offset: 3px !important;
				}

				/* Si ya hay destino o métodos, también usan todo el ancho disponible. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) :is(ul#shipping_method,ul.woocommerce-shipping-methods,.woocommerce-shipping-destination),
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) :is(ul#shipping_method,ul.woocommerce-shipping-methods,.woocommerce-shipping-destination) {
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					text-align: left !important;
				}

				/* Panel abierto: ancho completo, una única columna y sin apariencia de tabla. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) .shipping-calculator-form,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) .shipping-calculator-form {
					float: none !important;
					clear: both !important;
					position: relative !important;
					left: auto !important;
					right: auto !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 14px 0 0 !important;
					padding: 16px !important;
					border: 0 !important;
					border-radius: 12px !important;
					background: rgba(255,255,255,.045) !important;
					box-shadow: none !important;
					text-align: left !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(.form-row,.form-row-wide,.form-row-first,.form-row-last) {
					float: none !important;
					clear: both !important;
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 0 11px !important;
					padding: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(input.input-text,select,.select2-container,.select2-selection) {
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(input.input-text,select),
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection {
					min-height: 46px !important;
					border: 1px solid rgba(255,255,255,.14) !important;
					border-radius: 10px !important;
					background: rgba(255,255,255,.07) !important;
					color: #fffdf8 !important;
					box-shadow: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form input.input-text {
					padding: 11px 12px !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection {
					display: flex !important;
					align-items: center !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection__rendered {
					box-sizing: border-box !important;
					width: 100% !important;
					padding: 0 38px 0 12px !important;
					color: #fffdf8 !important;
					line-height: 44px !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .button {
					display: flex !important;
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-height: 46px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 2px 0 0 !important;
					padding: 10px 16px !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: rgba(255,255,255,.11) !important;
					color: #fffdf8 !important;
					box-shadow: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .button:hover,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .button:focus {
					background: rgba(255,255,255,.16) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
