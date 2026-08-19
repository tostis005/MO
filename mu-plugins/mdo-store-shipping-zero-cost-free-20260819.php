<?php
/**
 * Plugin Name: MDO Store Shipping Zero-Cost Normalizer
 * Description: Keeps the public producer Shipping tab aligned with WCFM vendor-zone data and treats explicit free/zero rates as free shipping.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mdo_sst_zcf_location_label( string $type, string $code ): string {
    $type = strtolower( trim( $type ) );
    $code = trim( $code );

    if ( 'country' === $type && function_exists( 'mdo_sst_country_name' ) ) {
        return mdo_sst_country_name( $code );
    }
    if ( 'state' === $type && false !== strpos( $code, ':' ) && function_exists( 'mdo_sst_state_name' ) ) {
        list( $country, $state ) = array_pad( explode( ':', $code, 2 ), 2, '' );
        return mdo_sst_state_name( $country, $state );
    }
    if ( 'continent' === $type && function_exists( 'mdo_sst_continent_name' ) ) {
        return mdo_sst_continent_name( $code );
    }

    return $code;
}

/**
 * Read enabled vendor shipping methods straight from WCFM's own tables.
 * WCFM's get_zones() API only exposes zones when the corresponding global
 * WooCommerce zone contains its bridge method, which can make valid vendor
 * rates disappear from this informational tab in some request contexts.
 */
