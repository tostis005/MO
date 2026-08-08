<?php
/**
 * Foco transaccional y disponibilidad final.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		global $product;

		if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
			return;
		}

		echo '<p class="emo-stock-state" role="status">' . esc_html__( 'Agotado temporalmente', 'elmercadodeorigen' ) . '</p>';
	},
	21
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-transaction-focus-final-01057">
			body.elmercado-child-theme.single-product div.product.outofstock .stock.out-of-stock {
				display: none !important;
			}

			body.elmercado-child-theme.single-product .emo-stock-state {
				display: inline-flex !important;
				align-items: center;
				gap: 8px;
				min-height: 34px;
				margin: 0 0 14px !important;
				padding: 7px 12px;
				border: 1px solid rgba(127, 47, 42, .20);
				border-radius: 999px;
				background: #f8ebe7;
				color: #7f2f2a !important;
				font-size: 12px;
				font-weight: 850;
				letter-spacing: .035em;
				line-height: 1.2;
				text-transform: uppercase;
			}

			body.elmercado-child-theme.single-product .emo-stock-state::before {
				width: 7px;
				height: 7px;
				flex: 0 0 7px;
				border-radius: 50%;
				background: currentColor;
				content: "";
			}

			body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(
				.emo-review-cluster-hidden,
				.emo-review-frame-hidden,
				.ti-widget,
				[class*="trustindex" i],
				[class*="trustpilot" i],
				[class*="google-review" i],
				[class*="reviews-widget" i]
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

			body.elmercado-child-theme.woocommerce-checkout form.checkout,
			body.elmercado-child-theme.woocommerce-checkout .woocommerce-checkout {
				align-items: start !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review,
			html body.elmercado-child-theme.woocommerce-checkout #payment,
			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-checkout-review-order {
				align-self: start !important;
				height: auto !important;
				min-height: 0 !important;
				color: #fffdf8 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(th,td,.product-name,.product-total,.amount,strong,small),
			html body.elmercado-child-theme.woocommerce-checkout #payment :is(label,p,span,strong,small,.woocommerce-terms-and-conditions-checkbox-text,.woocommerce-privacy-policy-text) {
				color: #fffdf8 !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .blockUI.blockOverlay,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .blockUI.blockOverlay {
				background: rgba(23, 63, 50, .12) !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #place_order {
				min-height: 50px !important;
				background: #f1d59c !important;
				border-color: #f1d59c !important;
				color: #0d211b !important;
				font-weight: 900 !important;
				opacity: 1 !important;
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
		<script id="elmercado-transaction-cleanup-01057">
		(() => {
			'use strict';
			const root = document.querySelector('#primary,.site-main') || document.body;
			const protectedSelector = '.woocommerce-cart-form,.cart_totals,#customer_details,#order_review,#payment,form.checkout,.woocommerce-checkout-review-order';
			const isProtected = (node) => !!node?.closest?.(protectedSelector);
			const hits = (node) => ((node?.innerText || '').match(/evaluaciones?|opiniones?|trustpilot|google/gi) || []).length;

			const clean = () => {
				const candidates = [...root.querySelectorAll('section,div,article,aside,ul')].filter((node) => {
					if (isProtected(node) || hits(node) < 2) return false;
					const r = node.getBoundingClientRect();
					return r.width > 240 && r.height > 100 && r.height < 2400;
				});

				if (candidates.length) {
					const maxHits = Math.max(...candidates.map(hits));
					const cluster = candidates
						.filter((node) => hits(node) === maxHits)
						.sort((a, b) => {
							const ar = a.getBoundingClientRect();
							const br = b.getBoundingClientRect();
							return (ar.width * ar.height) - (br.width * br.height);
						})[0];
					if (cluster) cluster.classList.add('emo-review-cluster-hidden');
				}

				root.querySelectorAll('iframe').forEach((frame) => {
					if (isProtected(frame)) return;
					let shell = frame;
					for (let i = 0; i < 3 && shell.parentElement && shell.parentElement !== root; i++) {
						const parent = shell.parentElement;
						const r = parent.getBoundingClientRect();
						if (r.height > 900 || r.width > innerWidth * .95) break;
						shell = parent;
					}
					shell.classList.add('emo-review-frame-hidden');
				});
			};

			const observer = new MutationObserver(() => requestAnimationFrame(clean));
			clean();
			observer.observe(root, { childList: true, subtree: true });
			[120, 400, 900, 1600, 2600, 4200].forEach((delay) => setTimeout(clean, delay));
			setTimeout(() => observer.disconnect(), 6000);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
