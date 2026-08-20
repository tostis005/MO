<?php
/**
 * Plugin Name: MDO Catalog Destination Modal Footer
 * Description: Renders the canonical destination dialog in the page footer so it is never trapped inside Woostify's hidden responsive toolbar.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! function_exists( 'mdo_catalog_default_spain_render_20260820' ) ) {
			return;
		}

		/* The original hook places both trigger and dialog inside Woostify's loop toolbar. */
		remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );

		/* Render once as a direct footer child, before the existing destination JS. */
		add_action( 'wp_footer', 'mdo_catalog_default_spain_render_20260820', 5 );
	},
	PHP_INT_MAX
);
