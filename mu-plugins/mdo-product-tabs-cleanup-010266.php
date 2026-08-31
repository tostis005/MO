<?php
/**
 * Plugin Name: MDO - Product tabs cleanup 0.10.267
 * Description: Shows the product description directly, without WooCommerce tab navigation or the automatic Description heading.
 * Version: 0.10.267
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove only WooCommerce's automatic heading above the long description.
 * Product-authored H2 headings remain visible for hierarchy, readability and SEO.
 */
add_filter(
	'woocommerce_product_description_heading',
	static function (): string {
		return '';
	},
	PHP_INT_MAX
);

/**
 * Keep the long description visible while removing the tab navigation.
 * Additional-information content remains hidden because the product page now
 * reads as a continuous commercial/editorial page rather than a tab interface.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<style id="mdo-product-tabs-cleanup-010266">
			body.single-product .woocommerce div.product .woocommerce-tabs ul.tabs,
			body.single-product div.product .woocommerce-tabs ul.tabs {
				display: none !important;
			}

			body.single-product .woocommerce-Tabs-panel--description,
			body.single-product #tab-description {
				display: block !important;
			}

			body.single-product .woocommerce-Tabs-panel--additional_information,
			body.single-product #tab-additional_information {
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
