<?php
/**
 * Plugin Name: MDO Store Shipping Tab
 * Description: Adds a bilingual Shipping tab to each WCFM store using the vendor's live shipping and minimum-order configuration.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Detect the storefront language without depending on one translation plugin. */
function mdo_sst_is_english(): bool {
    if ( function_exists( 'mdo_en_is_request' ) ) {
        return (bool) mdo_en_is_request();
    }
    if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
        return 'en' === strtolower( (string) ICL_LANGUAGE_CODE );
    }
    if ( function_exists( 'pll_current_language' ) ) {
        return 'en' === strtolower( (string) pll_current_language( 'slug' ) );
    }

    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    if ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) {
        return true;
    }

    return 0 === strpos( strtolower( (string) determine_locale() ), 'en_' );
}

function mdo_sst_text( string $es, string $en ): string {
    return mdo_sst_is_english() ? $en : $es;
}

function mdo_sst_get( $source, string $key, $default = null ) {
    if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
        return $source[ $key ];
    }
    if ( is_object( $source ) && isset( $source->{$key} ) ) {
        return $source->{$key};
    }
    return $default;
}

/**
 * The producer minimum configured in the marketplace/minimum-order plugin.
 * Keep the legacy filter so any existing customization keeps working.
 */
function mdo_sst_minimum_order( int $vendor_id ): float {
    $amount = get_user_meta( $vendor_id, '_wcfm_min_order_amt', true );
    $amount = is_numeric( $amount ) ? (float) $amount : 0.0;
    $amount = (float) apply_filters( 'mdo_product_shipping_minimum_order', $amount, $vendor_id );

    return max( 0, (float) apply_filters( 'mdo_store_shipping_minimum_order', $amount, $vendor_id ) );
}

function mdo_sst_money_or_text( $value ): string {
    if ( '' === $value || null === $value ) {
        return '';
    }
    if ( is_numeric( $value ) ) {
        return wp_strip_all_tags( wc_price( (float) $value ) );
    }
    return trim( wp_strip_all_tags( (string) $value ) );
}

function mdo_sst_country_name( string $code ): string {
    $code = strtoupper( trim( $code ) );
    if ( function_exists( 'WC' ) && WC()->countries ) {
        $countries = WC()->countries->get_countries();
        if ( isset( $countries[ $code ] ) ) {
            return (string) $countries[ $code ];
        }
    }
    return $code;
}

function mdo_sst_state_name( string $country, string $state ): string {
    $country = strtoupper( trim( $country ) );
    $state   = strtoupper( trim( $state ) );
    if ( function_exists( 'WC' ) && WC()->countries ) {
        $states = WC()->countries->get_states( $country );
        if ( is_array( $states ) && isset( $states[ $state ] ) ) {
            return (string) $states[ $state ];
        }
    }
    return $state;
}

