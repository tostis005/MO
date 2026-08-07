<?php
/**
 * Cross-page header and commerce layout consistency.
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
		<style id="elmercado-layout-consistency-097">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .site-header-inner,
				body.elmercado-child-theme .site-header .header-main-inner,
				body.elmercado-child-theme .site-header .woostify-container {
					display: flex !important;
					align-items: center !important;
					justify-content: flex-start !important;
					gap: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-branding,
				body.elmercado-child-theme .site-header .site-logo {
					flex: 0 0 auto !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .primary-navigation,
				body.elmercado-child-theme .site-header .main-navigation,
				body.elmercado-child-theme .site-header nav.site-navigation {
					flex: 0 1 auto !important;
					width: auto !important;
					margin: 0 0 0 clamp(2.5rem,4vw,4.75rem) !important;
				}
				body.elmercado-child-theme .site-header .primary-navigation > ul,
				body.elmercado-child-theme .site-header .main-navigation > ul,
				body.elmercado-child-theme .site-header nav.site-navigation > ul {
					display: flex !important;
					align-items: center !important;
					justify-content: flex-start !important;
					gap: clamp(1.5rem,2.2vw,2.5rem) !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-tools,
				body.elmercado-child-theme .site-header .header-right {
					flex: 0 0 auto !important;
					margin-left: auto !important;
				}

				body.elmercado-child-theme.post-type-archive-product ul.products,
				body.elmercado-child-theme.tax-product_cat ul.products,
				body.elmercado-child-theme.tax-product_tag ul.products,
				body.elmercado-child-theme.woocommerce-shop ul.products {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
					gap: 1.1rem !important;
				}
				body.elmercado-child-theme.post-type-archive-product ul.products > li.product,
				body.elmercado-child-theme.tax-product_cat ul.products > li.product,
				body.elmercado-child-theme.tax-product_tag ul.products > li.product,
				body.elmercado-child-theme.woocommerce-shop ul.products > li.product {
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
				margin: 1.25rem 8px 1.5rem !important;
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
				top: -6px !important;
				width: 16px !important;
				height: 16px !important;
				margin-left: -8px !important;
				border: 3px solid #fff !important;
				border-radius: 50% !important;
				background: #2f7d5d !important;
				box-shadow: 0 1px 5px rgba(13,33,27,.28) !important;
				transform: none !important;
			}

			/* La barra resultado/ordenación la gobierna runtime-stability-final.php.
			 * Aquí solo conservamos la separación de las pestañas del productor. */
			body.wcfmmp-store-page #wcfmmp-store #tab_links_area {
				margin: 0 0 2rem !important;
				padding: 0 !important;
				border: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count::before {
				content: none !important;
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
