<?php
/**
 * Final mobile/tablet alignment for vendor result count, ordering, menu close
 * and product cards.
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
		<style id="elmercado-vendor-toolbar-mobile-final">
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
				display: flex !important;
				flex-flow: row nowrap !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 14px !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count,
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				position: static !important;
				inset: auto !important;
				clear: none !important;
				float: none !important;
				align-self: center !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				flex: 1 1 auto !important;
				width: auto !important;
				min-width: 0 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				flex: 0 0 min(260px,42vw) !important;
				width: min(260px,42vw) !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
			}

			/* La foto ocupa el borde superior completo de la tarjeta, como en Tienda. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product {
				padding: 0 !important;
				overflow: hidden !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product .product-loop-image-wrapper {
				width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				overflow: hidden !important;
				background: #f7f4ec !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product .product-loop-image-wrapper img,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product img.product-loop-image,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product .woocommerce-loop-product__link > img {
				display: block !important;
				width: 100% !important;
				height: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				border-radius: 0 !important;
				object-fit: cover !important;
			}

			@media (max-width: 991px) {
				/* El trigger del header no se transforma en una segunda X. */
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu,
				html.sidebar-menu-open body.elmercado-child-theme [class*="close-sidebar"] {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}

				/* Un único cierre visible, fuera del borde del panel y plenamente accesible. */
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu {
					overflow: visible !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					position: absolute !important;
					top: 13px !important;
					right: -33px !important;
					display: grid !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					box-shadow: 0 6px 20px rgba(8,27,22,.24) !important;
					color: transparent !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
					z-index: 10040 !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					content: "" !important;
					position: absolute !important;
					width: 19px !important;
					height: 2px !important;
					border-radius: 999px !important;
					background: #fff !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before {
					transform: rotate(45deg) !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					transform: rotate(-45deg) !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close:focus-visible {
					outline: 2px solid #fff !important;
					outline-offset: 2px !important;
				}

				/* Los tres iconos forman un único grupo, con exactamente el mismo paso. */
				html body.elmercado-child-theme .site-header .site-tools {
					display: grid !important;
					grid-template-columns: repeat(3, 30px) !important;
					grid-auto-columns: 30px !important;
					grid-auto-flow: column !important;
					width: 90px !important;
					min-width: 90px !important;
					height: 40px !important;
					gap: 0 !important;
					align-items: center !important;
					justify-items: center !important;
					justify-content: end !important;
				}
				html body.elmercadodeorigen-child-theme .site-header .site-tools > *,
				html body.elmercado-child-theme .site-header .site-tools > *,
				html body.elmercado-child-theme .site-header .site-tools > * > a {
					position: static !important;
					display: grid !important;
					width: 30px !important;
					min-width: 30px !important;
					max-width: 30px !important;
					height: 40px !important;
					min-height: 40px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					justify-self: center !important;
					text-align: center !important;
					line-height: 1 !important;
					transform: none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools :is(
					.header-search-icon,
					.search-icon,
					.site-search-toggle,
					a.tools-icon,
					.my-account,
					.shopping-cart,
					.shopping-bag-button,
					.cart-contents,
					svg,
					i,
					.woostify-svg-icon
				) {
					margin: 0 !important;
					padding: 0 !important;
					inset: auto !important;
					transform: none !important;
					text-align: center !important;
				}

				/* Ritmo vertical compacto pero legible en móvil y tablet. */
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store {
					margin-top: 16px !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .tab_area {
					margin-top: 14px !important;
					margin-bottom: 0 !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					margin-top: 0 !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-sorting-normalized {
					margin: 0 !important;
					padding-top: 0 !important;
			}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					margin: 0 0 14px !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products {
					margin-top: 0 !important;
					padding-top: 0 !important;
				}
			}

			@media (max-width: 600px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) 132px !important;
					align-items: center !important;
					justify-content: stretch !important;
					gap: 8px !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					grid-column: 1 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 100% !important;
					min-width: 0 !important;
					min-height: 44px !important;
					align-items: center !important;
					font-size: 11px !important;
					line-height: 1.25 !important;
					white-space: normal !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
					grid-column: 2 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 132px !important;
					min-width: 132px !important;
					min-height: 44px !important;
					align-items: center !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					height: 44px !important;
					min-height: 44px !important;
					font-size: 11px !important;
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
		<script id="elmercado-vendor-toolbar-rhythm-final">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;

			let frame = 0;
			const sync = () => {
				if (frame) cancelAnimationFrame(frame);
				frame = requestAnimationFrame(() => {
					frame = 0;
					const toolbar = store.querySelector('.elmercado-vendor-toolbar');
					const tabs = store.querySelector('.tab_links');
					if (!toolbar || !tabs) return;

					toolbar.style.setProperty('transform', 'none', 'important');
					toolbar.style.setProperty('margin-bottom', '14px', 'important');
					if (!window.matchMedia('(max-width: 991px)').matches) return;

					requestAnimationFrame(() => {
						const tabRect = tabs.getBoundingClientRect();
						const toolbarRect = toolbar.getBoundingClientRect();
						const gap = Math.round(toolbarRect.top - tabRect.bottom);
						const shift = Math.max(0, 16 - gap);
						toolbar.style.setProperty('transform', `translateY(${shift}px)`, 'important');
						toolbar.style.setProperty('margin-bottom', `${14 + shift}px`, 'important');
						toolbar.dataset.elmercadoTabGap = String(gap + shift);
					});
				});
			};

			sync();
			setTimeout(sync, 350);
			setTimeout(sync, 1000);
			window.addEventListener('load', sync, { once: true });
			window.addEventListener('resize', sync, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
