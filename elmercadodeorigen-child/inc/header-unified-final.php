<?php
/**
 * Capa final y única de geometría de cabecera y estado del contador.
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
		<style id="elmercado-header-unified-final">
			/* Los iconos de herramientas comparten geometría y no muestran subrayados heredados. */
			body.elmercado-child-theme .site-header .site-tools {
				align-items: center !important;
				overflow: visible !important;
			}

			body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account,.shopping-cart,.shopping-bag-button,.cart-contents) {
				box-sizing: border-box !important;
				margin: 0 !important;
			}

			body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account > a.tools-icon,.shopping-cart,.shopping-bag-button,.cart-contents)::before,
			body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account > a.tools-icon,.shopping-cart,.shopping-bag-button,.cart-contents)::after {
				text-decoration: none !important;
			}

			body.elmercado-child-theme .site-header .site-tools > .header-search-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .search-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .site-search-toggle::after,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon::after {
				content: none !important;
				display: none !important;
			}

			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .site-tools {
				display: grid !important;
				grid-auto-flow: column !important;
				grid-auto-columns: 44px !important;
				justify-content: end !important;
				gap: 8px !important;
				margin: 0 !important;
			}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > .search-icon,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
				display: grid !important;
				width: 44px !important;
				height: 44px !important;
				min-width: 44px !important;
				padding: 0 !important;
				place-items: center !important;
				}
			}

			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header,
				body.elmercado-child-theme .site-header-inner {
				height: auto !important;
				min-height: 64px !important;
				overflow: visible !important;
				}

				body.elmercado-child-theme .site-header-inner > .woostify-container {
				display: grid !important;
				grid-template-columns: 40px minmax(0, 1fr) 120px !important;
				grid-template-rows: 64px !important;
				align-items: center !important;
				column-gap: 6px !important;
				width: 100% !important;
				height: 64px !important;
				min-height: 64px !important;
				padding: 0 10px !important;
			}

				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				body.elmercado-child-theme .site-header .site-branding,
				body.elmercado-child-theme .site-header .site-tools {
				position: static !important;
				top: auto !important;
				right: auto !important;
				bottom: auto !important;
				left: auto !important;
				transform: none !important;
				align-self: center !important;
			}

				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
				grid-column: 1 !important;
				grid-row: 1 !important;
				display: flex !important;
				width: 40px !important;
				height: 40px !important;
				min-width: 40px !important;
				margin: 0 !important;
				padding: 0 !important;
				align-items: center !important;
				justify-content: center !important;
			}

				body.elmercado-child-theme .site-header .site-branding {
				grid-column: 2 !important;
				grid-row: 1 !important;
				min-width: 0 !important;
				max-width: 100% !important;
				margin: 0 !important;
				overflow: hidden !important;
			}

				body.elmercado-child-theme .site-header .site-branding :is(.site-title,a) {
				display: block !important;
				max-width: 100% !important;
				margin: 0 !important;
				font-size: clamp(16px, 4.7vw, 20px) !important;
				line-height: 1.15 !important;
				white-space: nowrap !important;
				overflow: hidden !important;
				text-overflow: ellipsis !important;
			}

				body.elmercado-child-theme .site-header .site-tools {
				grid-column: 3 !important;
				grid-row: 1 !important;
				display: grid !important;
				grid-template-columns: repeat(3, 36px) !important;
				grid-auto-flow: column !important;
				align-items: center !important;
				justify-content: end !important;
				gap: 6px !important;
				width: 120px !important;
				height: 40px !important;
				margin: 0 !important;
				padding: 0 !important;
			}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > .search-icon,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
				display: grid !important;
				width: 36px !important;
				height: 36px !important;
				min-width: 36px !important;
				min-height: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				place-items: center !important;
				line-height: 1 !important;
			}
			}

			@media (max-width: 374px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
				grid-template-columns: 38px minmax(0, 1fr) 108px !important;
				column-gap: 4px !important;
				padding-inline: 8px !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
				grid-template-columns: repeat(3, 32px) !important;
				gap: 6px !important;
				width: 108px !important;
				}
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > .search-icon,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
				width: 32px !important;
				height: 32px !important;
				min-width: 32px !important;
				min-height: 32px !important;
				}
			}

			/* El contador se oculta siempre que represente cero, incluso si otra capa fuerza display. */
			body.elmercado-child-theme .site-header :is(.shopping-cart-count,.shop-cart-count,.cart-count,.mini-cart-count,.count).emo-cart-count-empty,
			body.elmercado-child-theme .site-header :is(.shopping-cart-count,.shop-cart-count,.cart-count,.mini-cart-count,.count)[data-count="0"],
			body.elmercado-child-theme .site-header :is(.shopping-cart-count,.shop-cart-count,.cart-count,.mini-cart-count,.count)[data-cart-count="0"],
			body.elmercado-child-theme .site-header :is(.shopping-cart-count,.shop-cart-count,.cart-count,.mini-cart-count,.count)[data-items="0"] {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
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
		<script id="elmercado-header-unified-final-js">
		(() => {
			'use strict';

			const selector = '.site-header .shopping-cart-count,.site-header .shop-cart-count,.site-header .cart-count,.site-header .mini-cart-count,.site-header .shopping-cart .count,.site-header .shopping-bag-button .count,.site-header .cart-contents .count';

			const parseCount = (node) => {
				for (const name of ['data-count','data-cart-count','data-items']) {
					const value = node.getAttribute(name);
					if (value !== null && /^\\d+$/.test(value.trim())) return Number.parseInt(value, 10);
				}
				const text = (node.textContent || '').trim();
				return /^\\d+$/.test(text) ? Number.parseInt(text, 10) : null;
			};

			const sync = () => {
				document.querySelectorAll(selector).forEach((node) => {
					const count = parseCount(node);
					if (count === null) return;
					const empty = count <= 0;
					node.classList.toggle('emo-cart-count-empty', empty);
					node.setAttribute('aria-hidden', empty ? 'true' : 'false');
					if (empty) {
						node.style.setProperty('display', 'none', 'important');
						node.style.setProperty('visibility', 'hidden', 'important');
						node.style.setProperty('opacity', '0', 'important');
					} else {
						node.style.removeProperty('display');
						node.style.removeProperty('visibility');
						node.style.removeProperty('opacity');
					}
				});
			};

			let queued = false;
			const queueSync = () => {
				if (queued) return;
				queued = true;
				requestAnimationFrame(() => {
					queued = false;
					sync();
				});
			};

			const start = () => {
				sync();
				new MutationObserver(queueSync).observe(document.documentElement, {
					childList: true,
					subtree: true,
					characterData: true,
					attributes: true,
					attributeFilter: ['class','data-count','data-cart-count','data-items']
				});
				if (window.jQuery) {
					window.jQuery(document.body).on('wc_fragments_refreshed added_to_cart removed_from_cart updated_wc_div', queueSync);
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
