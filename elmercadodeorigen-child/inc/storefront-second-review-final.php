<?php
/**
 * Segunda revisión visual: cabecera móvil, toolbar de catálogo, filtros y
 * navegación de tiendas de productores.
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
		<style id="elmercado-storefront-second-review-final">
			body.elmercado-child-theme .site-header .site-branding .site-title .site-title,
			body.elmercado-child-theme .site-header .site-branding > .site-title ~ .site-title {
				display: none !important;
			}

			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: 36px minmax(0, 1fr) auto !important;
					align-items: center !important;
					column-gap: 8px !important;
					width: 100% !important;
					height: 60px !important;
					min-height: 60px !important;
					padding: 0 14px !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					grid-column: 1 !important;
					display: flex !important;
					width: 36px !important;
					height: 40px !important;
					min-width: 36px !important;
					align-items: center !important;
					justify-content: flex-start !important;
					margin: 0 !important;
					padding: 0 !important;
					overflow: visible !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::after,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::after {
					width: 20px !important;
					max-width: 20px !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					grid-column: 2 !important;
					min-width: 0 !important;
					width: auto !important;
					max-width: 100% !important;
					margin: 0 !important;
					overflow: hidden !important;
				}
				body.elmercado-child-theme .site-header .site-branding .site-title {
					min-width: 0 !important;
					max-width: 100% !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-branding .site-title > a {
					display: block !important;
					max-width: 100% !important;
					overflow: hidden !important;
					font-size: 12px !important;
					font-weight: 700 !important;
					line-height: 1.15 !important;
					text-overflow: ellipsis !important;
					white-space: nowrap !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					grid-column: 3 !important;
					display: flex !important;
					width: auto !important;
					height: 44px !important;
					min-width: 0 !important;
					align-items: center !important;
					justify-content: flex-end !important;
					gap: 2px !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-tools > *,
				body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,.my-account,.shopping-cart,.shopping-bag-button) {
					min-width: 36px !important;
					width: 36px !important;
					max-width: 36px !important;
					height: 40px !important;
					margin: 0 !important;
					padding: 0 !important;
				}
			}

			@media (max-width: 359px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					grid-template-columns: 32px minmax(0, 1fr) auto !important;
					column-gap: 5px !important;
					padding-inline: 10px !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					width: 32px !important;
					min-width: 32px !important;
				}
				body.elmercado-child-theme .site-header .site-branding .site-title > a {
					font-size: 11px !important;
				}
				body.elmercado-child-theme .site-header .site-tools > *,
				body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,.my-account,.shopping-cart,.shopping-bag-button) {
					min-width: 33px !important;
					width: 33px !important;
					max-width: 33px !important;
				}
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) minmax(210px, 270px) !important;
				align-items: center !important;
				gap: 14px !important;
				width: 100% !important;
				min-height: 72px !important;
				margin: 0 0 24px !important;
				padding: 12px 14px !important;
				border: 1px solid rgba(23, 63, 50, .12) !important;
				border-radius: 16px !important;
				background: #fff !important;
				box-shadow: 0 8px 24px rgba(17, 42, 34, .055) !important;
				float: none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting .woostify-toolbar-left,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				grid-column: 1 !important;
				grid-row: 1 !important;
				min-width: 0 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting .woocommerce-ordering,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				grid-column: 2 !important;
				grid-row: 1 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting :is(.woostify-toolbar-left,.woocommerce-result-count,.woocommerce-ordering),
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar :is(.woocommerce-result-count,.woocommerce-ordering) {
				display: flex !important;
				min-width: 0 !important;
				min-height: 46px !important;
				align-items: center !important;
				justify-content: flex-start !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				float: none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-result-count,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				color: #65736d !important;
				font-size: 12px !important;
				font-weight: 650 !important;
				line-height: 1.35 !important;
				text-align: left !important;
				white-space: normal !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-ordering,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				width: 100% !important;
				max-width: none !important;
				outline: 0 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-ordering select,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
				box-sizing: border-box !important;
				display: block !important;
				width: 100% !important;
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
				padding: 0 36px 0 14px !important;
				border: 1px solid rgba(23, 63, 50, .22) !important;
				border-radius: 12px !important;
				outline: 0 !important;
				background-color: #fff !important;
				box-shadow: none !important;
				color: #173f32 !important;
				font-size: 13px !important;
				font-weight: 650 !important;
				line-height: 1.2 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-ordering select:focus,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select:focus {
				border-color: #2f7d5d !important;
				box-shadow: 0 0 0 3px rgba(47, 125, 93, .12) !important;
			}

			@media (max-width: 600px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					grid-template-columns: minmax(0, 1fr) 165px !important;
					gap: 10px !important;
					min-height: 68px !important;
					margin-bottom: 20px !important;
					padding: 10px 12px !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting :is(.woostify-toolbar-left,.woocommerce-result-count,.woocommerce-ordering),
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar :is(.woocommerce-result-count,.woocommerce-ordering),
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-ordering select,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					height: 44px !important;
					min-height: 44px !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-result-count,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					font-size: 11px !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woocommerce-ordering select,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					font-size: 11.5px !important;
					padding-inline: 11px 30px !important;
				}
			}

			@media (max-width: 339px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					grid-template-columns: minmax(0, 1fr) !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting .woocommerce-ordering,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
					grid-column: 1 !important;
					grid-row: 2 !important;
				}
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget-title,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widgettitle {
				display: block !important;
				margin: 0 0 10px !important;
				padding: 9px 10px !important;
				border: 1px solid rgba(23, 63, 50, .12) !important;
				border-radius: 10px !important;
				background: #edf4ef !important;
				color: #173f32 !important;
				font-size: 11px !important;
				font-weight: 850 !important;
				letter-spacing: .09em !important;
				line-height: 1.25 !important;
				text-transform: uppercase !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget ul {
				margin-top: 2px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget li > a {
				display: flex !important;
				min-height: 38px !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 8px !important;
				padding: 7px 3px !important;
				color: #173f32 !important;
				font-size: 14px !important;
				font-weight: 650 !important;
				line-height: 1.3 !important;
				text-decoration: none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget li > a::after {
				content: "›" !important;
				flex: 0 0 auto !important;
				color: #2f7d5d !important;
				font-size: 18px !important;
				font-weight: 700 !important;
				line-height: 1 !important;
				opacity: .65 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget li > a:hover,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .widget-area .widget li > a:focus-visible {
				color: #2f7d5d !important;
				text-decoration: underline !important;
				text-underline-offset: 3px !important;
			}

			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .tab_links li[hidden] {
				display: none !important;
			}
			@media (max-width: 600px) {
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .tab_area .tab_links {
					display: grid !important;
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
					gap: 8px !important;
				}
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme #ht-ctc-chat,
				body.elmercado-child-theme .ht-ctc-chat {
					left: 12px !important;
					right: auto !important;
					bottom: calc(18px + env(safe-area-inset-bottom, 0px)) !important;
					z-index: 20 !important;
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
		<script id="elmercado-storefront-second-review-final-js">
		(() => {
			'use strict';
			const normalize = (value) => (value || '')
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '')
				.trim()
				.toLowerCase();

			document.querySelectorAll('#wcfmmp-store .tab_links li').forEach((item) => {
				const link = item.querySelector('a');
				const label = normalize(item.textContent);
				const href = normalize(link?.getAttribute('href'));
				if (label.includes('politic') || href.includes('polic') || href.includes('policy')) {
					item.remove();
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
