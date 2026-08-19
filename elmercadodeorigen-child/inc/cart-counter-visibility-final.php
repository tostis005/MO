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

			const cartNodes = (header) => {
				const selector = [
					'.site-tools [class*="cart"]',
					'.site-tools [class*="bag"]',
					'.site-tools [data-count]',
					'.site-tools [data-cart-count]',
					'.site-tools [data-items]',
					'.cart-contents',
					'.mini-cart',
					'[class*="cart-count"]'
				].join(',');
				const nodes = new Set();
				header.querySelectorAll(selector).forEach((container) => {
					nodes.add(container);
					container.querySelectorAll('*').forEach((node) => nodes.add(node));
				});
				return [...nodes].filter((node) => node instanceof HTMLElement);
			};

			const sync = () => {
				const header = document.querySelector('.site-header');
				if (!header) return;

				/*
				 * Fase de lectura: no modificamos clases ni atributos hasta terminar de
				 * consultar todos los estilos. Así evitamos alternar write/read y forzar
				 * un recálculo de layout por cada nodo del header.
				 */
				const snapshot = cartNodes(header).map((node) => {
					const textCount = node.children.length === 0 ? numericText(node) : null;
					const before = pseudoNumber(getComputedStyle(node, '::before').content);
					const after = pseudoNumber(getComputedStyle(node, '::after').content);
					return { node, textCount, before, after };
				});

				/* Fase de escritura: todas las mutaciones se agrupan después de leer. */
				snapshot.forEach(({ node, textCount, before, after }) => {
					const textEmpty = textCount === 0;
					node.classList.toggle('emo-cart-count-empty', textEmpty);
					if (textCount !== null) node.setAttribute('aria-hidden', textEmpty ? 'true' : 'false');
					node.classList.toggle('emo-cart-pseudo-empty', before === 0 || after === 0);
				});
			};

			const start = () => {
				let scheduled = false;
				const schedule = () => {
					if (scheduled) return;
					scheduled = true;
					requestAnimationFrame(() => {
						scheduled = false;
						sync();
					});
				};

				const header = document.querySelector('.site-header');
				if (!header) return;
				schedule();

				/* Sólo observamos el header; cambios del catálogo ya no relanzan sync(). */
				const observer = new MutationObserver(schedule);
				observer.observe(header, {
					childList: true,
					subtree: true,
					characterData: true,
					attributes: true,
					attributeFilter: ['class', 'data-count', 'data-cart-count', 'data-items']
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
