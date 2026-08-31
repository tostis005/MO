<?php
/**
 * Plugin Name: MDO - Product detail refinement 0.10.277
 * Description: Refines single-product spacing, balances gallery and purchase columns, keeps uncropped Flickity imagery, white navigation breathing space and the full-width white description band.
 * Version: 0.10.277
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
		<style id="mdo-product-detail-refinement-010277">
			/*
			 * Keep a small pause under previous/next, but make that breathing space
			 * part of the white navigation surface instead of exposing the cream canvas.
			 */
			html body.elmercado-child-theme.single-product .content-top {
				position: relative !important;
				margin-bottom: 0 !important;
				padding-bottom: 18px !important;
			}

			html body.elmercado-child-theme.single-product .content-top::after {
				content: "" !important;
				position: absolute !important;
				z-index: 0 !important;
				left: 50% !important;
				bottom: 0 !important;
				width: 100vw !important;
				height: 18px !important;
				transform: translateX(-50%) !important;
				background: #fff !important;
				pointer-events: none !important;
			}

			html body.elmercado-child-theme.single-product .content-top > * {
				position: relative;
				z-index: 1;
			}

			html body.elmercado-child-theme.single-product div.product {
				padding-top: 0 !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/*
			 * Desktop purchase proportions: give the sales column slightly more room
			 * and make photography feel premium without letting it dominate the page.
			 */
			@media (min-width: 992px) {
				html body.elmercado-child-theme.single-product div.product .product-page-container > .woostify-container {
					grid-template-columns: minmax(0, .96fr) minmax(420px, 1.04fr) !important;
					gap: 38px !important;
				}
			}

			/*
			 * Product imagery: compact, predictable frame. Flickity normally sizes
			 * its viewport to the tallest source image; constrain its whole wrapper
			 * chain to the commerce frame and fit every image inside without crop.
			 */
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images {
				height: 620px !important;
				aspect-ratio: auto !important;
				background: #fff !important;
				border-radius: 12px !important;
				overflow: hidden !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images > #product-images,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images.flickity-enabled,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images > .flickity-viewport,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images > .flickity-viewport > .flickity-slider,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images .figure,
			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images figure.image-item {
				height: 100% !important;
				max-height: 100% !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images figure.image-item > a {
				display: flex !important;
				width: 100% !important;
				height: 100% !important;
				align-items: center !important;
				justify-content: center !important;
				background: #fff !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images #product-images figure.image-item > a > img,
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

			/* Keep the vertical thumbnail rail visually connected to the image. */
			@media (min-width: 992px) {
				html body.elmercado-child-theme.single-product div.product .product-page-container .product-thumbnail-images {
					margin-right: 16px !important;
				}
			}

			/*
			 * Long description: full-bleed white section. Editorial copy remains
			 * aligned to the site's existing 1240 / 1040px content geometry.
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

			/*
			 * Single-product pages previously stacked 70px content padding and 56px
			 * outer/footer spacing. Keep one deliberate transition into the dark footer.
			 */
			html body.elmercado-child-theme.single-product #content.site-content {
				margin-bottom: 0 !important;
				padding-bottom: 28px !important;
			}

			html body.elmercado-child-theme.single-product .site-footer {
				margin-top: 0 !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.single-product .content-top {
					margin-bottom: 0 !important;
					padding-bottom: 14px !important;
				}

				html body.elmercado-child-theme.single-product .content-top::after {
					height: 14px !important;
				}

				html body.elmercado-child-theme.single-product div.product .product-page-container .product-gallery .product-images {
					height: min(450px, calc((100vw - 30px) * 1.25)) !important;
				}

				html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
					padding-top: 24px !important;
					padding-bottom: 22px !important;
				}

				html body.elmercado-child-theme.single-product #content.site-content {
					padding-bottom: 20px !important;
				}
			}
		</style>
		<?php
	},
	1000
);
