<?php
/**
 * Tarjeta de estado del checkout sin observadores adicionales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		?>
		<style id="elmercado-checkout-status-css-01062">
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > .emo-checkout-status-card {
				display: none;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review.emo-order-review-loading) > :is(#order_review_heading,#order_review) {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review.emo-order-review-loading) > .emo-checkout-status-card {
				display: flex;
				min-height: 136px;
				flex-direction: column;
				justify-content: center;
				gap: 8px;
				padding: 24px;
				border: 1px solid rgba(255,255,255,.12);
				border-radius: 20px;
				background: #173f32;
				color: #fffdf8;
				box-shadow: 0 14px 38px rgba(13,33,27,.10);
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card strong {
				color: #fffdf8 !important;
				font-family: Georgia, "Times New Roman", serif;
				font-size: clamp(20px, 2vw, 25px);
				font-weight: 600;
				letter-spacing: -.025em;
				line-height: 1.1;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card span {
				max-width: 34ch;
				color: rgba(255,253,248,.82) !important;
				font-size: 13px;
				font-weight: 560;
				line-height: 1.55;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card span::before {
				display: inline-block;
				width: 7px;
				height: 7px;
				margin-right: 8px;
				border-radius: 50%;
				background: #f1d59c;
				content: "";
				vertical-align: 1px;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review.emo-order-review-loading) > .emo-checkout-status-card {
					min-height: 126px;
					padding: 20px;
					border-radius: 18px;
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
		<script id="elmercado-checkout-status-css-js-01062">
		(() => {
			'use strict';
			document.addEventListener('DOMContentLoaded', () => {
				const review = document.querySelector('#order_review');
				const column = review?.closest('.emo-checkout-summary-column');
				if (!review || !column || column.querySelector(':scope > .emo-checkout-status-card')) return;

				const card = document.createElement('div');
				card.className = 'emo-checkout-status-card';
				card.setAttribute('role', 'status');
				card.setAttribute('aria-live', 'polite');
				card.innerHTML = '<strong>Preparando tu resumen</strong><span>Estamos actualizando el pedido y las opciones de pago con tus datos.</span>';
				column.prepend(card);
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
