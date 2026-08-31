<?php
/**
 * Plugin Name: MDO - Product tabs cleanup 0.10.266
 * Description: Shows the product description directly, without WooCommerce tab navigation or the automatic Description heading.
 * Version: 0.10.266
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WooCommerce's automatic heading above the long description.
 * This applies equally to Spanish and translated product routes.
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
 * Additional-information content remains hidden because the user requested
 * the description to read as a normal continuous section rather than tabs.
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

			body.single-product .woocommerce-Tabs-panel--description > h2:first-child,
			body.single-product #tab-description > h2:first-child {
				display: none !important;
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
