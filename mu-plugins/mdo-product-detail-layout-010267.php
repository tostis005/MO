<?php
/**
 * Plugin Name: MDO - Product detail layout 0.10.268
 * Description: Provides a restrained, responsive WooCommerce single-product layout with balanced gallery/summary proportions and compact editorial spacing.
 * Version: 0.10.268
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
		<style id="mdo-product-detail-layout-010268">
			html body.single-product .content-top > .woostify-container,
			html body.single-product .product-page-container > .woostify-container,
			html body.single-product .woocommerce-tabs,
			html body.single-product .related,
			html body.single-product .up-sells {
				width: calc(100% - 32px) !important;
				max-width: 1240px !important;
				margin-left: auto !important;
				margin-right: auto !important;
				box-sizing: border-box !important;
			}

			html body.single-product .content-top {
				padding: 12px 0 4px !important;
			}
			html body.single-product .content-top > .woostify-container {
				align-items: center !important;
				gap: 8px 16px !important;
				padding: 0 !important;
			}
			html body.single-product .content-top .woocommerce {
				min-width: 0 !important;
				margin: 0 !important;
			}
			html body.single-product .wc-breadcrumb,
			html body.single-product .woocommerce-breadcrumb,
			html body.single-product .woostify-breadcrumb {
				margin: 0 !important;
				padding: 0 !important;
				color: #68736e !important;
				font-size: 12px !important;
				line-height: 1.45 !important;
			}
			html body.single-product .woostify-product-navigation {
				gap: 6px !important;
				margin: 0 !important;
			}
			html body.single-product .woostify-product-navigation .product-nav-item {
				margin: 0 !important;
				padding: 0 !important;
			}
			html body.single-product .woostify-product-navigation .product-nav-item + .product-nav-item::before,
			html body.single-product .woostify-product-navigation .product-nav-item-content {
				display: none !important;
				content: none !important;
			}
			html body.single-product .woostify-product-navigation .product-nav-item-text {
				display: inline-flex !important;
				min-height: 30px !important;
				align-items: center !important;
				gap: 4px !important;
				padding: 5px 9px !important;
				background: transparent !important;
				border: 1px solid rgba(13,33,27,.12) !important;
				border-radius: 999px !important;
				color: #53615b !important;
				font-size: 11px !important;
				font-weight: 700 !important;
				line-height: 1 !important;
				text-transform: none !important;
			}
			html body.single-product .woostify-product-navigation .product-nav-icon {
				margin: 0 !important;
			}
			html body.single-product .woostify-product-navigation .product-nav-icon svg {
				width: 13px !important;
				height: 13px !important;
			}

			html body.single-product .product-page-container {
				padding: 18px 0 30px !important;
			}
			html body.single-product .product-page-container > .woostify-container {
				padding: 0 !important;
			}
			html body.single-product div.product {
				padding-top: 0 !important;
			}
			html body.single-product .product-gallery,
			html body.single-product .product-summary,
			html body.single-product div.product .woocommerce-product-gallery,
			html body.single-product div.product .summary {
				margin-bottom: 0 !important;
			}

			/* The outer product-summary owns the desktop column width. */
			html body.single-product .product-summary > .summary,
			html body.single-product .product-summary > div.summary,
			html body.single-product .product-summary .summary.entry-summary {
				float: none !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}
			html body.single-product .product-gallery img,
			html body.single-product .woocommerce-product-gallery img,
			html body.single-product .product-images img {
				border-radius: 12px !important;
			}
			html body.single-product .product-thumbnail-images img,
			html body.single-product .flex-control-thumbs img {
				border-radius: 8px !important;
			}

			html body.single-product .product_title {
				width: 100% !important;
				max-width: none !important;
				margin: 0 0 10px !important;
				font-size: clamp(32px, 3vw, 44px) !important;
				font-weight: 600 !important;
				letter-spacing: -0.035em !important;
				line-height: 1.08 !important;
			}
			html body.single-product div.product p.price,
			html body.single-product div.product span.price,
			html body.single-product .product-summary .price {
				margin: 0 0 14px !important;
				font-size: clamp(24px, 2vw, 29px) !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
			}
			html body.single-product .woocommerce-product-details__short-description {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #4b5953 !important;
				font-size: 15px !important;
				line-height: 1.62 !important;
			}
			html body.single-product .woocommerce-product-details__short-description p {
				margin: 0 0 10px !important;
			}
			html body.single-product .woocommerce-product-details__short-description p:last-child {
				margin-bottom: 0 !important;
			}
			html body.single-product form.cart {
				margin-top: 18px !important;
			}
			html body.single-product form.cart table.variations {
				margin-bottom: 12px !important;
			}
			html body.single-product form.cart table.variations tr {
				margin-bottom: 10px !important;
			}
			html body.single-product form.cart table.variations label {
				font-size: 11px !important;
				font-weight: 800 !important;
				letter-spacing: .055em !important;
			}
			html body.single-product form.cart select {
				min-height: 42px !important;
				font-size: 14px !important;
			}
			html body.single-product .product_meta {
				margin-top: 18px !important;
				padding-top: 14px !important;
				border-top: 1px solid rgba(13,33,27,.09) !important;
				color: #68736e !important;
				font-size: 11px !important;
				line-height: 1.55 !important;
			}

			html body.single-product .woocommerce-tabs {
				clear: both !important;
				padding: 30px 0 26px !important;
				background: transparent !important;
				border: 0 !important;
				border-top: 1px solid rgba(13,33,27,.10) !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}
			html body.single-product .woocommerce-tabs .woocommerce-Tabs-panel,
			html body.single-product .woocommerce-tabs #tab-description,
			html body.single-product #tab-description {
				display: block !important;
				width: 100% !important;
				max-width: 1040px !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #293932 !important;
				font-size: 16px !important;
				line-height: 1.72 !important;
			}
			html body.single-product #tab-description > :first-child {
				margin-top: 0 !important;
			}
			html body.single-product #tab-description h2 {
				margin: 30px 0 10px !important;
				font-size: clamp(24px, 2vw, 30px) !important;
				font-weight: 650 !important;
				letter-spacing: -0.025em !important;
				line-height: 1.2 !important;
			}
			html body.single-product #tab-description h3 {
				margin: 24px 0 8px !important;
				font-size: clamp(19px, 1.5vw, 22px) !important;
				line-height: 1.3 !important;
			}
			html body.single-product #tab-description p,
			html body.single-product #tab-description ul,
			html body.single-product #tab-description ol {
				margin-top: 0 !important;
				margin-bottom: 14px !important;
			}

			html body.single-product .woocommerce-tabs + .related,
			html body.single-product section.related.products,
			html body.single-product .related,
			html body.single-product .up-sells {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
				padding: 24px 0 36px !important;
				border-top: 1px solid rgba(13,33,27,.10) !important;
			}
			html body.single-product .related > h2,
			html body.single-product .up-sells > h2 {
				margin: 0 0 18px !important;
				padding: 0 !important;
				color: #0d211b !important;
				font-size: clamp(24px, 2vw, 30px) !important;
				font-weight: 650 !important;
				letter-spacing: -0.025em !important;
				line-height: 1.2 !important;
				text-align: left !important;
			}
			html body.single-product .related ul.products,
			html body.single-product .up-sells ul.products {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
			}

			@media (min-width: 992px) {
				html body.single-product .content-top .woocommerce {
					flex: 1 1 auto !important;
				}
				html body.single-product .woocommerce + .woostify-product-navigation {
					flex: 0 0 auto !important;
					justify-content: flex-end !important;
				}
				html body.single-product .product-gallery {
					float: left !important;
					width: 54% !important;
					max-width: 54% !important;
				}
				html body.single-product .product-summary {
					float: left !important;
					width: calc(46% - 36px) !important;
					max-width: calc(46% - 36px) !important;
					margin-left: 36px !important;
				}
			}

			@media (max-width: 991px) {
				html body.single-product .content-top {
					padding-top: 9px !important;
				}
				html body.single-product .content-top .woocommerce {
					flex: 0 0 100% !important;
				}
				html body.single-product .woostify-product-navigation {
					width: 100% !important;
					justify-content: space-between !important;
				}
				html body.single-product .product-page-container {
					padding: 12px 0 24px !important;
				}
				html body.single-product .product-gallery,
				html body.single-product .product-summary {
					float: none !important;
					width: 100% !important;
					max-width: none !important;
					margin-left: 0 !important;
				}
				html body.single-product .product-summary {
					margin-top: 22px !important;
				}
				html body.single-product .woocommerce-tabs {
					padding: 24px 0 22px !important;
				}
				html body.single-product .woocommerce-tabs .woocommerce-Tabs-panel,
				html body.single-product .woocommerce-tabs #tab-description,
				html body.single-product #tab-description {
					max-width: none !important;
				}
			}

			@media (max-width: 767px) {
				html body.single-product .content-top > .woostify-container,
				html body.single-product .product-page-container > .woostify-container,
				html body.single-product .woocommerce-tabs,
				html body.single-product .related,
				html body.single-product .up-sells {
					width: calc(100% - 30px) !important;
				}
				html body.single-product .product_title {
					font-size: clamp(29px, 8.5vw, 36px) !important;
					line-height: 1.08 !important;
				}
				html body.single-product .woocommerce-product-details__short-description {
					font-size: 14px !important;
					line-height: 1.62 !important;
				}
				html body.single-product .woocommerce-tabs {
					padding: 22px 0 20px !important;
				}
				html body.single-product #tab-description {
					font-size: 15px !important;
					line-height: 1.68 !important;
				}
				html body.single-product #tab-description h2,
				html body.single-product .related > h2,
				html body.single-product .up-sells > h2 {
					font-size: 24px !important;
				}
				html body.single-product .related,
				html body.single-product .up-sells {
					padding: 22px 0 30px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
