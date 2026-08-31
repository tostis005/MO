<?php
/**
 * Plugin Name: MDO - Product detail layout 0.10.269
 * Description: Stable, responsive WooCommerce product layout with a centered purchase grid and continuous editorial content.
 * Version: 0.10.269
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<style id="mdo-product-detail-layout-010269">
			/* ---------------------------------------------------------
			 * Shared page geometry.
			 * --------------------------------------------------------- */
			html body.elmercado-child-theme.single-product .content-top > .woostify-container,
			html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container,
			html body.elmercado-child-theme.single-product div.product > .woostify-container {
				width: calc(100% - 32px) !important;
				max-width: 1240px !important;
				margin-left: auto !important;
				margin-right: auto !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
				box-sizing: border-box !important;
			}

			/* ---------------------------------------------------------
			 * Breadcrumb / previous-next.
			 * --------------------------------------------------------- */
			html body.elmercado-child-theme.single-product .content-top {
				padding: 10px 0 4px !important;
			}
			html body.elmercado-child-theme.single-product .content-top > .woostify-container {
				align-items: center !important;
				gap: 8px 16px !important;
			}
			html body.elmercado-child-theme.single-product .content-top .woocommerce {
				min-width: 0 !important;
				margin: 0 !important;
			}
			html body.elmercado-child-theme.single-product .wc-breadcrumb,
			html body.elmercado-child-theme.single-product .woocommerce-breadcrumb,
			html body.elmercado-child-theme.single-product .woostify-breadcrumb {
				margin: 0 !important;
				padding: 0 !important;
				color: #68736e !important;
				font-size: 12px !important;
				line-height: 1.45 !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation {
				gap: 6px !important;
				margin: 0 !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-item {
				margin: 0 !important;
				padding: 0 !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-item + .product-nav-item::before,
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-item-content {
				display: none !important;
				content: none !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-item-text {
				display: inline-flex !important;
				min-height: 30px !important;
				align-items: center !important;
				gap: 4px !important;
				padding: 5px 9px !important;
				background: transparent !important;
				border: 1px solid rgba(13, 33, 27, .12) !important;
				border-radius: 999px !important;
				color: #53615b !important;
				font-size: 11px !important;
				font-weight: 700 !important;
				line-height: 1 !important;
				text-transform: none !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-icon {
				margin: 0 !important;
			}
			html body.elmercado-child-theme.single-product .woostify-product-navigation .product-nav-icon svg {
				width: 13px !important;
				height: 13px !important;
			}

			/* ---------------------------------------------------------
			 * Purchase area: use a grid instead of nested percentage widths.
			 * --------------------------------------------------------- */
			html body.elmercado-child-theme.single-product div.product .product-page-container {
				padding: 18px 0 22px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container {
				display: grid !important;
				grid-template-columns: minmax(0, 1.08fr) minmax(420px, .92fr) !important;
				align-items: start !important;
				gap: 42px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container::before,
			html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container::after {
				display: none !important;
				content: none !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-summary {
				float: none !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				margin: 0 !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-summary > div.summary.entry-summary,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-summary .summary.entry-summary {
				position: static !important;
				top: auto !important;
				float: none !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-gallery img,
			html body.elmercado-child-theme.single-product div.product .woocommerce-product-gallery img,
			html body.elmercado-child-theme.single-product div.product .product-images img {
				border-radius: 12px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-thumbnail-images img,
			html body.elmercado-child-theme.single-product div.product .flex-control-thumbs img {
				border-radius: 8px !important;
			}

			/* Sales hierarchy. */
			html body.elmercado-child-theme.single-product div.product .product-summary .summary.entry-summary h1.product_title {
				width: 100% !important;
				max-width: none !important;
				margin: 0 0 10px !important;
				font-size: clamp(32px, 3vw, 42px) !important;
				font-weight: 600 !important;
				letter-spacing: -0.035em !important;
				line-height: 1.08 !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary .summary.entry-summary p.price,
			html body.elmercado-child-theme.single-product div.product .product-summary .summary.entry-summary span.price {
				margin: 0 0 13px !important;
				font-size: clamp(24px, 2vw, 28px) !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary .woocommerce-product-details__short-description {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #4b5953 !important;
				font-size: 15px !important;
				line-height: 1.62 !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary .woocommerce-product-details__short-description p {
				margin: 0 0 9px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary .woocommerce-product-details__short-description p:last-child {
				margin-bottom: 0 !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary form.cart {
				margin-top: 17px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary form.cart table.variations {
				margin-bottom: 11px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary form.cart table.variations tr {
				margin-bottom: 9px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary form.cart table.variations label {
				font-size: 11px !important;
				font-weight: 800 !important;
				letter-spacing: .055em !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary form.cart select {
				min-height: 42px !important;
				font-size: 14px !important;
			}
			html body.elmercado-child-theme.single-product div.product .product-summary .product_meta {
				margin-top: 16px !important;
				padding-top: 13px !important;
				border-top: 1px solid rgba(13, 33, 27, .09) !important;
				color: #68736e !important;
				font-size: 11px !important;
				line-height: 1.5 !important;
			}

			/* ---------------------------------------------------------
			 * After-summary container owns description and related products.
			 * This prevents percentage widths from being applied a second time.
			 * --------------------------------------------------------- */
			html body.elmercado-child-theme.single-product div.product > .woostify-container {
				padding-top: 0 !important;
				padding-bottom: 0 !important;
			}
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
				clear: both !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 28px 0 24px !important;
				background: transparent !important;
				border: 0 !important;
				border-top: 1px solid rgba(13, 33, 27, .10) !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				box-sizing: border-box !important;
			}
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs #tab-description,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs .woocommerce-Tabs-panel--description {
				display: block !important;
				width: 100% !important;
				max-width: 1040px !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #293932 !important;
				font-size: 16px !important;
				line-height: 1.72 !important;
			}
			html body.elmercado-child-theme.single-product div.product #tab-description > :first-child {
				margin-top: 0 !important;
			}
			html body.elmercado-child-theme.single-product div.product #tab-description h2 {
				margin: 28px 0 9px !important;
				font-size: clamp(24px, 2vw, 29px) !important;
				font-weight: 650 !important;
				letter-spacing: -0.025em !important;
				line-height: 1.2 !important;
			}
			html body.elmercado-child-theme.single-product div.product #tab-description h3 {
				margin: 22px 0 7px !important;
				font-size: clamp(19px, 1.5vw, 22px) !important;
				line-height: 1.3 !important;
			}
			html body.elmercado-child-theme.single-product div.product #tab-description p,
			html body.elmercado-child-theme.single-product div.product #tab-description ul,
			html body.elmercado-child-theme.single-product div.product #tab-description ol {
				margin-top: 0 !important;
				margin-bottom: 13px !important;
			}

			html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .related,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 22px 0 34px !important;
				border-top: 1px solid rgba(13, 33, 27, .10) !important;
				box-sizing: border-box !important;
			}
			html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products > h2,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .related > h2,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells > h2 {
				margin: 0 0 17px !important;
				padding: 0 !important;
				color: #0d211b !important;
				font-size: clamp(24px, 2vw, 29px) !important;
				font-weight: 650 !important;
				letter-spacing: -0.025em !important;
				line-height: 1.2 !important;
				text-align: left !important;
			}
			html body.elmercado-child-theme.single-product div.product .related ul.products,
			html body.elmercado-child-theme.single-product div.product .up-sells ul.products {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.single-product .content-top .woocommerce {
					flex: 0 0 100% !important;
				}
				html body.elmercado-child-theme.single-product .woostify-product-navigation {
					width: 100% !important;
					justify-content: space-between !important;
				}
				html body.elmercado-child-theme.single-product div.product .product-page-container {
					padding: 12px 0 20px !important;
				}
				html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container {
					display: block !important;
				}
				html body.elmercado-child-theme.single-product div.product .product-page-container .product-summary {
					margin-top: 22px !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.single-product .content-top > .woostify-container,
				html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container,
				html body.elmercado-child-theme.single-product div.product > .woostify-container {
					width: calc(100% - 30px) !important;
				}
				html body.elmercado-child-theme.single-product div.product .product-summary .summary.entry-summary h1.product_title {
					font-size: clamp(29px, 8.5vw, 35px) !important;
					line-height: 1.08 !important;
				}
				html body.elmercado-child-theme.single-product div.product .product-summary .woocommerce-product-details__short-description {
					font-size: 14px !important;
					line-height: 1.62 !important;
				}
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
					padding: 21px 0 19px !important;
				}
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs #tab-description,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs .woocommerce-Tabs-panel--description {
					max-width: none !important;
					font-size: 15px !important;
					line-height: 1.68 !important;
				}
				html body.elmercado-child-theme.single-product div.product #tab-description h2,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products > h2,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .related > h2,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells > h2 {
					font-size: 23px !important;
				}
				html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .related,
				html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells {
					padding: 20px 0 28px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
