<?php
/**
 * Plugin Name: MDO - Product detail refinement 0.10.271
 * Description: Removes the gap below product navigation and gives only the long description a white background.
 * Version: 0.10.271
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
		<style id="mdo-product-detail-refinement-010271">
			/* Remove the stacked top spacing between previous/next and the product. */
			html body.single-product .content-top {
				margin-bottom: 0 !important;
				padding-bottom: 0 !important;
			}

			html body.single-product div.product {
				padding-top: 0 !important;
			}

			html body.single-product div.product .product-page-container {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/* White background only for the long description. */
			html body.single-product .woocommerce-tabs {
				background-color: #fff !important;
			}

			/* Related products keep their existing page background. */
			html body.single-product section.related.products,
			html body.single-product .related,
			html body.single-product .up-sells {
				background-color: transparent !important;
			}
		</style>
		<?php
	},
	1000
);
