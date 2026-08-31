<?php
/**
 * Plugin Name: MDO - Product detail refinement 0.10.274
 * Description: Aligns product detail pages with the site's premium cream/white editorial system, compact uncropped gallery frames and a full-width white description band.
 * Version: 0.10.274
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<style id="mdo-product-detail-refinement-010274">
			/* Keep previous/next visually attached to the purchase area. */
			html body.elmercado-child-theme.single-product .content-top {
				margin-bottom: 0 !important;
				padding-bottom: 0 !important;
			}

			html body.elmercado-child-theme.single-product div.product {
				padding-top: 0 !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/*
			 * Product imagery: a compact commerce frame with a definite height.
			 * Every source image fills that frame with contain, never crop.
			 */
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images {
				height: clamp(580px, 50vw, 720px) !important;
				aspect-ratio: auto !important;
				background: #fff !important;
				border-radius: 12px !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images img {
				display: block !important;
				width: 100% !important;
				height: 100% !important;
				max-width: 100% !important;
				max-height: 100% !important;
				object-fit: contain !important;
				object-position: center center !important;
				background: #fff !important;
			}

			/* Slightly tighten the thumbnail rail so imagery reads as one unit. */
			@media (min-width: 992px) {
				html body.elmercado-child-theme.single-product div.product .product-page-container .product-thumbnail-images {
					margin-right: 16px !important;
				}
			}

			/*
			 * Long description: full-bleed white section, while the editorial copy
			 * remains aligned to the site's 1240 / 1040px layout system.
			 */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
				position: relative !important;
				isolation: isolate !important;
				background: #fff !important;
				border-top: 1px solid rgba(13, 33, 27, .08) !important;
				border-bottom: 1px solid rgba(13, 33, 27, .08) !important;
			}

			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs::before {
				content: "" !important;
				position: absolute !important;
				z-index: -1 !important;
				top: -1px !important;
				bottom: -1px !important;
				left: 50% !important;
				width: 100vw !important;
				transform: translateX(-50%) !important;
				background: #fff !important;
				border-top: 1px solid rgba(13, 33, 27, .08) !important;
				border-bottom: 1px solid rgba(13, 33, 27, .08) !important;
				pointer-events: none !important;
			}

			/* Related products deliberately return to the site's cream page canvas. */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .related,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells {
				position: relative !important;
				z-index: 1 !important;
				background: transparent !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images {
					height: min(450px, calc((100vw - 30px) * 1.25)) !important;
				}

				html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
					padding-top: 24px !important;
					padding-bottom: 22px !important;
				}
			}
		</style>
		<?php
	},
	1000
);
