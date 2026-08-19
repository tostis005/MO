<?php
/**
 * Plugin Name: MDO Store Shipping Public Destinations Only
 * Description: Removes ambiguous WCFM shipping rows that do not map to a public destination such as a country, province, postcode or other explicit location.
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Keep only rows that can be described to customers with a real destination.
 * Country-based shipping already has its destination in the row name, so this
 * cleanup is only needed for WCFM zone-based shipping.
 */
function mdo_sst_public_destinations_only( $rows, int $vendor_id = 0, string $type = '' ) {
    unset( $vendor_id );

    if ( ! is_array( $rows ) || 'by_country' === $type ) {
        return $rows;
    }

    $clean = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $locations = isset( $row['locations'] ) && is_array( $row['locations'] )
            ? array_values( array_filter( array_map( 'trim', $row['locations'] ) ) )
            : array();

        if ( ! empty( $locations ) ) {
            $row['locations'] = $locations;
            $clean[]          = $row;
            continue;
        }

        // Preserve the one intentionally public zone label used by EMDO.
        if ( function_exists( 'mdo_sst_is_mainland_spain_row' ) && mdo_sst_is_mainland_spain_row( $row ) ) {
            $clean[] = $row;
        }
    }

    return array_values( $clean );
}
add_filter( 'mdo_store_shipping_rows', 'mdo_sst_public_destinations_only', 120, 3 );
