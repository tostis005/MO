<?php
/**
 * Plugin Name: MDO Catalog Top Controls Arrow Final Owner
 * Description: Compatibility surface helper. Ordering arrows and producer widths are now CSS-only.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_arrow_final_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_is_surface_20260824();
	}
	return function_exists( 'is_shop' ) && is_shop();
}

/* Intentionally no frontend output. The previous implementation used repeated
 * requestAnimationFrame/setTimeout passes and a MutationObserver on producer
 * ordering controls. The CSS-only final catalogue layer owns this styling. */
function mdo_catalog_top_controls_arrow_final_output_20260824(): void {
	return;
}
