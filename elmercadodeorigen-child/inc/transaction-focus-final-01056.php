<?php
/**
 * Foco transaccional de carrito y checkout 0.10.56.
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
		<style id="elmercado-transaction-focus-final-01056">
			/* Carrito y checkout deben terminar en la acción, no en una pared de reseñas. */
			body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(
				.emo-transaction-review-hidden,
				.emo-transaction-review-shell-hidden,
				.ti-widget,
				[class*="trustindex" i],
				[class*="trustpilot" i],
				[class*="google-review" i],
				[class*="google_reviews" i],
				[class*="reviews-widget" i],
				iframe[src*="trustindex" i],
				iframe[src*="trustpilot" i],
				iframe[title*="trustpilot" i],
				iframe[title*="reviews" i]
			) {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
			}

			/* El panel de pago necesita contraste estable incluso mientras WooCommerce
			 * recalcula el pedido y otros módulos vuelven a inyectar estilos. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review,
			html body.elmercado-child-theme.woocommerce-checkout #payment {
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(
				th, td, .product-name, .product-total, .amount, strong, small
			),
			html body.elmercado-child-theme.woocommerce-checkout #payment :is(
				.payment_methods > li > label,
				.woocommerce-terms-and-conditions-checkbox-text,
				.woocommerce-privacy-policy-text,
				.form-row label
			) {
				color: #fffdf8 !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table th {
				font-size: 11px !important;
				font-weight: 850 !important;
				letter-spacing: .055em !important;
				text-transform: uppercase !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table td {
				font-size: 13px !important;
				line-height: 1.5 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_methods > li {
				padding-block: 10px !important;
				border-bottom: 1px solid rgba(255,255,255,.10) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_methods > li:last-child {
				border-bottom: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_methods > li > label {
				font-size: 13px !important;
				font-weight: 760 !important;
				line-height: 1.4 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment input[type="radio"],
			html body.elmercado-child-theme.woocommerce-checkout #payment input[type="checkbox"] {
				accent-color: #d7a84f !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #place_order {
				min-height: 50px !important;
				background: #f1d59c !important;
				border-color: #f1d59c !important;
				color: #0d211b !important;
				font-size: 12px !important;
				font-weight: 900 !important;
				letter-spacing: .025em !important;
				opacity: 1 !important;
			}

			/* El overlay de actualización debe indicar actividad sin borrar la lectura. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review > .blockUI.blockOverlay,
			html body.elmercado-child-theme.woocommerce-checkout #payment > .blockUI.blockOverlay {
				background: rgba(23,63,50,.18) !important;
				opacity: 1 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .site-content,
			body.elmercado-child-theme.woocommerce-checkout .site-content {
				padding-bottom: clamp(3.5rem, 7vw, 6rem) !important;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout #order_review_heading {
					padding: 18px 18px 4px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review {
					padding: 12px 18px 18px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #payment .payment_methods > li {
					padding-block: 8px !important;
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
		<script id="elmercado-transaction-focus-final-js-01056">
		(() => {
			'use strict';

			const root = document.querySelector('#primary,.site-main') || document.body;
			const protectedSelector = '.woocommerce-cart-form,.cart_totals,#customer_details,#order_review,#payment,form.checkout,.woocommerce-checkout-review-order';
			const reviewMarker = /(?:evaluaciones?|opiniones?|reviews?)/gi;

			const countMarkers = (node) => ((node?.textContent || '').match(reviewMarker) || []).length;
			const isProtected = (node) => !!node.closest?.(protectedSelector);

			const hideReviewCards = () => {
				const leaves = [...root.querySelectorAll('*')].filter((node) => {
					if (isProtected(node) || countMarkers(node) !== 1) return false;
					return ![...node.children].some((child) => countMarkers(child) === 1);
				});

				const cards = new Set();
				leaves.forEach((leaf) => {
					let card = leaf;
					while (card.parentElement && card.parentElement !== root) {
						const parent = card.parentElement;
						if (isProtected(parent) || countMarkers(parent) !== 1) break;
						const rect = parent.getBoundingClientRect();
						if (rect.height > 620 || rect.width > innerWidth * .92) break;
						card = parent;
					}
					cards.add(card);
				});

				cards.forEach((card) => card.classList.add('emo-transaction-review-hidden'));

				/* Si todas las tarjetas de un grid han quedado fuera, retiramos también
				 * su envoltorio para que no sobreviva un hueco vacío. */
				cards.forEach((card) => {
					let parent = card.parentElement;
					while (parent && parent !== root && !isProtected(parent)) {
						const children = [...parent.children];
						if (!children.length || !children.every((child) => child.classList.contains('emo-transaction-review-hidden') || child.classList.contains('emo-transaction-review-shell-hidden'))) break;
						parent.classList.add('emo-transaction-review-shell-hidden');
						parent = parent.parentElement;
					}
				});
			};

			const observer = new MutationObserver(() => requestAnimationFrame(hideReviewCards));
			document.addEventListener('DOMContentLoaded', () => {
				hideReviewCards();
				observer.observe(root, { childList: true, subtree: true });
				[150, 500, 1200, 2200].forEach((delay) => setTimeout(hideReviewCards, delay));
				setTimeout(() => observer.disconnect(), 5000);
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
