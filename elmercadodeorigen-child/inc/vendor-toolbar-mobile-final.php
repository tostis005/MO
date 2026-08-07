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
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .close-sidebar-menu,
				html.sidebar-menu-open body.elmercado-child-theme [class*="close-sidebar"] {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
				}

				html body.elmercado-child-theme .site-header .site-tools {
					display: grid !important;
					grid-template-columns: repeat(3, 32px) !important;
					grid-auto-columns: 32px !important;
					grid-auto-flow: column !important;
					width: 96px !important;
					min-width: 96px !important;
					height: 40px !important;
					gap: 0 !important;
					align-items: center !important;
					justify-items: center !important;
					justify-content: end !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > *,
				html body.elmercado-child-theme .site-header .site-tools > * > a {
					position: static !important;
					display: grid !important;
					width: 32px !important;
					min-width: 32px !important;
					max-width: 32px !important;
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
					margin: 16px 0 14px !important;
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
