<?php
/**
 * Final cross-page header and commerce layout consistency.
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
		<style id="elmercado-layout-consistency-096">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .site-header-inner,
				body.elmercado-child-theme .site-header .header-main-inner,
				body.elmercado-child-theme .site-header .woostify-container {
					display: grid !important;
					grid-template-columns: minmax(220px,1fr) auto minmax(220px,1fr) !important;
					align-items: center !important;
					column-gap: 2rem !important;
				}
				body.elmercado-child-theme .site-header .site-branding,
				body.elmercado-child-theme .site-header .site-logo {
					grid-column: 1 !important;
					justify-self: start !important;
				}
				body.elmercado-child-theme .site-header .primary-navigation,
				body.elmercado-child-theme .site-header .main-navigation,
				body.elmercado-child-theme .site-header nav.site-navigation {
					grid-column: 2 !important;
					justify-self: center !important;
					width: auto !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .primary-navigation > ul,
				body.elmercado-child-theme .site-header .main-navigation > ul,
				body.elmercado-child-theme .site-header nav.site-navigation > ul {
					display: flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: clamp(1.35rem,2.1vw,2.35rem) !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-tools,
				body.elmercado-child-theme .site-header .header-right {
					grid-column: 3 !important;
					justify-self: end !important;
					margin: 0 !important;
				}

				body.elmercado-child-theme.post-type-archive-product .site-main ul.products,
				body.elmercado-child-theme.tax-product_cat .site-main ul.products,
				body.elmercado-child-theme.tax-product_tag .site-main ul.products {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
					gap: 1.15rem !important;
				}
				body.elmercado-child-theme.post-type-archive-product .site-main ul.products > li.product,
				body.elmercado-child-theme.tax-product_cat .site-main ul.products > li.product,
				body.elmercado-child-theme.tax-product_tag .site-main ul.products > li.product {
					float: none !important;
					clear: none !important;
					width: auto !important;
					max-width: none !important;
					margin: 0 !important;
				}
			}

			body.elmercado-child-theme .widget_price_filter .price_slider,
			body.elmercado-child-theme .price_slider_wrapper .ui-slider-horizontal {
				position: relative !important;
				height: 4px !important;
				margin-top: 1.1rem !important;
				margin-bottom: 1.35rem !important;
				border: 0 !important;
				border-radius: 999px !important;
				background: #d9e1dc !important;
			}
			body.elmercado-child-theme .widget_price_filter .ui-slider-range {
				top: 0 !important;
				height: 4px !important;
				border-radius: 999px !important;
				background: #2f7d5d !important;
			}
			body.elmercado-child-theme .widget_price_filter .ui-slider-handle {
				top: 50% !important;
				width: 16px !important;
				height: 16px !important;
				margin-top: 0 !important;
				border: 3px solid #fff !important;
				border-radius: 50% !important;
				background: #2f7d5d !important;
				box-shadow: 0 1px 5px rgba(13,33,27,.28) !important;
				transform: translate(-50%,-50%) !important;
			}
			body.elmercado-child-theme .widget_price_filter .ui-slider-handle:last-child {
				transform: translate(50%,-50%) !important;
			}

			body.wcfmmp-store-page #wcfmmp-store #tab_links_area {
				margin-bottom: 2.25rem !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-products-header + .woocommerce-notices-wrapper,
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-notices-wrapper + .store-content,
			body.wcfmmp-store-page #wcfmmp-store .products-wrapper,
			body.wcfmmp-store-page #wcfmmp-store #products {
				margin-top: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count,
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
				float: none !important;
				margin: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count {
				display: flex !important;
				align-items: center !important;
				min-height: 46px !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
				display: flex !important;
				align-items: center !important;
				justify-content: flex-end !important;
				min-height: 46px !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count:has(+ .woocommerce-ordering),
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count + .woocommerce-ordering {
				position: relative !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count:has(+ .woocommerce-ordering) {
				display: inline-flex !important;
				width: calc(100% - 290px) !important;
				vertical-align: middle !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count + .woocommerce-ordering {
				display: inline-flex !important;
				width: 280px !important;
				vertical-align: middle !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count:has(+ .woocommerce-ordering)::before {
				content: "" !important;
				position: absolute !important;
				top: -.9rem !important;
				right: -290px !important;
				bottom: -.9rem !important;
				left: -.9rem !important;
				z-index: -1 !important;
				border: 1px solid rgba(23,63,50,.1) !important;
				border-radius: 16px !important;
				background: #fff !important;
				box-shadow: 0 8px 28px rgba(13,33,27,.06) !important;
			}
			@media (max-width: 767px) {
				body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count:has(+ .woocommerce-ordering),
				body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count + .woocommerce-ordering {
					display: flex !important;
					width: 100% !important;
					justify-content: flex-start !important;
				}
				body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count:has(+ .woocommerce-ordering)::before {
					right: -.9rem !important;
					bottom: -4.3rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