/** Normalise WCFM zone shipping into rows that are safe to render. */
function mdo_sst_zone_rows( int $vendor_id ): array {
    if ( ! function_exists( 'wcfmmp_get_shipping_zone' ) ) {
        return array();
    }

    $zones = wcfmmp_get_shipping_zone( '', $vendor_id );
    if ( empty( $zones ) || ! is_array( $zones ) ) {
        return array();
    }

    $rows = array();

    foreach ( $zones as $zone ) {
        $zone_id   = absint( mdo_sst_get( $zone, 'zone_id', mdo_sst_get( $zone, 'id', 0 ) ) );
        $zone_name = trim( (string) mdo_sst_get( $zone, 'zone_name', mdo_sst_get( $zone, 'name', '' ) ) );

        if ( '' === $zone_name && $zone_id && class_exists( 'WC_Shipping_Zones' ) ) {
            $wc_zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
            if ( $wc_zone && is_callable( array( $wc_zone, 'get_zone_name' ) ) ) {
                $zone_name = (string) $wc_zone->get_zone_name();
            }
        }
        if ( '' === $zone_name ) {
            $zone_name = mdo_sst_text( 'Zona de envío', 'Shipping zone' );
        }

        $methods = mdo_sst_get( $zone, 'shipping_methods', array() );
        if ( ! is_array( $methods ) ) {
            $methods = array();
        }

        $flat_costs   = array();
        $free_from    = array();
        $method_notes = array();

        foreach ( $methods as $method ) {
            $enabled = mdo_sst_get( $method, 'is_enabled', mdo_sst_get( $method, 'enabled', 1 ) );
            if ( in_array( strtolower( (string) $enabled ), array( '0', 'no', 'false' ), true ) ) {
                continue;
            }

            $method_id = strtolower( (string) mdo_sst_get( $method, 'method_id', mdo_sst_get( $method, 'id', '' ) ) );
            $settings  = mdo_sst_get( $method, 'settings', array() );
            if ( is_string( $settings ) ) {
                $settings = maybe_unserialize( $settings );
            }
            if ( ! is_array( $settings ) ) {
                $settings = array();
            }

            $title = trim( (string) ( $settings['title'] ?? mdo_sst_get( $method, 'title', '' ) ) );

            if ( false !== strpos( $method_id, 'flat_rate' ) ) {
                $cost = $settings['cost'] ?? mdo_sst_get( $method, 'cost', '' );
                if ( '' !== (string) $cost ) {
                    $flat_costs[] = mdo_sst_money_or_text( $cost );
                }
                if ( $title && ! in_array( $title, array( 'Flat rate', 'Tarifa plana' ), true ) ) {
                    $method_notes[] = $title;
                }
            } elseif ( false !== strpos( $method_id, 'free_shipping' ) ) {
                $threshold = $settings['min_amount'] ?? $settings['min_order_amount'] ?? mdo_sst_get( $method, 'min_amount', '' );
                if ( is_numeric( $threshold ) && (float) $threshold > 0 ) {
                    $free_from[] = (float) $threshold;
                } else {
                    $method_notes[] = $title ?: mdo_sst_text( 'Envío gratuito', 'Free shipping' );
                }
            } elseif ( false !== strpos( $method_id, 'local_pickup' ) ) {
                $pickup_cost = $settings['cost'] ?? '';
                $label       = $title ?: mdo_sst_text( 'Recogida local', 'Local pickup' );
                if ( '' !== (string) $pickup_cost ) {
                    $label .= ': ' . mdo_sst_money_or_text( $pickup_cost );
                }
                $method_notes[] = $label;
            } elseif ( $title ) {
                $method_notes[] = $title;
            }
        }

        if ( empty( $flat_costs ) && empty( $free_from ) && empty( $method_notes ) ) {
            continue;
        }

        $locations      = array();
        $zone_locations = mdo_sst_get( $zone, 'zone_locations', mdo_sst_get( $zone, 'locations', array() ) );
        if ( is_array( $zone_locations ) ) {
            foreach ( $zone_locations as $location ) {
                $type = strtolower( (string) mdo_sst_get( $location, 'location_type', mdo_sst_get( $location, 'type', '' ) ) );
                $code = (string) mdo_sst_get( $location, 'location_code', mdo_sst_get( $location, 'code', '' ) );
                if ( 'country' === $type && $code ) {
                    $locations[] = mdo_sst_country_name( $code );
                } elseif ( 'state' === $type && false !== strpos( $code, ':' ) ) {
                    list( $country, $state ) = array_pad( explode( ':', $code, 2 ), 2, '' );
                    $locations[] = mdo_sst_state_name( $country, $state );
                }
            }
        }

        $rows[] = array(
            'name'       => $zone_name,
            'locations'  => array_values( array_unique( array_filter( $locations ) ) ),
            'flat_costs' => array_values( array_unique( array_filter( $flat_costs ) ) ),
            'free_from'  => empty( $free_from ) ? 0 : min( $free_from ),
            'notes'      => array_values( array_unique( array_filter( $method_notes ) ) ),
        );
    }

    return $rows;
}

/** Normalise WCFM "shipping by country" into the same row structure. */
function mdo_sst_country_rows( int $vendor_id ): array {
    $config = get_user_meta( $vendor_id, '_wcfmmp_shipping_by_country', true );
    $rates  = get_user_meta( $vendor_id, '_wcfmmp_shipping_rates', true );
    if ( empty( $rates ) ) {
        $rates = get_user_meta( $vendor_id, 'wcfmmp_shipping_rates', true );
    }

    $default_cost = is_array( $config ) ? ( $config['_wcfmmp_shipping_type_price'] ?? '' ) : '';
    $free_from    = 0;

    if ( is_array( $config ) ) {
        foreach ( array( '_wcfmmp_free_shipping_amount', '_wcfmmp_shipping_free_amount', '_wcfmmp_free_shipping_min_amount' ) as $key ) {
            if ( isset( $config[ $key ] ) && is_numeric( $config[ $key ] ) ) {
                $free_from = (float) $config[ $key ];
                break;
            }
        }
    }

    if ( empty( $rates ) || ! is_array( $rates ) ) {
        return array();
    }

    $rows = array();
    foreach ( $rates as $rate ) {
        if ( ! is_array( $rate ) ) {
            continue;
        }

        $country = strtoupper( trim( (string) ( $rate['wcfmmp_country_to'] ?? '' ) ) );
        if ( ! $country ) {
            continue;
        }

        $price  = $rate['wcfmmp_country_to_price'] ?? $default_cost;
        $rows[] = array(
            'name'       => mdo_sst_country_name( $country ),
            'locations'  => array(),
            'flat_costs' => '' !== (string) $price ? array( mdo_sst_money_or_text( $price ) ) : array(),
            'free_from'  => $free_from,
            'notes'      => array(),
        );
    }

    return $rows;
}

