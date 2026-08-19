<?php
/**
 * Plugin Name: MDO WCFM Shipping Zone Fallback
 * Description: Lets vendor shipping fall back to the next matching WooCommerce zone when the vendor has no enabled WCFM shipping method in a more specific overlapping zone.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the WCFM shipping mode for a vendor, or an empty string when the
 * package should not be handled by the zone fallback.
 */
function mdo_wzsf_vendor_shipping_mode( int $vendor_id ): string {
    $shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
    if ( ! is_array( $shipping ) ) {
        return '';
    }

    if ( isset( $shipping['_wcfmmp_user_shipping_enable'] ) && 'no' === $shipping['_wcfmmp_user_shipping_enable'] ) {
        return '';
    }

    return isset( $shipping['_wcfmmp_user_shipping_type'] )
        ? (string) $shipping['_wcfmmp_user_shipping_type']
        : '';
}

/**
 * Get the zone IDs where this vendor has at least one enabled WCFM method.
 *
 * A method row means the vendor explicitly configured that zone. We do not
 * inspect price/minimum conditions here: once a vendor configured a zone it
 * keeps precedence, even if the configured rate is conditionally unavailable.
 */
function mdo_wzsf_enabled_vendor_zone_ids( int $vendor_id ): array {
    static $cache = array();

    if ( isset( $cache[ $vendor_id ] ) ) {
        return $cache[ $vendor_id ];
    }

    global $wpdb;

    $table = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
    $sql   = $wpdb->prepare(
        "SELECT DISTINCT zone_id
         FROM {$table}
         WHERE vendor_id = %d
           AND is_enabled = 1",
        $vendor_id
    );

    $zone_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is derived from $wpdb->prefix; values are prepared.
    if ( ! is_array( $zone_ids ) ) {
        $zone_ids = array();
    }

    $zone_ids = array_values( array_unique( array_map( 'absint', $zone_ids ) ) );
    sort( $zone_ids, SORT_NUMERIC );

    $cache[ $vendor_id ] = $zone_ids;

    return $zone_ids;
}

/**
 * Make WooCommerce ignore overlapping zones that the current vendor has not
 * configured. WooCommerce will then continue to the next geographically
 * matching zone (for example, from MAD/TO to España Península).
 */
function mdo_wzsf_filter_zone_criteria( array $criteria, array $package, array $postcode_locations ): array {
    unset( $postcode_locations );

    $vendor_id = isset( $package['vendor_id'] ) ? absint( $package['vendor_id'] ) : 0;
    if ( ! $vendor_id || ! class_exists( 'WCFMmp_Shipping_Zone' ) || 'by_zone' !== mdo_wzsf_vendor_shipping_mode( $vendor_id ) ) {
        return $criteria;
    }

    $zone_ids = mdo_wzsf_enabled_vendor_zone_ids( $vendor_id );

    // No configured WCFM zone methods: preserve WCFM/WooCommerce's original behaviour.
    if ( empty( $zone_ids ) ) {
        return $criteria;
    }

    // Zone 0 ("locations not covered") is not stored in WooCommerce's zones
    // table. Removing it here makes the SQL return no specific zone, which is
    // exactly how WooCommerce falls through to zone 0.
    $specific_zone_ids = array_values( array_filter( $zone_ids ) );

    if ( empty( $specific_zone_ids ) ) {
        $criteria[] = 'AND 1 = 0';
        return $criteria;
    }

    $criteria[] = 'AND zones.zone_id IN (' . implode( ',', array_map( 'absint', $specific_zone_ids ) ) . ')';

    return $criteria;
}
add_filter( 'woocommerce_get_zone_criteria', 'mdo_wzsf_filter_zone_criteria', 20, 3 );

/**
 * Bust WooCommerce's per-package shipping-session cache once for this change.
 * Keeping a version marker in vendor packages also makes future revisions easy
 * to invalidate by bumping the constant.
 */
function mdo_wzsf_package_cache_version( array $packages ): array {
    foreach ( $packages as &$package ) {
        if ( ! empty( $package['vendor_id'] ) && 'by_zone' === mdo_wzsf_vendor_shipping_mode( absint( $package['vendor_id'] ) ) ) {
            $package['mdo_wzsf_version'] = '1.0.0';
        }
    }
    unset( $package );

    return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'mdo_wzsf_package_cache_version', 1000 );
