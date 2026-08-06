<?php
/**
 * Visibilidad fiable del contador del carrito.
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
		<style id="elmercado-cart-count-visibility">
			body.elmercado-child-theme .site-header :is(.shopping-cart-count,.cart-count,.mini-cart-count,.shopping-bag-button .count).elmercado-cart-count-empty {
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-cart-count-visibility-script">
		(() => {
			'use strict';

			const selector = '.site-header .shopping-cart-count, .site-header .cart-count, .site-header .mini-cart-count, .site-header .shopping-bag-button .count';

			const updateCounter = (node) => {
				if (!(node instanceof HTMLElement)) return;
				const raw = (node.textContent || '').trim();
				const value = Number.parseInt(raw.replace(/[^0-9-]/g, ''), 10);
				const empty = !Number.isFinite(value) || value <= 0;
				node.classList.toggle('elmercado-cart-count-empty', empty);
				node.setAttribute('aria-hidden', empty ? 'true' : 'false');
			};

			const refresh = () => document.querySelectorAll(selector).forEach(updateCounter);

			const observer = new MutationObserver((mutations) => {
				let needsRefresh = false;
				for (const mutation of mutations) {
					if (mutation.type === 'characterData' || mutation.type === 'childList') {
						needsRefresh = true;
						break;
					}
				}
				if (needsRefresh) requestAnimationFrame(refresh);
			});

			document.addEventListener('DOMContentLoaded', () => {
				refresh();
				observer.observe(document.body, { subtree: true, childList: true, characterData: true });
				if (window.jQuery) {
					window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', refresh);
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
