<?php
/**
 * Plugin Name: MDO Catalog Top Controls Parity Final Owner
 * Description: Compatibility surface helper. Runtime layout ownership moved to the CSS-only final catalogue layer.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_parity_final_is_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_is_surface_20260824();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

/* Intentionally no frontend output. The previous implementation repeatedly
 * wrote inline-important styles and used a MutationObserver. Catalogue layout
 * is now owned exclusively by the CSS-only 2026-08-28 final layer. */
function mdo_catalog_top_controls_parity_final_output_20260824(): void {
	return;
}
