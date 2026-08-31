<?php
/**
 * Plugin Name: MDO - Product detail refinement 0.10.272
 * Description: Removes the gap below product navigation and gives only the long description a white background.
 * Version: 0.10.272
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
		<style id="mdo-product-detail-refinement-010272">
			/* Remove stacked spacing between previous/next and the product itself. */
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

			/* White background only for the long description section. */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .woocommerce-tabs {
				background-color: #fff !important;
			}

			/* Related products deliberately keep the page background. */
			html body.elmercado-child-theme.single-product div.product > .woostify-container > section.related.products,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .related,
			html body.elmercado-child-theme.single-product div.product > .woostify-container > .up-sells {
				background-color: transparent !important;
			}
		</style>
		<?php
	},
	1000
);
