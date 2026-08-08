<?php
/**
 * Segunda pasada de coherencia: garantías del carrito y estado de checkout.
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
		<style id="elmercado-visual-coherence-01064">
			/* Evita que estilos heredados conviertan las tres garantías en filas altas. */
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) !important;
				grid-template-rows: repeat(3, auto) !important;
				grid-auto-rows: auto !important;
				align-content: start !important;
				justify-content: stretch !important;
				row-gap: 7px !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 10px 0 0 !important;
				padding: 10px 0 0 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance > span {
				display: flex !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				align-self: start !important;
				justify-self: stretch !important;
				align-items: flex-start !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1.35 !important;
			}

			/* Durante cualquier estado pendiente el usuario siempre recibe una explicación. */
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > .emo-checkout-status-card {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review:is(.emo-order-review-loading,.emo-order-review-pending)) > :is(#order_review_heading,#order_review) {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review:is(.emo-order-review-loading,.emo-order-review-pending)) > .emo-checkout-status-card {
				display: flex !important;
				height: auto !important;
				min-height: 126px !important;
				max-height: none !important;
				flex-direction: column !important;
				justify-content: center !important;
				gap: 8px !important;
				padding: 22px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 18px !important;
				background: #173f32 !important;
				box-shadow: 0 14px 38px rgba(13,33,27,.10) !important;
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card strong {
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card span {
				color: rgba(255,253,248,.82) !important;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review:is(.emo-order-review-loading,.emo-order-review-pending)) > .emo-checkout-status-card {
					min-height: 116px !important;
					padding: 18px !important;
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
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		?>
		<script id="elmercado-checkout-status-ready-01064">
		(() => {
			'use strict';

			const ensureCard = () => {
				const review = document.querySelector('#order_review');
				const column = review?.closest('.emo-checkout-summary-column');
				if (!review || !column) return;

				let card = column.querySelector(':scope > .emo-checkout-status-card');
				if (!card) {
					card = document.createElement('div');
					card.className = 'emo-checkout-status-card';
					card.setAttribute('role', 'status');
					card.setAttribute('aria-live', 'polite');
					card.innerHTML = '<strong>Preparando tu resumen</strong><span>Estamos actualizando el pedido y las opciones de pago con tus datos.</span>';
					column.prepend(card);
				}
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', ensureCard, { once: true });
			} else {
				ensureCard();
			}

			[80, 220, 500, 1000, 1800].forEach((delay) => window.setTimeout(ensureCard, delay));
			if (window.jQuery) {
				window.jQuery(document.body).on('update_checkout updated_checkout checkout_error', ensureCard);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
