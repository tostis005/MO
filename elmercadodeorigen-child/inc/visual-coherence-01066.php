<?php
/**
 * Cierre de coherencia visual: carrito y checkout con estilos runtime estables.
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
		<script id="elmercado-visual-coherence-runtime-01066">
		(() => {
			'use strict';

			const important = (node, property, value) => {
				if (node) node.style.setProperty(property, value, 'important');
			};

			const compactCartAssurance = () => {
				const box = document.querySelector('.cart_totals .emo-cart-assurance');
				if (!box) return;

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
						padding: '0'
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

				important(card, 'height', 'auto');
				important(card, 'min-height', '116px');
				important(card, 'max-height', 'none');
				important(card, 'flex-direction', 'column');
				important(card, 'justify-content', 'center');
				important(card, 'gap', '8px');
				important(card, 'padding', '20px');
				important(card, 'border', '1px solid rgba(255,255,255,.12)');
				important(card, 'border-radius', '18px');
				important(card, 'background', '#173f32');
				important(card, 'color', '#fffdf8');

				return { review, column, card };
			};

			const checkoutOverlayVisible = (review) => [...review.querySelectorAll('.blockUI.blockOverlay')].some((node) => {
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
			});

			const syncCheckoutSummary = () => {
				const state = ensureCheckoutStatusCard();
				if (!state) return;

				const { review, column, card } = state;
				const heading = column.querySelector(':scope > #order_review_heading');
				const sourceText = [...review.children]
					.filter((node) => !node.matches('.blockUI,.emo-checkout-loading-note'))
					.map((node) => node.textContent || '')
					.join(' ')
					.replace(/\s+/g, ' ')
					.replace(/^[*·•\s]+$/, '')
					.trim();
				const hasProduct = !!review.querySelector('tr.cart_item');
				const overlay = checkoutOverlayVisible(review);
				const ready = hasProduct && !overlay && sourceText.length >= 18;

				column.classList.toggle('emo-checkout-summary-ready', ready);
				important(card, 'display', ready ? 'none' : 'flex');
				important(review, 'display', ready ? 'block' : 'none');
				if (heading) important(heading, 'display', ready ? 'block' : 'none');
			};

			const run = () => {
				compactCartAssurance();
				syncCheckoutSummary();
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', run, { once: true });
			} else {
				run();
			}

			[80, 220, 500, 900, 1600, 2800, 4500].forEach((delay) => window.setTimeout(run, delay));

			if (window.jQuery) {
				window.jQuery(document.body).on(
					'updated_wc_div updated_cart_totals update_checkout updated_checkout checkout_error',
					() => window.requestAnimationFrame(run)
				);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
