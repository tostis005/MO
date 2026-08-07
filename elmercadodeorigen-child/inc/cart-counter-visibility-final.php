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

			const numericText = (node) => {
				const value = (node.textContent || '').trim();
				return /^\d+$/.test(value) ? Number.parseInt(value, 10) : null;
			};

			const pseudoNumber = (value) => {
				const normalized = String(value || '')
					.replace(/^['"]|['"]$/g, '')
					.trim();
				return /^\d+$/.test(normalized) ? Number.parseInt(normalized, 10) : null;
			};

			const sync = () => {
				const header = document.querySelector('.site-header');
				if (!header) return;

				const nodes = [header, ...header.querySelectorAll('*')];
				nodes.forEach((node) => {
					if (!(node instanceof HTMLElement)) return;

					const textCount = node.children.length === 0 ? numericText(node) : null;
					const textEmpty = textCount === 0;
					node.classList.toggle('emo-cart-count-empty', textEmpty);
					if (textCount !== null) node.setAttribute('aria-hidden', textEmpty ? 'true' : 'false');

					const before = pseudoNumber(getComputedStyle(node, '::before').content);
					const after = pseudoNumber(getComputedStyle(node, '::after').content);
					node.classList.toggle('emo-cart-pseudo-empty', before === 0 || after === 0);
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
