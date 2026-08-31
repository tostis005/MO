<?php
/**
 * Plugin Name: MDO - Product detail layout 0.10.267
 * Description: Refines the WooCommerce single-product layout for clearer hierarchy, wider editorial content and tighter responsive spacing.
 * Version: 0.10.267
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
		<style id="mdo-product-detail-layout-010267">
			/* ---------------------------------------------------------
			 * Product header: breadcrumb + compact previous/next control.
			 * --------------------------------------------------------- */
			body.single-product .content-top {
				padding: 16px 0 6px !important;
			}

			body.single-product .content-top > .woostify-container {
				align-items: center !important;
				gap: 0.75rem 1rem;
			}

			body.single-product .content-top .woocommerce {
				min-width: 0;
				margin: 0 !important;
			}

			body.single-product .wc-breadcrumb,
			body.single-product .woocommerce-breadcrumb,
			body.single-product .woostify-breadcrumb {
				margin: 0 !important;
				padding: 0 !important;
				color: #65716b;
				font-size: 0.76rem;
				line-height: 1.45;
			}

			body.single-product .woostify-product-navigation {
				gap: 0.4rem;
				margin: 0 !important;
			}

			body.single-product .woostify-product-navigation .product-nav-item {
				padding: 0 !important;
			}

			body.single-product .woostify-product-navigation .product-nav-item + .product-nav-item {
				margin-left: 0 !important;
			}

			body.single-product .woostify-product-navigation .product-nav-item + .product-nav-item::before {
				display: none !important;
				content: none !important;
			}

			body.single-product .woostify-product-navigation .product-nav-item-text {
				display: inline-flex !important;
				min-height: 32px;
				align-items: center;
				gap: 0.3rem;
				padding: 0.38rem 0.62rem !important;
				background: #f7f5ef;
				border: 1px solid rgba(13, 33, 27, 0.1);
				border-radius: 999px;
				color: #405049;
				font-size: 0.68rem;
				font-weight: 760;
				letter-spacing: 0.02em;
				line-height: 1;
				text-decoration: none;
				text-transform: none !important;
			}

			body.single-product .woostify-product-navigation .product-nav-item-text:hover {
				background: #eef3ef;
				border-color: rgba(23, 63, 50, 0.22);
				color: #173f32;
			}

			body.single-product .woostify-product-navigation .product-nav-icon {
				margin: 0 !important;
			}

			body.single-product .woostify-product-navigation .product-nav-icon svg {
				width: 14px;
				height: 14px;
			}

			/* The large hover preview is visually noisy on a sales page. */
			body.single-product .woostify-product-navigation .product-nav-item-content {
				display: none !important;
			}

			/* ---------------------------------------------------------
			 * Main purchase area.
			 * --------------------------------------------------------- */
			body.single-product .product-page-container {
				padding: 18px 0 26px !important;
			}

			body.single-product .product-page-container > .woostify-container,
			body.single-product .woocommerce-tabs,
			body.single-product .related,
			body.single-product .up-sells {
				width: min(100%, 1240px);
			}

			body.single-product .product-page-container > .woostify-container {
				padding-inline: clamp(16px, 2.5vw, 30px) !important;
			}

			body.single-product div.product {
				padding-top: 0 !important;
			}

			body.single-product .product-gallery,
			body.single-product .product-summary,
			body.single-product div.product .woocommerce-product-gallery,
			body.single-product div.product .summary {
				margin-bottom: 0 !important;
			}

			body.single-product div.product .summary {
				position: static !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}

			body.single-product .product-gallery img,
			body.single-product .woocommerce-product-gallery img,
			body.single-product .product-images img {
				border-radius: 14px;
			}

			body.single-product .product-thumbnail-images img,
			body.single-product .flex-control-thumbs img {
				border-radius: 9px;
			}

			body.single-product .product_title {
				margin: 0 0 0.65rem !important;
				font-size: clamp(2rem, 3vw, 3.1rem) !important;
				letter-spacing: -0.035em;
				line-height: 1.06;
			}

			body.single-product div.product p.price,
			body.single-product div.product span.price,
			body.single-product .product-summary .price {
				margin-bottom: 0.85rem !important;
				font-size: clamp(1.35rem, 2vw, 1.72rem) !important;
				line-height: 1.2;
			}

			body.single-product .woocommerce-product-details__short-description {
				margin: 0 !important;
				padding: 0 !important;
				color: #4f5d57 !important;
				font-size: 0.98rem;
				line-height: 1.68;
			}

			body.single-product .woocommerce-product-details__short-description p {
				margin-bottom: 0.65rem;
			}

			body.single-product .woocommerce-product-details__short-description p:last-child {
				margin-bottom: 0;
			}

			body.single-product form.cart {
				margin-top: 1.15rem !important;
			}

			body.single-product .product_meta {
				margin-top: 1.15rem !important;
				padding-top: 0.95rem !important;
				border-top: 1px solid rgba(13, 33, 27, 0.09) !important;
				font-size: 0.73rem !important;
				line-height: 1.55;
			}

			/* ---------------------------------------------------------
			 * Long description: continuous editorial section, not a card.
			 * Woostify defaults the panel to only 770px; remove that limit.
			 * --------------------------------------------------------- */
			body.single-product .woocommerce-tabs {
				clear: both;
				max-width: 1240px !important;
				margin: 0 auto !important;
				padding: clamp(1.55rem, 3vw, 2.3rem) clamp(16px, 2.5vw, 30px) clamp(1.8rem, 3.5vw, 2.8rem) !important;
				background: transparent !important;
				border: 0 !important;
				border-top: 1px solid rgba(13, 33, 27, 0.1) !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}

			body.single-product .woocommerce-tabs .woocommerce-Tabs-panel,
			body.single-product .woocommerce-tabs #tab-description,
			body.single-product #tab-description {
				width: min(100%, 1080px) !important;
				max-width: 1080px !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #27372f;
				font-size: 1rem;
				line-height: 1.74;
			}

			body.single-product #tab-description > :first-child {
				margin-top: 0 !important;
			}

			body.single-product #tab-description h2 {
				margin: 2rem 0 0.75rem;
				font-size: clamp(1.45rem, 2.1vw, 1.85rem);
				letter-spacing: -0.022em;
				line-height: 1.2;
			}

			body.single-product #tab-description h3 {
				margin: 1.6rem 0 0.55rem;
				font-size: clamp(1.15rem, 1.7vw, 1.35rem);
				line-height: 1.3;
			}

			body.single-product #tab-description p,
			body.single-product #tab-description ul,
			body.single-product #tab-description ol {
				margin-top: 0;
				margin-bottom: 0.95rem;
			}

			body.single-product #tab-description li + li {
				margin-top: 0.28rem;
			}

			/* ---------------------------------------------------------
			 * Related products: left-aligned and close to the description.
			 * --------------------------------------------------------- */
			body.single-product .related,
			body.single-product .up-sells {
				max-width: 1240px !important;
				margin: 0 auto !important;
				padding: 1.45rem clamp(16px, 2.5vw, 30px) 2.4rem !important;
				border-top: 1px solid rgba(13, 33, 27, 0.1) !important;
			}

			body.single-product .related > h2,
			body.single-product .up-sells > h2 {
				margin: 0 0 1.15rem !important;
				padding: 0 !important;
				color: #0d211b;
				font-size: clamp(1.45rem, 2.1vw, 1.85rem) !important;
				font-weight: 760 !important;
				letter-spacing: -0.02em;
				line-height: 1.2;
				text-align: left !important;
			}

			body.single-product .related ul.products,
			body.single-product .up-sells ul.products {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
			}

			@media (min-width: 992px) {
				body.single-product .content-top .woocommerce {
					flex: 1 1 auto !important;
				}

				body.single-product .woocommerce + .woostify-product-navigation {
					flex: 0 0 auto !important;
					justify-content: flex-end !important;
				}

				body.single-product .product-gallery,
				body.single-product div.product .woocommerce-product-gallery {
					width: 54% !important;
				}

				body.single-product .product-summary,
				body.single-product div.product .summary {
					width: calc(46% - 32px) !important;
					margin-left: 32px !important;
				}
			}

			@media (max-width: 991px) {
				body.single-product .content-top {
					padding: 11px 0 5px !important;
				}

				body.single-product .content-top .woocommerce {
					flex: 0 0 100% !important;
				}

				body.single-product .woostify-product-navigation {
					width: 100%;
					justify-content: space-between !important;
				}

				body.single-product .product-page-container {
					padding: 12px 0 20px !important;
				}

				body.single-product .product-gallery,
				body.single-product .product-summary,
				body.single-product div.product .woocommerce-product-gallery,
				body.single-product div.product .summary {
					float: none !important;
					width: 100% !important;
					margin-left: 0 !important;
				}

				body.single-product .product-summary,
				body.single-product div.product .summary {
					margin-top: 1.35rem !important;
				}

				body.single-product .woocommerce-tabs {
					padding-top: 1.35rem !important;
					padding-bottom: 1.7rem !important;
				}

				body.single-product .woocommerce-tabs .woocommerce-Tabs-panel,
				body.single-product .woocommerce-tabs #tab-description,
				body.single-product #tab-description {
					width: 100% !important;
					max-width: none !important;
				}
			}

			@media (max-width: 767px) {
				body.single-product .content-top > .woostify-container,
				body.single-product .product-page-container > .woostify-container {
					padding-inline: 15px !important;
				}

				body.single-product .wc-breadcrumb,
				body.single-product .woocommerce-breadcrumb,
				body.single-product .woostify-breadcrumb {
					font-size: 0.7rem;
				}

				body.single-product .woostify-product-navigation .product-nav-item-text {
					min-height: 30px;
					padding: 0.35rem 0.52rem !important;
					font-size: 0.64rem;
				}

				body.single-product .product_title {
					font-size: clamp(1.75rem, 7.4vw, 2.35rem) !important;
				}

				body.single-product .woocommerce-product-details__short-description {
					font-size: 0.94rem;
					line-height: 1.62;
				}

				body.single-product .woocommerce-tabs {
					padding-inline: 15px !important;
				}

				body.single-product #tab-description {
					font-size: 0.96rem;
					line-height: 1.68;
				}

				body.single-product #tab-description h2 {
					margin-top: 1.65rem;
					font-size: 1.42rem;
				}

				body.single-product .related,
				body.single-product .up-sells {
					padding: 1.25rem 15px 1.8rem !important;
				}

				body.single-product .related > h2,
				body.single-product .up-sells > h2 {
					margin-bottom: 0.9rem !important;
					font-size: 1.42rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
