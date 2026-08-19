<?php
/**
 * Plugin Name: MDO Product Shipping Tab
 * Description: Adds a bilingual product Shipping tab sourced from each WCFM vendor's live shipping configuration.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Detect the storefront language without depending on one translation plugin. */
function mdo_pst_is_english(): bool {
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

function mdo_pst_text( string $es, string $en ): string {
    return mdo_pst_is_english() ? $en : $es;
}

function mdo_pst_get( $source, string $key, $default = null ) {
    if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
        return $source[ $key ];
    }
    if ( is_object( $source ) && isset( $source->{$key} ) ) {
        return $source->{$key};
    }
    return $default;
}

function mdo_pst_vendor_id( int $product_id ): int {
    if ( function_exists( 'wcfm_get_vendor_id_by_post' ) ) {
        $vendor_id = absint( wcfm_get_vendor_id_by_post( $product_id ) );
        if ( $vendor_id ) {
            return $vendor_id;
        }
    }
    return absint( get_post_field( 'post_author', $product_id ) );
}

function mdo_pst_minimum_order( int $vendor_id ): float {
    $amount = get_user_meta( $vendor_id, '_wcfm_min_order_amt', true );
    $amount = is_numeric( $amount ) ? (float) $amount : 0.0;

    return max( 0, (float) apply_filters( 'mdo_product_shipping_minimum_order', $amount, $vendor_id ) );
}

function mdo_pst_money_or_text( $value ): string {
    if ( '' === $value || null === $value ) {
        return '';
    }
    if ( is_numeric( $value ) ) {
        return wp_strip_all_tags( wc_price( (float) $value ) );
    }
    return trim( wp_strip_all_tags( (string) $value ) );
}

function mdo_pst_country_name( string $code ): string {
    $code = strtoupper( trim( $code ) );
    if ( function_exists( 'WC' ) && WC()->countries ) {
        $countries = WC()->countries->get_countries();
        if ( isset( $countries[ $code ] ) ) {
            return (string) $countries[ $code ];
        }
    }
    return $code;
}

