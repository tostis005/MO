<?php
/**
 * Envío y CTA transaccional final 0.10.132.
 *
 * Corrige la geometría del único método de envío (input oculto) y de las
 * alternativas con radio, mantiene el transporte legible en carrito/checkout
 * y usa exactamente el terracota del CTA principal de la home.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		?>
		<style id="elmercado-cart-checkout-shipping-final-010132">
			/* Método de envío: una tarifa única no debe reservar una columna para un radio inexistente. */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method,
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods {
				display: grid !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				gap: 8px !important;
				margin: 0 !important;
				padding: 0 !important;
				list-style: none !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li,
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li {
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				align-items: flex-start !important;
				justify-content: space-between !important;
				gap: 10px !important;
				margin: 0 !important;
				padding: 11px 12px !important;
				border: 1px solid rgba(255,255,255,.13) !important;
				border-radius: 12px !important;
				background: rgba(255,255,255,.055) !important;
				color: #fffdf8 !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li:has(> input[type="radio"]),
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li:has(> input[type="radio"]) {
				display: grid !important;
				grid-template-columns: 20px minmax(0,1fr) !important;
				align-items: start !important;
				column-gap: 9px !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li > input[type="hidden"],
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li > input[type="hidden"] {
				display: none !important;
		}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li > input[type="radio"],
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li > input[type="radio"] {
				grid-column: 1 !important;
				width: 16px !important;
				height: 16px !important;
				margin: 2px 0 0 !important;
				accent-color: #f1d59c !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li > label,
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li > label {
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				flex: 1 1 auto !important;
				flex-wrap: wrap !important;
				align-items: baseline !important;
				justify-content: space-between !important;
				gap: 4px 12px !important;
				margin: 0 !important;
				color: #fffdf8 !important;
				font-size: 14px !important;
				font-weight: 750 !important;
				line-height: 1.35 !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li:has(> input[type="radio"]) > label,
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li:has(> input[type="radio"]) > label {
				grid-column: 2 !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul#shipping_method > li > label .amount,
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) ul.woocommerce-shipping-methods > li > label .amount {
				margin-left: auto !important;
				color: #f1d59c !important;
				font-weight: 900 !important;
				white-space: nowrap !important;
			}

			/* Carrito: el paquete del productor funciona como contexto y la tarifa como dato principal. */
			html body.elmercado-child-theme.woocommerce-cart .cart_totals tr.woocommerce-shipping-totals.shipping > th {
				box-sizing: border-box !important;
				width: 100% !important;
				padding: 0 0 9px !important;
				color: rgba(255,253,248,.74) !important;
				font-size: 11px !important;
				font-weight: 800 !important;
				letter-spacing: .025em !important;
				line-height: 1.5 !important;
				text-align: left !important;
				text-transform: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals tr.woocommerce-shipping-totals.shipping > td {
				box-sizing: border-box !important;
				width: 100% !important;
				padding: 0 !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals .woocommerce-shipping-destination {
				width: 100% !important;
				margin: 10px 0 0 !important;
				color: rgba(255,253,248,.82) !important;
				font-size: 13px !important;
				line-height: 1.45 !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals .woocommerce-shipping-destination strong {
				color: #fffdf8 !important;
				font-weight: 850 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-button {
				margin-top: 6px !important;
				color: #f1d59c !important;
				font-size: 13px !important;
				font-weight: 850 !important;
				line-height: 1.35 !important;
				text-decoration: underline !important;
				text-decoration-thickness: 1px !important;
				text-underline-offset: 3px !important;
			}

			/* Checkout: el envío ocupa una fila completa en móvil y nunca pierde la etiqueta de la tarifa. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping > th {
				color: rgba(255,253,248,.74) !important;
				font-size: 11px !important;
				font-weight: 800 !important;
				letter-spacing: .02em !important;
				line-height: 1.5 !important;
				text-align: left !important;
				text-transform: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping > td {
				color: #fffdf8 !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping > td::before {
				display: none !important;
				content: none !important;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping {
					display: grid !important;
					grid-template-columns: minmax(0,1fr) !important;
					gap: 8px !important;
					box-sizing: border-box !important;
					width: 100% !important;
					padding: 10px 0 12px !important;
					border-bottom: 1px solid rgba(255,255,255,.11) !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping > :is(th,td) {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: none !important;
					margin: 0 !important;
					padding: 0 !important;
					border: 0 !important;
				}
			}

			/* CTA: mismo terracota exacto que “Descubrir productos” del hero de la home. */
			html body.elmercado-child-theme.woocommerce-cart .wc-proceed-to-checkout .checkout-button,
			html body.elmercado-child-theme.woocommerce-cart .wc-proceed-to-checkout a.checkout-button,
			html body.elmercado-child-theme.woocommerce-checkout #place_order {
				border-color: #c96d45 !important;
				background: #c96d45 !important;
				background-color: #c96d45 !important;
				color: #fff !important;
				box-shadow: 0 9px 22px rgba(201,109,69,.20) !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .wc-proceed-to-checkout .checkout-button:hover,
			html body.elmercado-child-theme.woocommerce-cart .wc-proceed-to-checkout .checkout-button:focus,
			html body.elmercado-child-theme.woocommerce-checkout #place_order:hover,
			html body.elmercado-child-theme.woocommerce-checkout #place_order:focus {
				border-color: #e07c50 !important;
				background: #e07c50 !important;
				background-color: #e07c50 !important;
				color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .wc-proceed-to-checkout .checkout-button:focus-visible,
			html body.elmercado-child-theme.woocommerce-checkout #place_order:focus-visible {
				outline: 3px solid rgba(241,213,156,.68) !important;
				outline-offset: 3px !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
