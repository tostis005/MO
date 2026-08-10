<?php
/**
 * Calculadora de envío móvil final 0.10.144.
 *
 * La fila de envío deja de heredar la composición de dos medias celdas de la
 * tabla responsive de WooCommerce. El disparador queda alineado a la derecha y
 * el formulario abierto usa todo el ancho disponible con controles apilados.
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
		<style id="elmercado-mobile-shipping-calculator-final-010144">
			@media (max-width: 767px) {
				/* Se identifica la fila por su contenido real para cubrir las variantes de WooCommerce. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button),
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
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
					padding: 8px 0 10px !important;
					border: 0 !important;
					text-align: right !important;
				}

				/* Etiqueta a la izquierda sin reservar media celda. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) > td::before,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) > td::before {
					display: block !important;
					content: "ENVÍO" !important;
					float: none !important;
					position: absolute !important;
					top: 11px !important;
					left: 0 !important;
					right: auto !important;
					box-sizing: border-box !important;
					width: auto !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					color: rgba(255,253,248,.68) !important;
					font-size: 10px !important;
					font-weight: 850 !important;
					letter-spacing: .055em !important;
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
					max-width: calc(100% - 74px) !important;
					min-height: 18px !important;
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

				/* Destino y métodos existentes también ocupan toda la fila. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) :is(ul#shipping_method,ul.woocommerce-shipping-methods,.woocommerce-shipping-destination),
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) :is(ul#shipping_method,ul.woocommerce-shipping-methods,.woocommerce-shipping-destination) {
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					text-align: left !important;
				}

				/* Panel abierto: una sola superficie amplia, sin aspecto de tabla anidada. */
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
					margin: 12px 0 0 !important;
					padding: 13px !important;
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
					margin: 0 0 10px !important;
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
					margin: 1px 0 0 !important;
					padding: 10px 14px !important;
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
