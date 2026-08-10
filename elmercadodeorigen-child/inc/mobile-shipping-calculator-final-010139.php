<?php
/**
 * Carrito móvil: calculadora de envío final 0.10.141 QA.
 *
 * Reestructura la fila móvil para que ENVÍO y el disparador compartan una línea
 * a ancho completo, mientras el formulario desplegado ocupa la fila siguiente
 * completa. `user-visual` lo fuerza abierto solo para comprobación visual.
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
		<style id="elmercado-mobile-shipping-calculator-final-010141">
			@media (max-width: 767px) {
				/* La fila de envío deja de ser una tabla de dos medias columnas. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					margin: 0 !important;
					padding: 8px 0 10px !important;
					border: 0 !important;
				}

				/* El encabezado real se sustituye por la etiqueta móvil dentro de la celda completa. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > th {
					display: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td {
					display: grid !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					grid-template-columns: auto minmax(0,1fr) !important;
					align-items: start !important;
					column-gap: 14px !important;
					row-gap: 9px !important;
					margin: 0 !important;
					padding: 0 !important;
					border: 0 !important;
					text-align: left !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td::before {
					display: block !important;
					content: "ENVÍO" !important;
					grid-column: 1 !important;
					grid-row: 1 !important;
					align-self: center !important;
					margin: 0 !important;
					padding: 3px 0 !important;
					color: rgba(255,253,248,.68) !important;
					font-size: 10px !important;
					font-weight: 850 !important;
					letter-spacing: .055em !important;
					line-height: 1.35 !important;
					text-transform: uppercase !important;
			}

				/* El wrapper no crea otra columna: sus hijos participan en la rejilla de la celda. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .woocommerce-shipping-calculator {
					display: contents !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-button {
					display: inline-flex !important;
					grid-column: 2 !important;
					grid-row: 1 !important;
					width: auto !important;
					max-width: 100% !important;
					align-items: center !important;
					justify-content: flex-end !important;
					justify-self: end !important;
					margin: 0 !important;
					padding: 3px 0 !important;
					color: #f1d59c !important;
					font-size: 13px !important;
					font-weight: 850 !important;
					line-height: 1.35 !important;
					text-align: right !important;
					text-decoration: underline !important;
					text-decoration-thickness: 1px !important;
					text-underline-offset: 3px !important;
				}

				/* Métodos/destino, si ya existen, también usan toda la anchura disponible. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals tr.woocommerce-shipping-totals.shipping > td > :is(ul#shipping_method,ul.woocommerce-shipping-methods,.woocommerce-shipping-destination) {
					grid-column: 1 / -1 !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
				}

				/* El panel abierto cruza ambas columnas y se convierte en una única superficie. */
				html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form {
					float: none !important;
					clear: both !important;
					grid-column: 1 / -1 !important;
					grid-row: auto !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 3px 0 0 !important;
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
		<?php if ( isset( $_GET['user-visual'] ) ) : ?>
			<style id="elmercado-mobile-shipping-calculator-qa-010141">
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
