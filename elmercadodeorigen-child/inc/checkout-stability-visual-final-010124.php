<?php
/**
 * Estabilidad y acabado visual definitivo del checkout 0.10.124.
 *
 * El resumen permanece visible durante las actualizaciones AJAX de WooCommerce.
 * Se eliminan los estados heredados que colapsaban la columna y se unifica la
 * paleta de tablas, métodos de pago, cajas informativas y controles.
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
		<style id="elmercado-checkout-stability-visual-final-010124">
			/* Estructura: el resumen nunca sale del flujo ni se colapsa durante AJAX. */
			html body.elmercado-child-theme.woocommerce-checkout form.checkout {
				align-items: start !important;
				gap: clamp(18px, 2.5vw, 30px) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column {
				position: sticky !important;
				top: 110px !important;
				display: block !important;
				box-sizing: border-box !important;
				width: 100% !important;
				min-width: 0 !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				align-self: start !important;
				overflow: visible !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > .emo-checkout-status-card {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				overflow: hidden !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > #order_review_heading,
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > #order_review,
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review:is(.emo-order-review-loading,.emo-order-review-pending)) > :is(#order_review_heading,#order_review),
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column:not(:has(> #order_review tr.cart_item)) > :is(#order_review_heading,#order_review) {
				display: block !important;
				visibility: visible !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				opacity: 1 !important;
				overflow: visible !important;
				pointer-events: auto !important;
				transform: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-pending #payment,
			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading #payment {
				display: block !important;
				visibility: visible !important;
			}

			/* Tarjeta de datos: papel cálido y controles inequívocos. */
			html body.elmercado-child-theme.woocommerce-checkout #customer_details {
				box-sizing: border-box !important;
				padding: clamp(20px, 3vw, 30px) !important;
				border: 1px solid rgba(23,63,50,.12) !important;
				border-radius: 20px !important;
				background: #fffdf8 !important;
				box-shadow: 0 16px 42px rgba(13,33,27,.07) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details :is(.col-1,.col-2) {
				box-sizing: border-box !important;
				min-width: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details :is(label,.woocommerce-input-wrapper,.form-row) {
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details :is(input.input-text,textarea,select),
			html body.elmercado-child-theme.woocommerce-checkout #customer_details .select2-container .select2-selection {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				border: 1px solid rgba(23,63,50,.20) !important;
				border-radius: 12px !important;
				background: #fff !important;
				color: #173f32 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details :is(input.input-text,select),
			html body.elmercado-child-theme.woocommerce-checkout #customer_details .select2-container .select2-selection {
				min-height: 48px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details textarea {
				min-height: 136px !important;
				padding: 12px 14px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details input.input-text {
				padding: 11px 13px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #customer_details ::placeholder {
				color: #66736c !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .select2-dropdown {
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 10px !important;
				background: #fffdf8 !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .select2-results__option {
				color: #173f32 !important;
			}

			/* Resumen: una sola superficie oscura, legible y estable. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review_heading {
				position: static !important;
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 22px 22px 9px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-bottom: 0 !important;
				border-radius: 18px 18px 0 0 !important;
				background: #173f32 !important;
				color: #fffdf8 !important;
				font-size: clamp(24px, 2.6vw, 31px) !important;
				line-height: 1.12 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review {
				position: relative !important;
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 8px 22px 22px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-top: 0 !important;
				border-radius: 0 0 18px 18px !important;
				background: #173f32 !important;
				box-shadow: 0 20px 52px rgba(13,33,27,.18) !important;
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(.shop_table,.shop_table > *,tr,th,td),
			html body.elmercado-child-theme.woocommerce-checkout #payment,
			html body.elmercado-child-theme.woocommerce-checkout #payment > *,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods,
			html body.elmercado-child-theme.woocommerce-checkout #payment .form-row.place-order {
				background: transparent !important;
				background-color: transparent !important;
				border-color: rgba(255,255,255,.13) !important;
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table {
				width: 100% !important;
				margin: 0 0 18px !important;
				border: 0 !important;
				border-collapse: collapse !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table :is(th,td) {
				padding: 10px 0 !important;
				border: 0 !important;
				border-bottom: 1px solid rgba(255,255,255,.11) !important;
				color: rgba(255,253,248,.88) !important;
				vertical-align: top !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table :is(.product-name,.product-total,.amount,strong,small) {
				color: #fffdf8 !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tfoot tr:last-child :is(th,td,strong,.amount) {
				border-bottom: 0 !important;
				color: #fffdf8 !important;
				font-size: 1.04rem !important;
			}

			/* Aviso de actualización: acompaña al contenido, nunca lo sustituye. */
			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-loading-note {
				display: none !important;
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 0 12px !important;
				padding: 10px 12px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 10px !important;
				background: rgba(255,255,255,.065) !important;
				color: rgba(255,253,248,.82) !important;
				font-size: 12px !important;
				font-weight: 650 !important;
				line-height: 1.4 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-updating > .emo-checkout-loading-note {
				display: block !important;
			}

			/* Pago: métodos siempre presentes; solo la explicación activa se abre/cierra. */
			html body.elmercado-child-theme.woocommerce-checkout #payment {
				display: block !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment ul.wc_payment_methods,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods {
				display: grid !important;
				width: 100% !important;
				gap: 8px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				list-style: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods > li {
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 12px 13px !important;
				border: 1px solid rgba(255,255,255,.13) !important;
				border-radius: 12px !important;
				background: rgba(255,255,255,.045) !important;
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method > input[type="radio"] {
				margin: 2px 8px 0 0 !important;
				accent-color: #f1d59c !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method > label,
			html body.elmercado-child-theme.woocommerce-checkout #payment :is(label,p,span,strong,small,.woocommerce-terms-and-conditions-checkbox-text,.woocommerce-privacy-policy-text) {
				color: #fffdf8 !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box,
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box * {
				background-color: transparent !important;
				color: #f7f3ea !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				margin: 11px 0 0 !important;
				padding: 12px 13px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 10px !important;
				background: #214f40 !important;
				background-color: #214f40 !important;
				color: #f7f3ea !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box::before {
				border-bottom-color: #214f40 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box a {
				color: #f1d59c !important;
				font-weight: 800 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box :is(
				input:not([type="radio"]):not([type="checkbox"]),
				select,
				textarea,
				.StripeElement,
				.wc-stripe-elements-field
			) {
				box-sizing: border-box !important;
				max-width: 100% !important;
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 9px !important;
				background: #fffdf8 !important;
				background-color: #fffdf8 !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box iframe {
				max-width: 100% !important;
				border-radius: 9px !important;
				background: #fff !important;
				background-color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box .button {
				background: #f1d59c !important;
				border-color: #f1d59c !important;
				color: #0d211b !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .form-row.place-order {
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 16px 0 0 !important;
				padding: 16px 0 0 !important;
				border-top: 1px solid rgba(255,255,255,.13) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment :is(input[type="radio"],input[type="checkbox"]) {
				accent-color: #f1d59c !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment a:not(.button) {
				color: #f1d59c !important;
				text-decoration-color: rgba(241,213,156,.55) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #place_order {
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				min-height: 50px !important;
				align-items: center !important;
				justify-content: center !important;
				margin: 12px 0 0 !important;
				padding: 12px 18px !important;
				border: 1px solid #f1d59c !important;
				border-radius: 999px !important;
				background: #f1d59c !important;
				color: #0d211b !important;
				font-weight: 900 !important;
				opacity: 1 !important;
			}

			/* El overlay de WooCommerce comunica carga sin borrar el contenido. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review .blockUI.blockOverlay,
			html body.elmercado-child-theme.woocommerce-checkout #payment .blockUI.blockOverlay {
				border-radius: 14px !important;
				background: rgba(13,33,27,.16) !important;
				opacity: .36 !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column {
					position: static !important;
					top: auto !important;
					grid-column: 1 !important;
					grid-row: auto !important;
					margin: 0 !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout form.checkout {
					gap: 18px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #customer_details {
					padding: 16px !important;
					border-radius: 17px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review_heading {
					padding: 18px 17px 8px !important;
					border-radius: 16px 16px 0 0 !important;
					font-size: 25px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review {
					padding: 6px 17px 18px !important;
					border-radius: 0 0 16px 16px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method,
				html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods > li {
					padding: 11px 12px !important;
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
		<script id="elmercado-checkout-stability-runtime-010124">
		(() => {
			'use strict';

			const restoreReview = () => {
				const review = document.querySelector('#order_review');
				const heading = document.querySelector('#order_review_heading');
				const column = review?.closest('.emo-checkout-summary-column');
				if (!review) return;

				review.classList.remove('emo-order-review-loading', 'emo-order-review-pending');
				column?.classList.add('emo-checkout-summary-stable');
				column?.querySelectorAll(':scope > .emo-checkout-status-card').forEach((card) => card.remove());

				[review, heading].forEach((node) => {
					if (!node) return;
					[
						'display', 'visibility', 'opacity', 'height', 'min-height', 'max-height',
						'overflow', 'pointer-events', 'position', 'transform'
					].forEach((property) => node.style.removeProperty(property));
				});
			};

			const start = () => {
				restoreReview();

				document.addEventListener('change', (event) => {
					const target = event.target;
					if (!(target instanceof Element)) return;
					if (target.matches('input[name="payment_method"],input.shipping_method,select.shipping_method')) {
						restoreReview();
					}
				});

				if (window.jQuery) {
					window.jQuery(document.body).on(
						'update_checkout updated_checkout checkout_error payment_method_selected',
						restoreReview
					);
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
