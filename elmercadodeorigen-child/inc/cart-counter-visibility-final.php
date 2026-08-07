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
			body.elmercado-child-theme .site-header .site-tools .emo-cart-count-empty {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}
		</style>
		<script id="elmercado-cart-counter-visibility-final-js">
		(() => {
			'use strict';

			const selector = '.site-header .site-tools .shop-cart-count, .site-header .site-tools .cart-count, .site-header .site-tools .shopping-cart .count, .site-header .site-tools .shopping-bag-button .count, .site-header .site-tools .cart-contents .count';

			const readCount = (node) => {
				const raw = (node.textContent || '').replace(/[^0-9]/g, '');
				return raw === '' ? 0 : Number.parseInt(raw, 10) || 0;
			};

			const sync = (root = document) => {
				root.querySelectorAll(selector).forEach((node) => {
					const empty = readCount(node) <= 0;
					node.classList.toggle('emo-cart-count-empty', empty);
					node.setAttribute('aria-hidden', empty ? 'true' : 'false');
				});
			};

			const start = () => {
				sync();
				const observer = new MutationObserver(() => sync());
				observer.observe(document.documentElement, {
					childList: true,
					subtree: true,
					characterData: true
				});
				document.body.addEventListener('wc_fragments_refreshed', () => sync());
				document.body.addEventListener('added_to_cart', () => sync());
				document.body.addEventListener('removed_from_cart', () => sync());
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
