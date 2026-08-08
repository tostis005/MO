<?php
/**
 * Limpieza estructural de cierre de compra y estado de carga del resumen.
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
		<style id="elmercado-transaction-tail-cleanup-01058">
			body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) .emo-post-transaction-hidden {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-pending {
				display: block !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: 300px !important;
				padding-bottom: 22px !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-pending::after {
				display: block;
				margin: 18px;
				padding: 18px;
				border: 1px solid rgba(255,255,255,.14);
				border-radius: 14px;
				background: rgba(255,255,255,.06);
				color: #fffdf8;
				content: "Completa tus datos para actualizar el resumen y mostrar las opciones de pago disponibles.";
				font-size: 13px;
				font-weight: 650;
				line-height: 1.55;
			}

			body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-pending #payment {
				display: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-pending {
					max-height: 260px !important;
				}
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
		<script id="elmercado-transaction-tail-cleanup-js-01058">
		(() => {
			'use strict';

			const protectedSelector = '.woocommerce-cart-form,.cart-collaterals,#customer_details,#order_review,#payment,form.checkout,.woocommerce-checkout-review-order';
			const root = document.querySelector('#primary,.site-main') || document.body;

			const hideTail = () => {
				let boundary = document.body.classList.contains('woocommerce-checkout')
					? document.querySelector('#order_review')
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

			const syncCheckoutState = () => {
				if (!document.body.classList.contains('woocommerce-checkout')) return;
				const review = document.querySelector('#order_review');
				if (!review) return;
				const summaryText = (review.querySelector('.shop_table')?.innerText || '').replace(/\s+/g, ' ').trim();
				const paymentOptions = review.querySelectorAll('#payment .payment_methods li').length;
				review.classList.toggle('emo-order-review-pending', summaryText.length < 12 && paymentOptions === 0);
			};

			const run = () => {
				hideTail();
				syncCheckoutState();
			};

			run();
			[150, 500, 1100, 2200, 4200].forEach((delay) => setTimeout(run, delay));
			if (window.jQuery) {
				jQuery(document.body).on('updated_checkout', run);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
