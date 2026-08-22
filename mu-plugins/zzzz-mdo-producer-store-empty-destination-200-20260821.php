<?php
/**
 * Plugin Name: MDO Producer Store Empty Destination 200
 * Description: Keeps a producer storefront publicly reachable when the selected shipping destination leaves that producer with zero products.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_ps_empty_destination_is_blocked_20260821(): bool {
	if ( ! function_exists( 'mdo_ps_safe_is_store_20260821' ) || ! mdo_ps_safe_is_store_20260821() ) {
		return false;
	}
	if ( ! function_exists( 'mdo_ps_safe_vendor_id_20260821' ) || ! function_exists( 'mdo_ps_safe_destination_20260821' ) ) {
		return false;
	}
	if ( ! class_exists( 'MDO_Shipping_Destinations' ) ) {
		return false;
	}

	$vendor_id = absint( mdo_ps_safe_vendor_id_20260821() );
	if ( $vendor_id <= 0 ) {
		return false;
	}
	$destination = mdo_ps_safe_destination_20260821();
	return ! MDO_Shipping_Destinations::vendor_can_ship_to(
		$vendor_id,
		(string) ( $destination['country'] ?? 'ES' ),
		(string) ( $destination['postcode'] ?? '' )
	);
}

function mdo_ps_empty_destination_keep_store_200_20260821(): void {
	if ( is_admin() || wp_doing_ajax() || ! mdo_ps_empty_destination_is_blocked_20260821() ) {
		return;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}

	status_header( 200 );
}

/* Clear WordPress' empty-main-query 404 before canonical/template handling. */
add_action( 'wp', 'mdo_ps_empty_destination_keep_store_200_20260821', PHP_INT_MAX );

/* Reassert 200 after plugins that may derive status from an empty catalogue. */
add_action( 'template_redirect', 'mdo_ps_empty_destination_keep_store_200_20260821', PHP_INT_MAX );
