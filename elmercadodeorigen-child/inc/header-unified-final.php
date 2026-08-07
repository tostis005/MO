<?php
/**
 * Capa unificada de geometría de cabecera, navegación móvil y contador.
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
			body.elmercado-child-theme .site-header .site-tools {
				align-items: center !important;
				overflow: visible !important;
			}
			body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account,.shopping-cart,.shopping-bag-button,.cart-contents) {
				box-sizing: border-box !important;
				margin: 0 !important;
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
					min-height: 60px !important;
					overflow: visible !important;
				}
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: 28px minmax(0, 1fr) 102px !important;
					grid-template-rows: 60px !important;
					align-items: center !important;
					column-gap: 4px !important;
					width: 100% !important;
					height: 60px !important;
					min-height: 60px !important;
					padding: 0 10px !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				body.elmercado-child-theme .site-header .site-branding,
				body.elmercado-child-theme .site-header .site-tools {
					position: static !important;
					inset: auto !important;
					transform: none !important;
					align-self: center !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					grid-column: 1 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 28px !important;
					height: 40px !important;
					min-width: 28px !important;
					margin: 0 !important;
					padding: 0 !important;
					align-items: center !important;
					justify-content: flex-start !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::after,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::after {
					width: 18px !important;
					max-width: 18px !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					grid-column: 2 !important;
					grid-row: 1 !important;
					justify-self: start !important;
					min-width: 0 !important;
					max-width: 100% !important;
					margin: 0 !important;
					overflow: hidden !important;
					text-align: left !important;
				}
				body.elmercado-child-theme .site-header .site-branding :is(.site-title,a) {
					display: block !important;
					max-width: 100% !important;
					margin: 0 !important;
					font-size: clamp(12px,3.2vw,14px) !important;
					font-weight: 700 !important;
					line-height: 1.15 !important;
					white-space: nowrap !important;
					overflow: hidden !important;
					text-overflow: ellipsis !important;
					text-align: left !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					grid-column: 3 !important;
					grid-row: 1 !important;
					display: grid !important;
					grid-template-columns: repeat(3,34px) !important;
					grid-auto-flow: column !important;
					grid-auto-columns: 34px !important;
					align-items: center !important;
					justify-items: center !important;
					justify-content: end !important;
					gap: 0 !important;
					width: 102px !important;
					height: 40px !important;
					min-width: 102px !important;
					margin: 0 !important;
					padding: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-tools > *,
				body.elmercado-child-theme .site-header .site-tools > * > a,
				body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account,.shopping-cart,.shopping-bag-button,.cart-contents) {
					display: grid !important;
					width: 34px !important;
					height: 40px !important;
					min-width: 34px !important;
					max-width: 34px !important;
					min-height: 40px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					align-self: center !important;
					line-height: 1 !important;
					transform: none !important;
				}
				body.elmercado-child-theme .site-header .site-tools :is(svg,i) {
					margin: 0 !important;
					vertical-align: middle !important;
					transform: none !important;
				}

				/* El cierre nativo puede estar fuera de .sidebar-menu: se oculta durante este panel. */
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu,
				html.sidebar-menu-open body.elmercado-child-theme [class*="close-sidebar"] {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
				}
			}

			@media (max-width: 374px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					grid-template-columns: 26px minmax(0,1fr) 96px !important;
					column-gap: 3px !important;
					padding-inline: 8px !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					width: 26px !important;
					min-width: 26px !important;
				}
				body.elmercado-child-theme .site-header .site-branding :is(.site-title,a) {
					font-size: 11.5px !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					grid-template-columns: repeat(3,32px) !important;
					grid-auto-columns: 32px !important;
					width: 96px !important;
					min-width: 96px !important;
				}
				body.elmercado-child-theme .site-header .site-tools > *,
				body.elmercado-child-theme .site-header .site-tools > * > a,
				body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account,.shopping-cart,.shopping-bag-button,.cart-contents) {
					width: 32px !important;
					min-width: 32px !important;
					max-width: 32px !important;
				}
			}

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
			const header = document.querySelector('.site-header');
			if (!header) return;
			const selector = '.shopping-cart-count,.shop-cart-count,.cart-count,.mini-cart-count,.shopping-cart .count,.shopping-bag-button .count,.cart-contents .count';
			const parseCount = (node) => {
				for (const name of ['data-count','data-cart-count','data-items']) {
					const value = node.getAttribute(name);
					if (value !== null && /^\d+$/.test(value.trim())) return Number.parseInt(value, 10);
				}
				const text = (node.textContent || '').trim();
				return /^\d+$/.test(text) ? Number.parseInt(text, 10) : null;
			};
			const sync = () => {
				header.querySelectorAll(selector).forEach((node) => {
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
			sync();
			new MutationObserver(queueSync).observe(header, {
				childList: true,
				subtree: true,
				characterData: true,
				attributes: true,
				attributeFilter: ['class','data-count','data-cart-count','data-items']
			});
			if (window.jQuery) {
				window.jQuery(document.body).on('wc_fragments_refreshed added_to_cart removed_from_cart updated_wc_div', queueSync);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
