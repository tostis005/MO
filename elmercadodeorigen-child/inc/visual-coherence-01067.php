<?php
/**
 * Cierre definitivo de carrito/checkout basado en el marcado realmente renderizado.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$is_cart     = function_exists( 'is_cart' ) && is_cart();
		$is_checkout = function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page();

		if ( ! $is_cart && ! $is_checkout ) {
			return;
		}
		?>
		<script id="elmercado-visual-coherence-runtime-01067">
		(() => {
			'use strict';

			const important = (node, property, value) => {
				if (node) node.style.setProperty(property, value, 'important');
			};

			const compactCartAssurance = () => {
				const box = document.querySelector('.cart_totals .emo-cart-assurance');
				if (!box) return;
				[...box.childNodes].forEach((node) => {
					const keep = node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SPAN';
					if (!keep) node.remove();
				});
				const boxStyles = {
					display: 'flex',
					'flex-direction': 'column',
					'flex-wrap': 'nowrap',
					'align-items': 'stretch',
					'align-content': 'flex-start',
					'justify-content': 'flex-start',
					gap: '7px',
					width: '100%',
					height: 'auto',
					'min-height': '0',
					'max-height': 'none',
					margin: '10px 0 0',
					padding: '10px 0 0'
				};
				Object.entries(boxStyles).forEach(([property, value]) => important(box, property, value));
				box.querySelectorAll(':scope > span').forEach((row) => {
					const rowStyles = {
						position: 'static',
						display: 'flex',
						flex: '0 0 auto',
						'align-self': 'auto',
						'align-items': 'flex-start',
						'justify-content': 'flex-start',
						width: '100%',
						height: 'auto',
						'min-height': '0',
						'max-height': 'none',
						margin: '0',
						padding: '0',
						transform: 'none'
					};
					Object.entries(rowStyles).forEach(([property, value]) => important(row, property, value));
				});
			};

			const ensureCheckoutStatusCard = () => {
				const review = document.querySelector('#order_review');
				const column = review?.closest('.emo-checkout-summary-column');
				if (!review || !column) return null;
				let card = column.querySelector(':scope > .emo-checkout-status-card');
				if (!card) {
					card = document.createElement('div');
					card.className = 'emo-checkout-status-card';
					card.setAttribute('role', 'status');
					card.setAttribute('aria-live', 'polite');
					card.innerHTML = '<strong>Preparando tu resumen</strong><span>Estamos actualizando el pedido y las opciones de pago con tus datos.</span>';
					column.prepend(card);
				}
				const cardStyles = {
					height: 'auto',
					'min-height': '116px',
					'max-height': 'none',
					'flex-direction': 'column',
					'justify-content': 'center',
					gap: '8px',
					padding: '20px',
					border: '1px solid rgba(255,255,255,.12)',
					'border-radius': '18px',
					background: '#173f32',
					color: '#fffdf8'
				};
				Object.entries(cardStyles).forEach(([property, value]) => important(card, property, value));
				return { review, column, card };
			};

			const overlayVisible = (review) => [...review.querySelectorAll('.blockUI.blockOverlay')].some((node) => {
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && rect.width > 0 && rect.height > 0;
			});
			const cleanRenderedText = (review) => (review.innerText || '')
				.replace(/\s+/g, ' ')
				.replace(/^[*·•\s]+$/, '')
				.trim();
			const syncCheckoutSummary = () => {
				const state = ensureCheckoutStatusCard();
				if (!state) return;
				const { review, column, card } = state;
				const heading = column.querySelector(':scope > #order_review_heading');
				important(review, 'display', 'block');
				const renderedText = cleanRenderedText(review);
				const hasProduct = !!review.querySelector('tr.cart_item');
				const ready = hasProduct && !overlayVisible(review) && renderedText.length >= 18;
				column.classList.toggle('emo-checkout-summary-ready', ready);
				important(card, 'display', ready ? 'none' : 'flex');
				if (heading) important(heading, 'display', ready ? 'block' : 'none');
				if (ready) {
					important(review, 'opacity', '1');
					important(review, 'height', 'auto');
					important(review, 'max-height', 'none');
					important(review, 'overflow', 'visible');
					important(review, 'pointer-events', 'auto');
					important(review, 'margin', '');
					important(review, 'padding', '');
				} else {
					important(review, 'opacity', '0');
					important(review, 'height', '0');
					important(review, 'max-height', '0');
					important(review, 'overflow', 'hidden');
					important(review, 'pointer-events', 'none');
					important(review, 'margin', '0');
					important(review, 'padding', '0');
				}
			};

			const run = () => {
				compactCartAssurance();
				syncCheckoutSummary();
			};
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, { once: true });
			else run();
			[80, 220, 500, 900, 1600, 2800, 4500].forEach((delay) => window.setTimeout(run, delay));
			if (window.jQuery) {
				window.jQuery(document.body).on(
					'updated_wc_div updated_cart_totals update_checkout updated_checkout checkout_error',
					run
				);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
