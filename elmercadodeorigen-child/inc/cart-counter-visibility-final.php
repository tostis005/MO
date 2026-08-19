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

			const numeric = (value) => {
				const normalized = String(value ?? '').trim();
				return /^\d+$/.test(normalized) ? Number.parseInt(normalized, 10) : null;
			};

			const countFor = (node) => {
				for (const name of ['data-count', 'data-cart-count', 'data-items']) {
					if (node.hasAttribute(name)) return numeric(node.getAttribute(name));
				}
				return node.children.length === 0 ? numeric(node.textContent) : null;
			};

			const sync = () => {
				const header = document.querySelector('.site-header');
				if (!header) return;

				const nodes = header.querySelectorAll(
					'[data-count],[data-cart-count],[data-items],.cart-count,.mini-cart-count,.cart-contents-count,.shopping-cart .count,.shopping-bag-button .count'
				);
				nodes.forEach((node) => {
					const count = countFor(node);
					if (count === null) return;
					const empty = count === 0;
					node.classList.toggle('emo-cart-count-empty', empty);
					node.setAttribute('aria-hidden', empty ? 'true' : 'false');
				});
			};

			const start = () => {
				const header = document.querySelector('.site-header');
				if (!header) return;

				sync();
				let scheduled = false;
				const schedule = () => {
					if (scheduled) return;
					scheduled = true;
					requestAnimationFrame(() => {
						scheduled = false;
						sync();
					});
				};

				new MutationObserver(schedule).observe(header, {
					childList: true,
					subtree: true,
					characterData: true,
					attributes: true,
					attributeFilter: ['data-count', 'data-cart-count', 'data-items']
				});

				['wc_fragments_refreshed', 'added_to_cart', 'removed_from_cart', 'updated_wc_div'].forEach((eventName) => {
					document.body.addEventListener(eventName, schedule);
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
