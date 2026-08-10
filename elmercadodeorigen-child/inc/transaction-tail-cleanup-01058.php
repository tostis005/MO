<?php
/**
 * Cierre transaccional seguro 0.10.125.
 *
 * Conserva compacto el bloque de garantías del carrito y oculta contenido
 * promocional/reseñas que aparezca después del flujo transaccional. No crea
 * estados de checkout ni oculta el resumen o los métodos de pago.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		?>
		<style id="elmercado-transaction-tail-cleanup-010125">
			body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) .emo-post-transaction-hidden {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
				display: flex !important;
				flex-direction: column !important;
				flex-wrap: nowrap !important;
				align-items: stretch !important;
				align-content: flex-start !important;
				justify-content: flex-start !important;
				gap: 7px !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 10px 0 0 !important;
				padding: 10px 0 0 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance > span {
				position: static !important;
				display: flex !important;
				flex: 0 0 auto !important;
				align-self: auto !important;
				align-items: flex-start !important;
				justify-content: flex-start !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
				line-height: 1.35 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		?>
		<script id="elmercado-transaction-tail-cleanup-js-010125">
		(() => {
			'use strict';

			const protectedSelector = '.woocommerce-cart-form,.cart-collaterals,#customer_details,#order_review,#payment,form.checkout,.woocommerce-checkout-review-order,.emo-checkout-summary-column';
			const root = document.querySelector('#primary,.site-main') || document.body;

			const hideTail = () => {
				let boundary = document.body.classList.contains('woocommerce-checkout')
					? (document.querySelector('.emo-checkout-summary-column') || document.querySelector('#order_review'))
					: (document.querySelector('.cart-collaterals') || document.querySelector('.woocommerce-cart-form'));

				while (boundary && boundary !== root && root.contains(boundary)) {
					let sibling = boundary.nextElementSibling;
					while (sibling) {
						const next = sibling.nextElementSibling;
						if (!sibling.matches('script,style') && !sibling.matches(protectedSelector) && !sibling.querySelector(protectedSelector)) {
							sibling.classList.add('emo-post-transaction-hidden');
						}
						sibling = next;
					}
					boundary = boundary.parentElement;
				}
			};

			const start = () => {
				hideTail();
				[180, 700, 1800].forEach((delay) => window.setTimeout(hideTail, delay));
				if (window.jQuery) {
					window.jQuery(document.body).on('updated_checkout updated_wc_div', hideTail);
				}
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', start, { once: true });
			} else {
				start();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
