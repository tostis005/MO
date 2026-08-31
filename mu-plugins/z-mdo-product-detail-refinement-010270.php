<?php
/**
 * Plugin Name: MDO - Product detail refinement 0.10.270
 * Description: Tightens the previous/next-to-product spacing and gives only the long description a clean white background.
 * Version: 0.10.270
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
		<style id="mdo-product-detail-refinement-010270">
			/* Bring the product immediately after the compact previous/next navigation. */
			html body.elmercado-child-theme.single-product .content-top {
				margin: 0 !important;
				padding-bottom: 0 !important;
			}

			html body.elmercado-child-theme.single-product .content-top > .woostify-container {
				margin-bottom: 0 !important;
				padding-bottom: 0 !important;
			}

			html body.elmercado-child-theme.single-product div.product .product-page-container {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/* White background only for the long description section. */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
				background: #fff !important;
			}

			/* Related products deliberately keep the page background. */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .related,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells {
				background: transparent !important;
			}
		</style>
		<?php
	},
	1000
);
