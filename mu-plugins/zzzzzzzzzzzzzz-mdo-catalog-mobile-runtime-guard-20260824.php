<?php
/**
 * Plugin Name: MDO Catalog Mobile Runtime Guard
 * Description: Compatibility surface helper. Mobile catalogue geometry is now CSS-only.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_mobile_runtime_guard_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_arrow_final_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_arrow_final_surface_20260824();
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	return function_exists( 'is_shop' ) && is_shop();
}

/* Intentionally no frontend output. The previous implementation used both
 * MutationObserver and ResizeObserver plus RAF/timer retries to rewrite inline
 * widths. The CSS-only final catalogue layer now owns mobile geometry. */
function mdo_catalog_mobile_runtime_guard_output_20260824(): void {
	return;
}
