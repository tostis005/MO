<?php
/**
 * Columna de resumen de checkout estable durante actualizaciones AJAX.
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
		<style id="elmercado-checkout-summary-column-01059">
			/* La columna de datos y la de resumen deben medir por su propio contenido.
			 * Evitamos que el alto del formulario izquierdo se transfiera al panel verde. */
			html body.elmercado-child-theme.woocommerce-checkout form.checkout {
				display: grid !important;
				grid-template-columns: minmax(0, 1.2fr) minmax(360px, .8fr) !important;
				grid-template-rows: auto !important;
				align-items: start !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout > #customer_details {
				grid-column: 1 !important;
				grid-row: 1 !important;
				align-self: start !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column {
				grid-column: 2 !important;
				grid-row: 1 !important;
				align-self: start !important;
				min-width: 0;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > :is(#order_review_heading,#order_review) {
				grid-column: auto !important;
				grid-row: auto !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
			}

			/* Durante updated_checkout no enseñamos una gran superficie vacía. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading {
				position: relative !important;
				display: block !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				padding: 14px 18px 18px !important;
				overflow: visible !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading > :not(.emo-checkout-loading-note):not(.blockUI) {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading > .blockUI.blockOverlay {
				display: block !important;
				background: transparent !important;
				opacity: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-loading-note {
				display: none;
				margin: 0 !important;
				padding: 16px;
				border: 1px solid rgba(255,255,255,.14);
				border-radius: 14px;
				background: rgba(255,255,255,.055);
				color: #fffdf8 !important;
				font-size: 13px;
				font-weight: 650;
				line-height: 1.55;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading .emo-checkout-loading-note {
				display: block;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-loading-note::before {
				display: inline-block;
				width: 8px;
				height: 8px;
				margin-right: 9px;
				border-radius: 50%;
				background: #f1d59c;
				content: "";
				vertical-align: 1px;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.woocommerce-checkout form.checkout {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 18px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout > #customer_details,
				html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column {
					grid-column: 1 !important;
					grid-row: auto !important;
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
		<script id="elmercado-checkout-summary-column-js-01059">
		(() => {
			'use strict';

			const setup = () => {
				const form = document.querySelector('form.checkout');
				const heading = form?.querySelector(':scope > #order_review_heading');
				const review = form?.querySelector(':scope > #order_review');
				if (!form || !review) return null;

				let column = form.querySelector(':scope > .emo-checkout-summary-column');
				if (!column) {
					column = document.createElement('div');
					column.className = 'emo-checkout-summary-column';
					(heading || review).before(column);
					if (heading) column.append(heading);
					column.append(review);
				}

				let note = review.querySelector(':scope > .emo-checkout-loading-note');
				if (!note) {
					note = document.createElement('p');
					note.className = 'emo-checkout-loading-note';
					note.setAttribute('role', 'status');
					note.setAttribute('aria-live', 'polite');
					note.textContent = 'Estamos actualizando el resumen y las opciones de pago con tus datos.';
					review.append(note);
				}

				return review;
			};

			const overlayVisible = (review) => [...review.querySelectorAll('.blockUI.blockOverlay')].some((node) => {
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
			});

			const sync = () => {
				const review = setup();
				if (!review) return;
				const text = [...review.children]
					.filter((node) => !node.matches('.blockUI,.emo-checkout-loading-note'))
					.map((node) => node.innerText || '')
					.join(' ')
					.replace(/\s+/g, ' ')
					.trim();
				const loading = overlayVisible(review) || text.length < 18;
				review.classList.toggle('emo-order-review-loading', loading);
			};

			document.addEventListener('DOMContentLoaded', () => {
				sync();
				[80, 220, 500, 900, 1600, 2800, 4500].forEach((delay) => setTimeout(sync, delay));
				const form = document.querySelector('form.checkout');
				if (form) {
					new MutationObserver(() => requestAnimationFrame(sync)).observe(form, { childList: true, subtree: true, attributes: true, attributeFilter: ['style','class'] });
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
