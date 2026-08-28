<?php
/**
 * Plugin Name: MDO Catalog Top Controls Parity
 * Description: Compatibility helpers only. Catalogue presentation is owned by the CSS-only final layers.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_parity_is_store_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	return (bool) get_query_var( 'store' );
}

function mdo_catalog_top_controls_parity_is_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	return mdo_catalog_top_controls_parity_is_store_20260824();
}

/* No frontend output by design. The former implementation mounted and moved
 * catalogue controls in the browser and watched WCFM DOM changes. That runtime
 * presentation ownership has been retired; geometry is CSS-only. */
function mdo_catalog_top_controls_parity_output_20260824(): void {
	return;
}
