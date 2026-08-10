<?php
/**
 * Columna de resumen de checkout estable durante actualizaciones AJAX.
 *
 * Mantiene el resumen en su columna sin ocultar métodos de pago ni sustituir
 * el contenido mientras WooCommerce recalcula envío, impuestos o pasarelas.
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
				min-width: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > :is(#order_review_heading,#order_review) {
				grid-column: auto !important;
				grid-row: auto !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
			}

			/* El estado AJAX acompaña al contenido; nunca lo sustituye. */
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-loading-note {
				display: none;
				margin: 0 0 14px !important;
				padding: 12px 14px;
				border: 1px solid rgba(255,255,255,.14);
				border-radius: 12px;
				background: rgba(255,255,255,.055);
				color: #fffdf8 !important;
				font-size: 12.5px;
				font-weight: 650;
				line-height: 1.45;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-updating > .emo-checkout-loading-note {
				display: block;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-loading-note::before {
				display: inline-block;
				width: 7px;
				height: 7px;
				margin-right: 8px;
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
				const review = form?.querySelector('#order_review');
				const heading = form?.querySelector('#order_review_heading');
				if (!form || !review) return null;

				let column = form.querySelector(':scope > .emo-checkout-summary-column');
				if (!column) {
					column = document.createElement('div');
					column.className = 'emo-checkout-summary-column';
					(heading || review).before(column);
				}
				if (heading && heading.parentElement !== column) column.append(heading);
				if (review.parentElement !== column) column.append(review);

				let note = review.querySelector(':scope > .emo-checkout-loading-note');
				if (!note) {
					note = document.createElement('p');
					note.className = 'emo-checkout-loading-note';
					note.setAttribute('role', 'status');
					note.setAttribute('aria-live', 'polite');
					note.textContent = 'Actualizando el resumen y las opciones disponibles…';
					review.prepend(note);
				}

				/* Clases antiguas podían ocultar por completo el resumen. */
				review.classList.remove('emo-order-review-loading', 'emo-order-review-pending');
				return review;
			};

			const markUpdating = () => {
				const review = setup();
				if (review) review.classList.add('emo-order-review-updating');
			};

			const markReady = () => {
				const review = setup();
				if (review) review.classList.remove('emo-order-review-updating');
			};

			document.addEventListener('DOMContentLoaded', () => {
				const review = setup();
				if (review) {
					const content = [...review.children]
						.filter((node) => !node.matches('.blockUI,.emo-checkout-loading-note'))
						.map((node) => node.innerText || '')
						.join(' ')
						.replace(/\s+/g, ' ')
						.trim();
					if (content.length < 18) review.classList.add('emo-order-review-updating');
				}

				if (window.jQuery) {
					jQuery(document.body)
						.on('update_checkout', markUpdating)
						.on('updated_checkout checkout_error', markReady);
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
