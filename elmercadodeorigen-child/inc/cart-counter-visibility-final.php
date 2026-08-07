<?php
/**
 * Estado final del contador del carrito en cabecera.
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
		<style id="elmercado-cart-counter-visibility-final">
			body.elmercado-child-theme .site-header .emo-cart-count-empty {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}

			body.elmercado-child-theme .site-header .emo-cart-pseudo-empty::before,
			body.elmercado-child-theme .site-header .emo-cart-pseudo-empty::after,
			body.elmercado-child-theme .site-header [data-count="0"]::before,
			body.elmercado-child-theme .site-header [data-count="0"]::after,
			body.elmercado-child-theme .site-header [data-cart-count="0"]::before,
			body.elmercado-child-theme .site-header [data-cart-count="0"]::after,
			body.elmercado-child-theme .site-header [data-items="0"]::before,
			body.elmercado-child-theme .site-header [data-items="0"]::after {
				content: none !important;
				display: none !important;
			}
		</style>
		<script id="elmercado-cart-counter-visibility-final-js">
		(() => {
			'use strict';

			const explicitCounters = '.site-header .shop-cart-count, .site-header .cart-count, .site-header .cart-contents-count, .site-header .elmercado-cart-direct-count, .site-header [class*="cart-count"], .site-header [class*="cart_count"]';
			const cartRoots = '.site-header .shopping-cart, .site-header .shopping-bag-button, .site-header .cart-contents, .site-header [class*="shopping-cart"], .site-header [class*="shopping-bag"], .site-header a[href*="carrito"], .site-header a[href*="cart"]';

			const numericText = (node) => {
				const value = (node.textContent || '').trim();
				return /^\d+$/.test(value) ? Number.parseInt(value, 10) : null;
			};

			const quotedPseudoNumber = (value) => {
				const normalized = String(value || '').replace(/^['"]|['"]$/g, '').trim();
				return /^\d+$/.test(normalized) ? Number.parseInt(normalized, 10) : null;
			};

			const syncNode = (node) => {
				const count = numericText(node);
				if (count === null) return;
				const empty = count <= 0;
				node.classList.toggle('emo-cart-count-empty', empty);
				node.setAttribute('aria-hidden', empty ? 'true' : 'false');
			};

			const syncPseudo = (node) => {
				const before = quotedPseudoNumber(getComputedStyle(node, '::before').content);
				const after = quotedPseudoNumber(getComputedStyle(node, '::after').content);
				node.classList.toggle('emo-cart-pseudo-empty', before === 0 || after === 0);
			};

			const sync = () => {
				document.querySelectorAll(explicitCounters).forEach(syncNode);

				document.querySelectorAll(cartRoots).forEach((root) => {
					syncPseudo(root);
					root.querySelectorAll('*').forEach((node) => {
						if (node.children.length === 0) syncNode(node);
						syncPseudo(node);
					});
				});
			};

			const start = () => {
				sync();
				let scheduled = false;
				const observer = new MutationObserver(() => {
					if (scheduled) return;
					scheduled = true;
					requestAnimationFrame(() => {
						scheduled = false;
						sync();
					});
				});
				observer.observe(document.documentElement, {
					childList: true,
					subtree: true,
					characterData: true,
					attributes: true,
					attributeFilter: ['class', 'data-count', 'data-cart-count', 'data-items']
				});
				['wc_fragments_refreshed', 'added_to_cart', 'removed_from_cart', 'updated_wc_div'].forEach((eventName) => {
					document.body.addEventListener(eventName, () => requestAnimationFrame(sync));
				});
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
