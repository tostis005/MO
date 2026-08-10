<?php
/**
 * Carrito móvil: calculadora de envío final 0.10.139.
 *
 * Alinea el disparador con los importes del resumen y hace que el formulario
 * desplegable use toda la anchura disponible, sin floats ni media columna.
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
		<style id="elmercado-mobile-shipping-calculator-final-010139">
			@media (max-width: 767px) {
				/* El enlace forma parte del lado de importes del resumen. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .woocommerce-shipping-calculator {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					margin: 9px 0 0 !important;
					padding: 0 !important;
					text-align: right !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-button {
					display: inline-flex !important;
					width: auto !important;
					max-width: 100% !important;
					align-items: center !important;
					justify-content: flex-end !important;
					margin: 0 0 0 auto !important;
					padding: 3px 0 !important;
					text-align: right !important;
					line-height: 1.35 !important;
				}

				/* Al abrirlo, el formulario es una única superficie a ancho completo. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form {
					float: none !important;
					clear: both !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 11px 0 2px !important;
					padding: 12px !important;
					border: 0 !important;
					border-radius: 12px !important;
					background: rgba(255,255,255,.045) !important;
					box-shadow: none !important;
					text-align: left !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(
					.form-row,
					.form-row-wide,
					.form-row-first,
					.form-row-last
				) {
					float: none !important;
					clear: both !important;
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 0 9px !important;
					padding: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(
					input.input-text,
					select,
					.select2-container,
					.select2-selection
				) {
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
