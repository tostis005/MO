<?php
/**
 * Fila real de calculadora de envío móvil 0.10.143 QA.
 *
 * Selecciona por contenido real (:has) en lugar de depender de clases de fila
 * variables de WooCommerce. Convierte la fila en bloque de anchura completa y
 * posiciona ENVÍO a la izquierda mientras el disparador queda a la derecha.
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
		<style id="elmercado-mobile-shipping-calculator-row-final-010143">
			@media (max-width: 767px) {
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
					text-align: right !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-button) .shipping-calculator-form,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) .shipping-calculator-form {
					float: none !important;
					clear: both !important;
					position: relative !important;
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

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr:has(.shipping-calculator-form) .shipping-calculator-form :is(.form-row,.form-row-wide,.form-row-first,.form-row-last,input.input-text,select,.select2-container,.select2-selection,.button) {
					float: none !important;
					clear: both !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
				}
			}
		</style>
		<?php if ( isset( $_GET['user-visual'] ) ) : ?>
			<style id="elmercado-mobile-shipping-calculator-row-qa-010143">
				@media (max-width: 767px) {
					html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form {
						display: block !important;
					}
				}
			</style>
		<?php endif; ?>
		<?php
	},
	PHP_INT_MAX
);
