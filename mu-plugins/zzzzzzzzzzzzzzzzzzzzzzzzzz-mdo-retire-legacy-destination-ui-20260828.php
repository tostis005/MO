<?php
/**
 * Plugin Name: MDO Retire Legacy Destination UI 2026-08-28
 * Description: Disables only duplicated destination UI renderers so the shared modal is the sole interface owner.
 * Version: 1.0.1
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

	/* Historical default-Spain compatibility layer. Keep its shipping/cache guards. */
	remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
	remove_action( 'wp_head', 'mdo_catalog_default_spain_styles_20260820', PHP_INT_MAX );
	remove_action( 'wp_footer', 'mdo_catalog_default_spain_script_20260820', PHP_INT_MAX );

	/* Historical producer UI. Keep its catalogue/shipping logic. */
	remove_action( 'woocommerce_before_shop_loop', 'mdo_ps_safe_render_trigger_20260821', 21 );
	remove_action( 'wp_footer', 'mdo_ps_safe_footer_20260821', PHP_INT_MAX );
}

/* Default Spain registers its replacement UI on plugins_loaded at PHP_INT_MAX.
 * Repeating cleanup at lifecycle boundaries is harmless. The loop-local cleanup
 * at priority 0 is the definitive guard: it executes immediately before any
 * legacy trigger/modal renderer at priorities 21/22 can print markup. */
add_action( 'plugins_loaded', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp_loaded', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'template_redirect', 'mdo_retire_legacy_destination_ui_20260828', PHP_INT_MAX );
add_action( 'wp_head', 'mdo_retire_legacy_destination_ui_20260828', 0 );
add_action( 'woocommerce_before_shop_loop', 'mdo_retire_legacy_destination_ui_20260828', 0 );
add_action( 'wp_footer', 'mdo_retire_legacy_destination_ui_20260828', 0 );