function mdo_pst_state_name( string $country, string $state ): string {
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
function mdo_pst_zone_rows( int $vendor_id ): array {
    if ( ! function_exists( 'wcfmmp_get_shipping_zone' ) ) {
        return array();
    }

    $zones = wcfmmp_get_shipping_zone( '', $vendor_id );
    if ( empty( $zones ) || ! is_array( $zones ) ) {
        return array();
    }

    $rows = array();

    foreach ( $zones as $zone ) {
        $zone_id   = absint( mdo_pst_get( $zone, 'zone_id', mdo_pst_get( $zone, 'id', 0 ) ) );
        $zone_name = trim( (string) mdo_pst_get( $zone, 'zone_name', mdo_pst_get( $zone, 'name', '' ) ) );

        if ( '' === $zone_name && $zone_id && class_exists( 'WC_Shipping_Zones' ) ) {
            $wc_zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
            if ( $wc_zone && is_callable( array( $wc_zone, 'get_zone_name' ) ) ) {
                $zone_name = (string) $wc_zone->get_zone_name();
            }
        }
        if ( '' === $zone_name ) {
            $zone_name = mdo_pst_text( 'Zona de envío', 'Shipping zone' );
        }

        $methods = mdo_pst_get( $zone, 'shipping_methods', array() );
        if ( ! is_array( $methods ) ) {
            $methods = array();
        }

        $flat_costs   = array();
        $free_from    = array();
        $method_notes = array();

        foreach ( $methods as $method ) {
            $enabled = mdo_pst_get( $method, 'is_enabled', mdo_pst_get( $method, 'enabled', 1 ) );
            if ( in_array( (string) $enabled, array( '0', 'no', 'false' ), true ) ) {
                continue;
            }

            $method_id = strtolower( (string) mdo_pst_get( $method, 'method_id', mdo_pst_get( $method, 'id', '' ) ) );
            $settings  = mdo_pst_get( $method, 'settings', array() );
            if ( is_string( $settings ) ) {
                $settings = maybe_unserialize( $settings );
            }
            if ( ! is_array( $settings ) ) {
                $settings = array();
            }

            $title = trim( (string) ( $settings['title'] ?? mdo_pst_get( $method, 'title', '' ) ) );

            if ( false !== strpos( $method_id, 'flat_rate' ) ) {
                $cost = $settings['cost'] ?? mdo_pst_get( $method, 'cost', '' );
                if ( '' !== (string) $cost ) {
                    $flat_costs[] = mdo_pst_money_or_text( $cost );
                }
                if ( $title && ! in_array( $title, array( 'Flat rate', 'Tarifa plana' ), true ) ) {
                    $method_notes[] = $title;
                }
            } elseif ( false !== strpos( $method_id, 'free_shipping' ) ) {
                $threshold = $settings['min_amount'] ?? $settings['min_order_amount'] ?? mdo_pst_get( $method, 'min_amount', '' );
                if ( is_numeric( $threshold ) && (float) $threshold > 0 ) {
                    $free_from[] = (float) $threshold;
                } else {
                    $method_notes[] = $title ?: mdo_pst_text( 'Envío gratuito', 'Free shipping' );
                }
            } elseif ( false !== strpos( $method_id, 'local_pickup' ) ) {
                $pickup_cost = $settings['cost'] ?? '';
                $label = $title ?: mdo_pst_text( 'Recogida local', 'Local pickup' );
                if ( '' !== (string) $pickup_cost ) {
                    $label .= ': ' . mdo_pst_money_or_text( $pickup_cost );
                }
                $method_notes[] = $label;
            } elseif ( $title ) {
                $method_notes[] = $title;
            }
        }

        if ( empty( $flat_costs ) && empty( $free_from ) && empty( $method_notes ) ) {
            continue;
        }

        $locations = array();
        $zone_locations = mdo_pst_get( $zone, 'zone_locations', mdo_pst_get( $zone, 'locations', array() ) );
        if ( is_array( $zone_locations ) ) {
            foreach ( $zone_locations as $location ) {
                $type = strtolower( (string) mdo_pst_get( $location, 'location_type', mdo_pst_get( $location, 'type', '' ) ) );
                $code = (string) mdo_pst_get( $location, 'location_code', mdo_pst_get( $location, 'code', '' ) );
                if ( 'country' === $type && $code ) {
                    $locations[] = mdo_pst_country_name( $code );
                } elseif ( 'state' === $type && false !== strpos( $code, ':' ) ) {
                    list( $country, $state ) = array_pad( explode( ':', $code, 2 ), 2, '' );
                    $locations[] = mdo_pst_state_name( $country, $state );
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

function mdo_pst_country_rows( int $vendor_id ): array {
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
        $price = $rate['wcfmmp_country_to_price'] ?? $default_cost;
        $rows[] = array(
            'name'       => mdo_pst_country_name( $country ),
            'locations'  => array(),
            'flat_costs' => '' !== (string) $price ? array( mdo_pst_money_or_text( $price ) ) : array(),
            'free_from'  => $free_from,
            'notes'      => array(),
        );
    }
    return $rows;
}

function mdo_pst_shipping_rows( int $vendor_id ): array {
    $shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
    $type = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';

    if ( 'by_country' === $type ) {
        $rows = mdo_pst_country_rows( $vendor_id );
    } else {
        $rows = mdo_pst_zone_rows( $vendor_id );
    }

    usort( $rows, static function( array $a, array $b ): int {
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
    } );

    return apply_filters( 'mdo_product_shipping_rows', $rows, $vendor_id, $type );
}

function mdo_pst_has_content( int $product_id ): bool {
    $vendor_id = mdo_pst_vendor_id( $product_id );
    if ( ! $vendor_id ) {
        return false;
    }
    return mdo_pst_minimum_order( $vendor_id ) > 0 || ! empty( mdo_pst_shipping_rows( $vendor_id ) );
}

add_filter( 'woocommerce_product_tabs', function( array $tabs ): array {
    global $product;
    if ( ! $product instanceof WC_Product || ! mdo_pst_has_content( $product->get_id() ) ) {
        return $tabs;
    }

    $tabs['mdo_shipping'] = array(
        'title'    => mdo_pst_text( 'Envíos', 'Shipping' ),
        'priority' => 35,
        'callback' => 'mdo_pst_render_tab',
    );
    return $tabs;
}, 35 );

function mdo_pst_render_tab(): void {
    global $product;
    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $vendor_id = mdo_pst_vendor_id( $product->get_id() );
    if ( ! $vendor_id ) {
        return;
    }

    $minimum = mdo_pst_minimum_order( $vendor_id );
    $rows    = mdo_pst_shipping_rows( $vendor_id );

    echo '<div class="mdo-product-shipping-tab">';
    echo '<p>' . esc_html( mdo_pst_text(
        'Condiciones de envío de esta tienda. Los importes se actualizan automáticamente con la configuración del productor.',
        'Shipping conditions for this store. Amounts update automatically from the producer’s current settings.'
    ) ) . '</p>';

    if ( $minimum > 0 ) {
        echo '<p><strong>' . esc_html( mdo_pst_text( 'Pedido mínimo:', 'Minimum order:' ) ) . '</strong> ' . wp_kses_post( wc_price( $minimum ) ) . '</p>';
    }

    if ( ! empty( $rows ) ) {
        echo '<div class="mdo-shipping-table-wrap" style="overflow-x:auto">';
        echo '<table class="shop_attributes mdo-shipping-table"><thead><tr>';
        echo '<th>' . esc_html( mdo_pst_text( 'Destino', 'Destination' ) ) . '</th>';
        echo '<th>' . esc_html( mdo_pst_text( 'Condiciones', 'Conditions' ) ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            $parts = array();
            if ( ! empty( $row['flat_costs'] ) ) {
                $parts[] = esc_html( mdo_pst_text( 'Envío:', 'Shipping:' ) . ' ' . implode( ' / ', $row['flat_costs'] ) );
            }
            if ( ! empty( $row['free_from'] ) ) {
                $parts[] = esc_html( mdo_pst_text( 'Gratis a partir de', 'Free from' ) . ' ' ) . wp_kses_post( wc_price( (float) $row['free_from'] ) );
            }
            foreach ( $row['notes'] as $note ) {
                $parts[] = esc_html( $note );
            }

            echo '<tr><th scope="row">' . esc_html( $row['name'] );
            if ( ! empty( $row['locations'] ) ) {
                echo '<br><small>' . esc_html( implode( ', ', $row['locations'] ) ) . '</small>';
            }
            echo '</th><td>' . implode( ' · ', $parts ) . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    echo '<p><small>' . esc_html( mdo_pst_text(
        'El coste definitivo se calcula en el carrito según los productos, el importe del pedido y la dirección de entrega.',
        'The final shipping cost is calculated in the basket according to the products, order value and delivery address.'
    ) ) . '</small></p>';
    echo '</div>';
}
