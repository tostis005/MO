<?php
/**
 * Consolidación final de la cabecera en todas las plantillas.
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
		<style id="elmercado-header-final-audit">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: minmax(190px, auto) minmax(0, 1fr) 148px !important;
					align-items: center !important;
					column-gap: clamp(28px, 3.2vw, 54px) !important;
					min-height: 80px !important;
				}

				body.elmercado-child-theme .site-header .site-branding {
					margin: 0 !important;
					justify-self: start !important;
				}

				body.elmercado-child-theme .site-header .main-navigation {
					width: 100% !important;
					margin: 0 !important;
					justify-content: center !important;
				}

				body.elmercado-child-theme .site-header .site-tools {
					display: grid !important;
					grid-template-columns: repeat(3, 44px) !important;
					align-items: center !important;
					justify-content: end !important;
					column-gap: 8px !important;
					width: 148px !important;
					margin: 0 !important;
					overflow: visible !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > .search-icon,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents) {
					position: relative !important;
					display: grid !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: transparent !important;
					box-shadow: none !important;
					line-height: 1 !important;
					transition: background-color 160ms ease, color 160ms ease !important;
					overflow: visible !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > .search-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > .search-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle:hover,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents):hover,
				body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents):focus-visible {
					background: #e8f1eb !important;
				}
			}

			/* No usar subrayados en los iconos de utilidad. */
			body.elmercado-child-theme .site-header .site-tools *::after,
			body.elmercado-child-theme .site-header .site-tools *::before {
				text-decoration: none !important;
			}

			body.elmercado-child-theme .site-header .site-tools > .header-search-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .search-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .site-search-toggle::after,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon::after {
				display: none !important;
				content: none !important;
			}

			/* El contador solo aparece si WooCommerce informa de una cantidad real. */
			body.elmercado-child-theme .site-header .site-tools :is(.shop-cart-count,.cart-count,.count).is-zero,
			body.elmercado-child-theme .site-header .site-tools :is(.shop-cart-count,.cart-count,.count):empty {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
			}

			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header .site-tools {
					display: flex !important;
					align-items: center !important;
					gap: 4px !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > .search-icon,
				body.elmercado-child-theme .site-header .site-tools > .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents) {
					position: relative !important;
					display: grid !important;
					width: 40px !important;
					height: 40px !important;
					min-width: 40px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: transparent !important;
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
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-header-final-audit-script">
		(() => {
			'use strict';

			const selectors = '.site-header .site-tools .shop-cart-count, .site-header .site-tools .cart-count, .site-header .site-tools .count';

			const syncCounter = (node) => {
				const value = Number.parseInt((node.textContent || '').trim(), 10);
				node.classList.toggle('is-zero', !Number.isFinite(value) || value < 1);
				node.setAttribute('aria-hidden', (!Number.isFinite(value) || value < 1) ? 'true' : 'false');
			};

			const syncAll = () => document.querySelectorAll(selectors).forEach(syncCounter);
			syncAll();

			const observer = new MutationObserver(syncAll);
			observer.observe(document.documentElement, { childList: true, subtree: true, characterData: true });
			document.body.addEventListener('wc_fragments_refreshed', syncAll);
			document.body.addEventListener('updated_wc_div', syncAll);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
