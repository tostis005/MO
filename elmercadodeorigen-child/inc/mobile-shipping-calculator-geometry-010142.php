<?php
/**
 * Geometría final de la calculadora de envío móvil 0.10.142 QA.
 *
 * Neutraliza el 50% heredado del pseudo-label de WooCommerce y hace que el
 * wrapper completo de la calculadora abarque ambas columnas de la fila.
 * `user-visual` fuerza el panel abierto únicamente para validación.
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
		<style id="elmercado-mobile-shipping-calculator-geometry-010142">
			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td::before {
					float: none !important;
					position: relative !important;
					z-index: 2 !important;
					box-sizing: border-box !important;
					width: auto !important;
					max-width: none !important;
					min-width: 0 !important;
					justify-self: start !important;
					text-align: left !important;
					pointer-events: none !important;
				}

				/* El wrapper completo ocupa la fila; el enlace se coloca a la derecha dentro de él. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td > .woocommerce-shipping-calculator,
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td > form.woocommerce-shipping-calculator {
					display: block !important;
					float: none !important;
					clear: both !important;
					position: relative !important;
					z-index: 1 !important;
					grid-column: 1 / -1 !important;
					grid-row: 1 !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					text-align: right !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping .shipping-calculator-button {
					display: inline-flex !important;
					float: none !important;
					width: auto !important;
					max-width: calc(100% - 72px) !important;
					margin: 0 0 0 auto !important;
					justify-content: flex-end !important;
					text-align: right !important;
				}

				/* El panel ya no puede heredar la anchura de la antigua media celda. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping .shipping-calculator-form {
					float: none !important;
					clear: both !important;
					position: relative !important;
					left: auto !important;
					right: auto !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 10px 0 0 !important;
					text-align: left !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping .shipping-calculator-form :is(.form-row,.form-row-wide,.form-row-first,.form-row-last,input.input-text,select,.select2-container,.select2-selection,.button) {
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
				}
			}
		</style>
		<?php if ( isset( $_GET['user-visual'] ) ) : ?>
			<style id="elmercado-mobile-shipping-calculator-geometry-qa-010142">
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