function mdo_sst_shipping_rows( int $vendor_id ): array {
    $shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
    $type     = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';

    $rows = 'by_country' === $type ? mdo_sst_country_rows( $vendor_id ) : mdo_sst_zone_rows( $vendor_id );

    usort(
        $rows,
        static function( array $a, array $b ): int {
            $rank = static function( array $row ): int {
                $haystack = remove_accents( strtolower( $row['name'] . ' ' . implode( ' ', $row['locations'] ) ) );
                if ( false !== strpos( $haystack, 'peninsula' ) || false !== strpos( $haystack, 'mainland spain' ) ) {
                    return 0;
                }
                if ( false !== strpos( $haystack, 'espana' ) || false !== strpos( $haystack, 'spain' ) ) {
                    return 1;
                }
                return 2;
            };

            $rank_a = $rank( $a );
            $rank_b = $rank( $b );
            if ( $rank_a !== $rank_b ) {
                return $rank_a <=> $rank_b;
            }

            return strcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) );
        }
    );

    $rows = apply_filters( 'mdo_product_shipping_rows', $rows, $vendor_id, $type );

    return apply_filters( 'mdo_store_shipping_rows', $rows, $vendor_id, $type );
}

/**
 * Add "Shipping" to every producer store. Products and About are kept first,
 * then Shipping, then any optional WCFM tabs.
 */
function mdo_sst_add_store_tab( $tabs, $store_id = 0 ) {
    if ( ! is_array( $tabs ) ) {
        return $tabs;
    }

    $ordered = array();

    if ( isset( $tabs['products'] ) ) {
        $ordered['products'] = $tabs['products'];
    }
    if ( isset( $tabs['about'] ) ) {
        $ordered['about'] = $tabs['about'];
    }

    $ordered['shipping'] = mdo_sst_text( 'Envíos', 'Shipping' );

    foreach ( $tabs as $key => $label ) {
        if ( in_array( $key, array( 'products', 'about', 'shipping' ), true ) ) {
            continue;
        }
        $ordered[ $key ] = $label;
    }

    return $ordered;
}
add_filter( 'wcfmmp_store_tabs', 'mdo_sst_add_store_tab', 40, 2 );

/** Use a query var so no rewrite-rule flush is required. */
function mdo_sst_query_vars( array $vars ): array {
    $vars[] = 'mdo_shipping';
    return array_values( array_unique( $vars ) );
}
add_filter( 'query_vars', 'mdo_sst_query_vars' );

function mdo_sst_store_tab_url( string $url, string $tab ): string {
    if ( 'shipping' !== $tab ) {
        return $url;
    }

    return add_query_arg( 'mdo_shipping', '1', $url ) . '#tab_links_area';
}
add_filter( 'wcfmp_store_tabs_url', 'mdo_sst_store_tab_url', 40, 2 );

/**
 * WCFM calls this filter with two different argument shapes in different
 * locations; only the request flag matters to us.
 */
function mdo_sst_default_store_tab( $current, $arg2 = null, $arg3 = null ) {
    $is_shipping = get_query_var( 'mdo_shipping' );

    if ( ! $is_shipping && isset( $_GET['mdo_shipping'] ) ) {
        $is_shipping = sanitize_text_field( wp_unslash( $_GET['mdo_shipping'] ) );
    }

    if ( $is_shipping ) {
        return 'shipping';
    }

    return $current;
}
add_filter( 'wcfmmp_store_default_query_vars', 'mdo_sst_default_store_tab', 40, 3 );

function mdo_sst_store_template( string $template, string $store_tab ): string {
    return 'shipping' === $store_tab ? 'mdo-store-shipping.php' : $template;
}
add_filter( 'wcfmmp_store_default_template', 'mdo_sst_store_template', 40, 2 );

function mdo_sst_store_template_path( string $path, string $store_tab ): string {
    return 'shipping' === $store_tab ? plugin_dir_path( __FILE__ ) . 'mdo-store-shipping/' : $path;
}
add_filter( 'wcfmp_store_default_template_path', 'mdo_sst_store_template_path', 40, 2 );