function mdo_sst_zcf_db_rows( int $vendor_id ): array {
    global $wpdb;

    if ( $vendor_id <= 0 ) {
        return array();
    }

    $methods_table   = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
    $locations_table = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';

    $methods = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT zone_id, instance_id, method_id, is_enabled, settings
             FROM {$methods_table}
             WHERE vendor_id = %d AND is_enabled = 1
             ORDER BY zone_id ASC, instance_id ASC",
            $vendor_id
        ),
        ARRAY_A
    );

    if ( empty( $methods ) || ! is_array( $methods ) ) {
        return array();
    }

    $grouped = array();

    foreach ( $methods as $method ) {
        $zone_id   = absint( $method['zone_id'] ?? 0 );
        $method_id = strtolower( trim( (string) ( $method['method_id'] ?? '' ) ) );
        if ( '' === $method_id ) {
            continue;
        }

        if ( ! isset( $grouped[ $zone_id ] ) ) {
            $zone_name = '';
            $locations = array();
            $zone_obj  = null;

            if ( class_exists( 'WC_Shipping_Zone' ) ) {
                $zone_obj = 0 === $zone_id ? new WC_Shipping_Zone( 0 ) : WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
                if ( $zone_obj && is_callable( array( $zone_obj, 'get_zone_name' ) ) ) {
                    $zone_name = (string) $zone_obj->get_zone_name();
                }
            }
            if ( '' === $zone_name ) {
                $zone_name = 0 === $zone_id
                    ? ( function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Resto de destinos', 'Other destinations' ) : 'Resto de destinos' )
                    : ( function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Zona de envío', 'Shipping zone' ) : 'Zona de envío' );
            }

            $vendor_locations = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT location_code, location_type FROM {$locations_table} WHERE vendor_id = %d AND zone_id = %d ORDER BY location_type, location_code",
                    $vendor_id,
                    $zone_id
                ),
                ARRAY_A
            );

            if ( is_array( $vendor_locations ) && ! empty( $vendor_locations ) ) {
                foreach ( $vendor_locations as $location ) {
                    $label = mdo_sst_zcf_location_label(
                        (string) ( $location['location_type'] ?? '' ),
                        (string) ( $location['location_code'] ?? '' )
                    );
                    if ( '' !== trim( $label ) ) {
                        $locations[] = trim( $label );
                    }
                }
            } elseif ( $zone_obj && is_callable( array( $zone_obj, 'get_zone_locations' ) ) ) {
                foreach ( (array) $zone_obj->get_zone_locations() as $location ) {
                    $type = is_object( $location ) ? (string) ( $location->type ?? '' ) : (string) ( $location['type'] ?? '' );
                    $code = is_object( $location ) ? (string) ( $location->code ?? '' ) : (string) ( $location['code'] ?? '' );
                    $label = mdo_sst_zcf_location_label( $type, $code );
                    if ( '' !== trim( $label ) ) {
                        $locations[] = trim( $label );
                    }
                }
            }

            $grouped[ $zone_id ] = array(
                'zone_id'    => $zone_id,
                'name'       => $zone_name,
                'locations'  => array_values( array_unique( array_filter( $locations ) ) ),
                'flat_costs' => array(),
                'sort_cost'  => null,
                'free_from'  => 0,
                'notes'      => array(),
            );
        }

        $settings = maybe_unserialize( $method['settings'] ?? array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $title = trim( (string) ( $settings['title'] ?? '' ) );

        if ( 'flat_rate' === $method_id ) {
            // WCFM itself defaults an enabled flat-rate method to zero cost.
            $cost = array_key_exists( 'cost', $settings ) ? $settings['cost'] : '0';
            $display_cost = function_exists( 'mdo_sst_money_or_text' ) ? mdo_sst_money_or_text( $cost ) : trim( (string) $cost );
            if ( '' !== $display_cost ) {
                $grouped[ $zone_id ]['flat_costs'][] = $display_cost;
            }
            if ( is_numeric( $cost ) ) {
                $numeric = (float) $cost;
                if ( null === $grouped[ $zone_id ]['sort_cost'] || $numeric < (float) $grouped[ $zone_id ]['sort_cost'] ) {
                    $grouped[ $zone_id ]['sort_cost'] = $numeric;
                }
                if ( abs( $numeric ) < 0.0001 ) {
                    $grouped[ $zone_id ]['notes'][] = function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Envío gratuito', 'Free shipping' ) : 'Envío gratuito';
                }
            }
            if ( $title && ! in_array( $title, array( 'Flat rate', 'Tarifa plana' ), true ) ) {
                $grouped[ $zone_id ]['notes'][] = $title;
            }
        } elseif ( 'free_shipping' === $method_id ) {
            $threshold = $settings['min_amount'] ?? $settings['min_order_amount'] ?? '';
            if ( is_numeric( $threshold ) && (float) $threshold > 0 ) {
                $threshold = (float) $threshold;
                $current   = (float) $grouped[ $zone_id ]['free_from'];
                $grouped[ $zone_id ]['free_from'] = $current > 0 ? min( $current, $threshold ) : $threshold;
            } else {
                $grouped[ $zone_id ]['notes'][] = $title ?: ( function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Envío gratuito', 'Free shipping' ) : 'Envío gratuito' );
            }
        } elseif ( 'local_pickup' === $method_id ) {
            $pickup_cost = $settings['cost'] ?? '';
            $label       = $title ?: ( function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Recogida local', 'Local pickup' ) : 'Recogida local' );
            if ( '' !== (string) $pickup_cost ) {
                $formatted = function_exists( 'mdo_sst_money_or_text' ) ? mdo_sst_money_or_text( $pickup_cost ) : trim( (string) $pickup_cost );
                if ( '' !== $formatted ) {
                    $label .= ': ' . $formatted;
                }
            }
            $grouped[ $zone_id ]['notes'][] = $label;
        } elseif ( $title ) {
            $grouped[ $zone_id ]['notes'][] = $title;
        }
    }

    foreach ( $grouped as &$row ) {
        $row['flat_costs'] = array_values( array_unique( array_filter( array_map( 'trim', (array) $row['flat_costs'] ) ) ) );
        $row['notes']      = array_values( array_unique( array_filter( array_map( 'trim', (array) $row['notes'] ) ) ) );
    }
    unset( $row );

    return array_values( $grouped );
}

function mdo_sst_zcf_merge_rows( $rows, int $vendor_id = 0, string $type = '' ) {
    if ( ! is_array( $rows ) || 'by_country' === $type || $vendor_id <= 0 ) {
        return $rows;
    }

    $db_rows = mdo_sst_zcf_db_rows( $vendor_id );
    if ( empty( $db_rows ) ) {
        return $rows;
    }

    $result = array_values( $rows );

    foreach ( $db_rows as $db_row ) {
        $match = null;
        foreach ( $result as $index => $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            if ( isset( $row['zone_id'] ) && (int) $row['zone_id'] === (int) $db_row['zone_id'] ) {
                $match = $index;
                break;
            }
            if ( 0 === strcasecmp( trim( (string) ( $row['name'] ?? '' ) ), trim( (string) $db_row['name'] ) ) ) {
                $match = $index;
                break;
            }
        }

        if ( null === $match ) {
            $result[] = $db_row;
            continue;
        }

        $result[ $match ]['zone_id']    = (int) $db_row['zone_id'];
        $result[ $match ]['locations']  = array_values( array_unique( array_merge( (array) ( $result[ $match ]['locations'] ?? array() ), (array) $db_row['locations'] ) ) );
        $result[ $match ]['flat_costs'] = array_values( array_unique( array_merge( (array) ( $result[ $match ]['flat_costs'] ?? array() ), (array) $db_row['flat_costs'] ) ) );
        $result[ $match ]['notes']      = array_values( array_unique( array_merge( (array) ( $result[ $match ]['notes'] ?? array() ), (array) $db_row['notes'] ) ) );

        if ( isset( $db_row['sort_cost'] ) && is_numeric( $db_row['sort_cost'] ) ) {
            $result[ $match ]['sort_cost'] = (float) $db_row['sort_cost'];
        }
        if ( ! empty( $db_row['free_from'] ) ) {
            $result[ $match ]['free_from'] = (float) $db_row['free_from'];
        }
    }

    return $result;
}
add_filter( 'mdo_store_shipping_rows', 'mdo_sst_zcf_merge_rows', 20, 3 );

/** Return true for all public labels that mean unconditional free shipping. */
function mdo_sst_zcf_note_is_free( $note ): bool {
    $plain = remove_accents( strtolower( trim( wp_strip_all_tags( (string) $note ) ) ) );

    return false !== strpos( $plain, 'envio gratuito' )
        || false !== strpos( $plain, 'envio gratis' )
        || false !== strpos( $plain, 'free shipping' );
}

/** Return true when a public shipping row explicitly represents a zero cost. */
function mdo_sst_zcf_row_is_zero_cost( array $row ): bool {
    if ( array_key_exists( 'sort_cost', $row ) && is_numeric( $row['sort_cost'] ) ) {
        return abs( (float) $row['sort_cost'] ) < 0.0001;
    }

    foreach ( (array) ( $row['flat_costs'] ?? array() ) as $cost ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $cost ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( str_replace( array( "\xc2\xa0", ' ' ), '', $plain ) );
        $numeric = preg_replace( '/[^0-9,\.\-]/u', '', $plain );
        if ( '' !== $numeric && preg_match( '/^-?0+(?:[\.,]0+)?$/', $numeric ) ) {
            return true;
        }
    }

    return false;
}

function mdo_sst_zcf_normalize_rows( $rows, int $vendor_id = 0, string $type = '' ) {
    unset( $vendor_id, $type );

    if ( ! is_array( $rows ) ) {
        return $rows;
    }

    foreach ( $rows as &$row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $zero_cost = mdo_sst_zcf_row_is_zero_cost( $row );
        $notes     = isset( $row['notes'] ) && is_array( $row['notes'] ) ? $row['notes'] : array();
        $free_note = false;

        foreach ( $notes as $note ) {
            if ( mdo_sst_zcf_note_is_free( $note ) ) {
                $free_note = true;
                break;
            }
        }

        if ( ! $zero_cost && ! $free_note ) {
            continue;
        }

        // An unconditional-free method sorts with the free rates even when WCFM
        // represents it as a titled free-shipping method rather than cost=0.
        $row['sort_cost'] = 0.0;

        $notes = array_values(
            array_filter(
                array_map( 'trim', $notes ),
                static function( string $note ): bool {
                    return '' !== $note && ! mdo_sst_zcf_note_is_free( $note );
                }
            )
        );
        $notes[]      = function_exists( 'mdo_sst_text' ) ? mdo_sst_text( 'Envío gratuito', 'Free shipping' ) : 'Envío gratuito';
        $row['notes'] = array_values( array_unique( $notes ) );
    }
    unset( $row );

    return $rows;
}
add_filter( 'mdo_store_shipping_rows', 'mdo_sst_zcf_normalize_rows', 99, 3 );
