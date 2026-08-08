<?php
/**
 * Estado de carga legible para la columna de resumen del checkout.
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
		<style id="elmercado-checkout-status-card-01061">
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card {
				display: none;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column.is-emo-loading > :is(#order_review_heading,#order_review) {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column.is-emo-loading > .emo-checkout-status-card {
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
				html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column.is-emo-loading > .emo-checkout-status-card {
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
		<script id="elmercado-checkout-status-card-js-01061">
		(() => {
			'use strict';

			const visible = (node) => {
				if (!node) return false;
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && rect.width > 0 && rect.height > 0;
			};

			const ensureStatusCard = (column) => {
				let card = column.querySelector(':scope > .emo-checkout-status-card');
				if (card) return card;
				card = document.createElement('div');
				card.className = 'emo-checkout-status-card';
				card.setAttribute('role', 'status');
				card.setAttribute('aria-live', 'polite');
				card.innerHTML = '<strong>Preparando tu resumen</strong><span>Estamos actualizando el pedido y las opciones de pago con tus datos.</span>';
				column.prepend(card);
				return card;
			};

			const sync = () => {
				const review = document.querySelector('#order_review');
				const column = review?.closest('.emo-checkout-summary-column');
				if (!review || !column) return;
				ensureStatusCard(column);

				const overlay = [...document.querySelectorAll('.blockUI.blockOverlay')].some((node) => visible(node) && (review.contains(node) || column.contains(node)));
				const table = review.querySelector('.shop_table');
				const payment = review.querySelector('#payment');
				const contentText = `${table?.innerText || ''} ${payment?.innerText || ''}`.replace(/\s+/g, ' ').trim();
				const hasActualContent = contentText.length > 45 && (visible(table) || visible(payment));
				const loading = overlay || !hasActualContent;

				column.classList.toggle('is-emo-loading', loading);
				if (loading) review.classList.add('emo-order-review-loading');
				else review.classList.remove('emo-order-review-loading');
			};

			document.addEventListener('DOMContentLoaded', () => {
				sync();
				[100, 260, 520, 900, 1500, 2500, 4200].forEach((delay) => setTimeout(sync, delay));
				const form = document.querySelector('form.checkout');
				if (form) {
					new MutationObserver(() => requestAnimationFrame(sync)).observe(form, { childList: true, subtree: true, attributes: true, attributeFilter: ['class','style'] });
				}
				if (window.jQuery) {
					jQuery(document.body).on('update_checkout updated_checkout checkout_error', () => requestAnimationFrame(sync));
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
