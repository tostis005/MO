<?php
/**
 * Plugin Name: MDO Retire Legacy Destination UI 2026-08-28
 * Description: Disables only duplicated destination UI renderers so the shared modal is the sole interface owner.
 * Version: 1.0.2
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_retire_legacy_destination_ui_20260828(): void {
	/* Original global destination UI; keep AJAX, cookies, ranking and filtering. */
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		remove_action( 'woocommerce_before_shop_loop', array( 'MDO_Catalog_Destination_Frontend', 'render_destination_control' ), 22 );
		remove_action( 'wp_head', array( 'MDO_Catalog_Destination_Frontend', 'render_styles' ), PHP_INT_MAX );
		remove_action( 'wp_footer', array( 'MDO_Catalog_Destination_Frontend', 'render_script' ), PHP_INT_MAX );
	}

	/* Historical default-Spain UI; keep its shipping/cache/default guards. */
	remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
	remove_action( 'wp_head', 'mdo_catalog_default_spain_styles_20260820', PHP_INT_MAX );
	remove_action( 'wp_footer', 'mdo_catalog_default_spain_script_20260820', PHP_INT_MAX );

	/* Canonical global toolbar UI. Keep its toolbar CSS/layout, retire only its
	 * duplicated destination trigger and footer modal. */
	remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_toolbar_render_destination_control_20260820', 22 );
	remove_action( 'wp_footer', 'mdo_catalog_toolbar_render_destination_modal_20260820', 5 );

	/* Historical producer UI. Keep its catalogue/shipping logic. */
	remove_action( 'woocommerce_before_shop_loop', 'mdo_ps_safe_render_trigger_20260821', 21 );
	remove_action( 'wp_footer', 'mdo_ps_safe_footer_20260821', PHP_INT_MAX );
}

/* Several historical layers register presentation callbacks late. Repeating
 * cleanup is harmless. The loop-local priority-0 cleanup is definitive for
 * triggers, and footer priority 0 removes the canonical legacy modal before its
 * priority-5 renderer can print. */
add_action( 'plugins_loaded', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp_loaded', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'template_redirect', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp_head', 'mdo_retire_legacy_destination_ui_20260828', 0 );
add_action( 'woocommerce_before_shop_loop', 'mdo_retire_legacy_destination_ui_20260828', 0 );
add_action( 'wp_footer', 'mdo_retire_legacy_destination_ui_20260828', 0 );
