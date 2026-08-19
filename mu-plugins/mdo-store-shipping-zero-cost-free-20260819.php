<?php
/**
 * Plugin Name: MDO Store Shipping Zero-Cost Normalizer
 * Description: Treats an explicit zero shipping rate as unconditional free shipping in the public producer Shipping tab.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return true when a public shipping row explicitly represents a zero cost.
 */
function mdo_sst_zcf_row_is_zero_cost( array $row ): bool {
    if ( array_key_exists( 'sort_cost', $row ) && is_numeric( $row['sort_cost'] ) ) {
        return abs( (float) $row['sort_cost'] ) < 0.0001;
    }

    foreach ( (array) ( $row['flat_costs'] ?? array() ) as $cost ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $cost ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( str_replace( array( "\xc2\xa0", ' ' ), '', $plain ) );

        // Formulas such as "0 * [qty]" are also unconditionally free.
        if ( preg_match( '/^0+(?:[\.,]0+)?(?:\*|x|×)?(?:\[qty\])?(?:€|eur)?$/iu', $plain ) ) {
            return true;
        }

        // Currency-formatted zero, e.g. 0,00 €, €0.00 or 0 EUR.
        $numeric = preg_replace( '/[^0-9,\.\-]/u', '', $plain );
        if ( '' !== $numeric && preg_match( '/^-?0+(?:[\.,]0+)?$/', $numeric ) ) {
            return true;
        }
    }

    return false;
}

/**
 * The main template already recognises the note "Envío gratuito" / "Free shipping"
 * as an unconditional-free marker. Adding that marker here keeps the rendering,
 * sorting and bilingual behaviour in one place.
 */
function mdo_sst_zcf_normalize_rows( $rows, int $vendor_id = 0, string $type = '' ) {
    unset( $vendor_id, $type );

    if ( ! is_array( $rows ) ) {
        return $rows;
    }

    foreach ( $rows as &$row ) {
        if ( ! is_array( $row ) || ! mdo_sst_zcf_row_is_zero_cost( $row ) ) {
            continue;
        }

        $row['sort_cost'] = 0.0;
        $notes            = isset( $row['notes'] ) && is_array( $row['notes'] ) ? $row['notes'] : array();
        $notes[]          = function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Envío gratuito', 'Free shipping' ) : 'Envío gratuito';
        $row['notes']     = array_values( array_unique( array_filter( array_map( 'trim', $notes ) ) ) );
    }
    unset( $row );

    return $rows;
}
add_filter( 'mdo_store_shipping_rows', 'mdo_sst_zcf_normalize_rows', 99, 3 );
