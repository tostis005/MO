<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
const MDO_GMF_VERSION_V1 = '1.0.0';

function mdo_gmf_clean_text_v1( $value, int $max_length = 5000 ): string {
    $value = html_entity_decode( wp_strip_all_tags( (string) $value, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
    if ( function_exists( 'mb_substr' ) ) {
        return mb_substr( $value, 0, $max_length, 'UTF-8' );
    }
    return substr( $value, 0, $max_length );
}

function mdo_gmf_xml_v1( $value ): string {
    return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

function mdo_gmf_money_v1( $value ): string {
    return number_format( max( 0, (float) $value ), 2, '.', '' ) . ' EUR';
}

function mdo_gmf_country_code_v1( string $country ): string {
    $country = strtoupper( trim( $country ) );
    return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '';
}

function mdo_gmf_feed_language_v1( string $country ): string {
    return 'ES' === mdo_gmf_country_code_v1( $country ) ? 'es' : 'en';
}

function mdo_gmf_country_names_v1(): array {
    if ( function_exists( 'mdo_catalog_allowed_map_v060' ) ) {
        $map = (array) mdo_catalog_allowed_map_v060();
        if ( ! empty( $map ) ) {
            return $map;
        }
    }

    $countries = array();
    if ( function_exists( 'WC' ) && WC()->countries ) {
        $countries = (array) WC()->countries->get_allowed_countries();
        if ( empty( $countries ) ) {
            $countries = (array) WC()->countries->get_countries();
        }
    } elseif ( class_exists( 'WC_Countries' ) ) {
        $wc_countries = new WC_Countries();
        $countries = (array) $wc_countries->get_allowed_countries();
    }

    $result = array();
    foreach ( $countries as $code => $name ) {
        $code = mdo_gmf_country_code_v1( (string) $code );
        if ( '' !== $code ) {
            $result[ $code ] = mdo_gmf_clean_text_v1( $name, 100 );
        }
    }
    return $result;
}

function mdo_gmf_vendor_is_active_v1( int $vendor_id ): bool {
    static $cache = array();

    if ( isset( $cache[ $vendor_id ] ) ) {
        return $cache[ $vendor_id ];
    }
    if ( $vendor_id <= 0 ) {
        return $cache[ $vendor_id ] = false;
    }

    if ( function_exists( 'emdo_cleanup_vendor_is_disabled' ) && emdo_cleanup_vendor_is_disabled( $vendor_id ) ) {
        return $cache[ $vendor_id ] = false;
    }

    $user = get_userdata( $vendor_id );
    if ( ! $user instanceof WP_User ) {
        return $cache[ $vendor_id ] = false;
    }

    $roles = array_map( 'sanitize_key', (array) $user->roles );
    if ( in_array( 'disable_vendor', $roles, true ) ) {
        return $cache[ $vendor_id ] = false;
    }

    foreach ( array( '_disable_vendor', '_wcfm_store_offline' ) as $meta_key ) {
        $raw = get_user_meta( $vendor_id, $meta_key, true );
        $normalized = strtolower( trim( is_scalar( $raw ) ? (string) $raw : '' ) );
        if ( ! in_array( $normalized, array( '', '0', 'no', 'false', 'off', 'none' ), true ) ) {
            return $cache[ $vendor_id ] = false;
        }
    }

    // WCFM assigns the wcfm_vendor role once a producer is approved. The
    // profile fallback preserves already-approved stores whose role was
    // customized while still excluding arbitrary WordPress authors.
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $has_store_profile = is_array( $profile ) && '' !== trim( (string) ( $profile['store_name'] ?? '' ) );
    $approved = in_array( 'wcfm_vendor', $roles, true ) || $has_store_profile;

    return $cache[ $vendor_id ] = (bool) apply_filters( 'mdo_google_merchant_vendor_is_active', $approved, $vendor_id );
}

function mdo_gmf_vendor_brand_v1( int $vendor_id ): string {
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    if ( is_array( $profile ) && ! empty( $profile['store_name'] ) ) {
        $name = mdo_gmf_clean_text_v1( $profile['store_name'], 70 );
        if ( '' !== $name ) {
            return $name;
        }
    }

    $user = get_userdata( $vendor_id );
    if ( $user instanceof WP_User ) {
        $name = mdo_gmf_clean_text_v1( $user->display_name, 70 );
        if ( '' !== $name ) {
            return $name;
        }
    }

    return 'El Mercado de Origen';
}

function mdo_gmf_parent_ids_v1(): array {
    static $cache = null;
    if ( is_array( $cache ) ) {
        return $cache;
    }

    if ( function_exists( 'emdo_cleanup_eligible_product_ids' ) ) {
        return $cache = array_values( array_unique( array_filter( array_map( 'absint', (array) emdo_cleanup_eligible_product_ids() ) ) ) );
    }

    $ids = get_posts( array(
        'post_type'        => 'product',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'has_password'     => false,
        'suppress_filters' => true,
    ) );

    $eligible = array();
    foreach ( array_map( 'absint', (array) $ids ) as $id ) {
        $post = get_post( $id );
        if ( ! $post instanceof WP_Post || ! mdo_gmf_vendor_is_active_v1( (int) $post->post_author ) ) {
            continue;
        }
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $id );
            if ( ! $product instanceof WC_Product || 'hidden' === (string) $product->get_catalog_visibility() ) {
                continue;
            }
        }
        $eligible[] = $id;
    }
    return $cache = array_values( array_unique( $eligible ) );
}

function mdo_gmf_vendor_shipping_type_v1( int $vendor_id ): string {
    $shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
    return is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';
}

function mdo_gmf_country_shipping_by_country_v1( int $vendor_id, string $country ): array {
    $result = array(
        'can_ship'       => false,
        'cost'           => null,
        'free_threshold' => 0.0,
    );

    $country = mdo_gmf_country_code_v1( $country );
    if ( '' === $country ) {
        return $result;
    }

    $config = get_user_meta( $vendor_id, '_wcfmmp_shipping_by_country', true );
    $rates  = get_user_meta( $vendor_id, '_wcfmmp_shipping_rates', true );
    if ( empty( $rates ) ) {
        $rates = get_user_meta( $vendor_id, 'wcfmmp_shipping_rates', true );
    }
    $default_cost = is_array( $config ) ? ( $config['_wcfmmp_shipping_type_price'] ?? '' ) : '';

    foreach ( (array) $rates as $rate ) {
        if ( ! is_array( $rate ) || $country !== strtoupper( trim( (string) ( $rate['wcfmmp_country_to'] ?? '' ) ) ) ) {
            continue;
        }
        $result['can_ship'] = true;
        $price = $rate['wcfmmp_country_to_price'] ?? $default_cost;
        if ( is_numeric( $price ) ) {
            $result['cost'] = max( 0.0, (float) $price );
        }
        break;
    }

    if ( is_array( $config ) ) {
        foreach ( array( '_wcfmmp_free_shipping_amount', '_wcfmmp_shipping_free_amount', '_wcfmmp_free_shipping_min_amount' ) as $key ) {
            if ( isset( $config[ $key ] ) && is_numeric( $config[ $key ] ) && (float) $config[ $key ] > 0 ) {
                $result['free_threshold'] = (float) $config[ $key ];
                break;
            }
        }
    }

    return $result;
}

function mdo_gmf_zone_row_unconditional_free_v1( array $row ): bool {
    if ( isset( $row['sort_cost'] ) && is_numeric( $row['sort_cost'] ) && abs( (float) $row['sort_cost'] ) < 0.0001 ) {
        return true;
    }
    foreach ( (array) ( $row['notes'] ?? array() ) as $note ) {
        $plain = remove_accents( strtolower( mdo_gmf_clean_text_v1( $note, 200 ) ) );
        if ( false !== strpos( $plain, 'envio gratuito' ) || false !== strpos( $plain, 'envio gratis' ) || false !== strpos( $plain, 'free shipping' ) ) {
            return true;
        }
    }
    return false;
}

function mdo_gmf_zone_row_single_item_cost_v1( array $row ): ?float {
    if ( isset( $row['sort_cost'] ) && is_numeric( $row['sort_cost'] ) ) {
        return max( 0.0, (float) $row['sort_cost'] );
    }

    $parsed = array();
    foreach ( (array) ( $row['flat_costs'] ?? array() ) as $raw_cost ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $raw_cost ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( str_replace( array( "\xc2\xa0" ), ' ', $plain ) );

        // WCFM/WooCommerce quantity formulas are common for food shipments.
        // For a Shopping offer, shipping is reported for one unit, so a simple
        // "8 * [qty]" (or x/×) formula safely resolves to 8.
        if ( preg_match( '/^\s*([0-9]+(?:[\.,][0-9]+)?)\s*(?:\*|x|×)\s*\[qty\]\s*$/iu', $plain, $matches ) ) {
            $parsed[] = max( 0.0, (float) str_replace( ',', '.', $matches[1] ) );
            continue;
        }

        $numeric = preg_replace( '/[^0-9,\.\-]/u', '', $plain );
        if ( '' !== $numeric && preg_match( '/^-?[0-9]+(?:[\.,][0-9]+)?$/', $numeric ) ) {
            $parsed[] = max( 0.0, (float) str_replace( ',', '.', $numeric ) );
        }
    }

    return empty( $parsed ) ? null : max( $parsed );
}

function mdo_gmf_country_shipping_by_zone_v1( int $vendor_id, string $country ): array {
    $result = array(
        'can_ship'       => false,
        'cost'           => null,
        'free_threshold' => 0.0,
    );
    $country = mdo_gmf_country_code_v1( $country );
    if ( '' === $country ) {
        return $result;
    }

    $rows = function_exists( 'mdo_sst_zcf_db_rows' ) ? (array) mdo_sst_zcf_db_rows( $vendor_id ) : array();
    if ( empty( $rows ) && function_exists( 'mdo_sst_shipping_rows' ) ) {
        $rows = (array) mdo_sst_shipping_rows( $vendor_id );
    }

    $matching = array();
    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $zone_id = isset( $row['zone_id'] ) ? absint( $row['zone_id'] ) : null;
        $matches = false;

        if ( null !== $zone_id && function_exists( 'mdo_catalog_public_zone_matches_20260820' ) ) {
            $matches = (bool) mdo_catalog_public_zone_matches_20260820( $zone_id, $country, '' );
        } elseif ( isset( $row['locations'] ) && is_array( $row['locations'] ) ) {
            // Last-resort fallback for older public row structures: compare
            // country display names rather than guessing from a zone title.
            $country_name = '';
            if ( function_exists( 'mdo_sst_country_name' ) ) {
                $country_name = remove_accents( strtolower( mdo_sst_country_name( $country ) ) );
            }
            foreach ( $row['locations'] as $location ) {
                if ( '' !== $country_name && remove_accents( strtolower( mdo_gmf_clean_text_v1( $location, 100 ) ) ) === $country_name ) {
                    $matches = true;
                    break;
                }
            }
        }

        if ( $matches ) {
            $matching[] = $row;
        }
    }

    if ( empty( $matching ) ) {
        return $result;
    }

    $result['can_ship'] = true;
    $costs = array();
    $thresholds = array();
    $all_costs_known = true;
    $all_have_threshold_or_free = true;

    foreach ( $matching as $row ) {
        if ( mdo_gmf_zone_row_unconditional_free_v1( $row ) ) {
            $costs[] = 0.0;
            continue;
        }
        $single_item_cost = mdo_gmf_zone_row_single_item_cost_v1( $row );
        if ( null !== $single_item_cost ) {
            $costs[] = $single_item_cost;
        } else {
            $all_costs_known = false;
        }
        $free_from = isset( $row['free_from'] ) && is_numeric( $row['free_from'] ) ? (float) $row['free_from'] : 0.0;
        if ( $free_from > 0 ) {
            $thresholds[] = $free_from;
        } else {
            $all_have_threshold_or_free = false;
        }
    }

    // A country feed cannot express postcode-specific differences. Use the
    // highest matching fixed rate so Google never advertises a lower charge
    // than checkout can legitimately apply somewhere in that country.
    if ( $all_costs_known && ! empty( $costs ) ) {
        $result['cost'] = max( $costs );
    }
    // Only promise a country-wide free threshold when every non-free matching
    // zone has one. The maximum threshold is the conservative country value.
    if ( $all_have_threshold_or_free && ! empty( $thresholds ) ) {
        $result['free_threshold'] = max( $thresholds );
    }

    return $result;
}

function mdo_gmf_vendor_shipping_v1( int $vendor_id, string $country ): array {
    static $cache = array();

    $country = mdo_gmf_country_code_v1( $country );
    $cache_key = $vendor_id . ':' . $country;
    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $type = mdo_gmf_vendor_shipping_type_v1( $vendor_id );

    $shipping = 'by_country' === $type
        ? mdo_gmf_country_shipping_by_country_v1( $vendor_id, $country )
        : mdo_gmf_country_shipping_by_zone_v1( $vendor_id, $country );

    $destination = array( 'country' => $country, 'postcode' => '' );
    $shipping['can_ship'] = (bool) apply_filters(
        'mdo_shipping_vendor_can_ship_to',
        (bool) $shipping['can_ship'],
        $vendor_id,
        $destination,
        $type
    );

    $minimum = function_exists( 'mdo_sst_minimum_order' ) ? (float) mdo_sst_minimum_order( $vendor_id ) : 0.0;
    if ( $minimum <= 0 ) {
        $legacy = get_user_meta( $vendor_id, '_wcfm_min_order_amt', true );
        $minimum = is_numeric( $legacy ) ? max( 0.0, (float) $legacy ) : 0.0;
    }
    $shipping['minimum_order'] = max( 0.0, $minimum );

    return $cache[ $cache_key ] = (array) apply_filters( 'mdo_google_merchant_shipping_info', $shipping, $vendor_id, $country );
}

function mdo_gmf_vendor_country_codes_v1( int $vendor_id ): array {
    $type = mdo_gmf_vendor_shipping_type_v1( $vendor_id );
    $codes = array();

    if ( 'by_country' === $type ) {
        $rates = get_user_meta( $vendor_id, '_wcfmmp_shipping_rates', true );
        if ( empty( $rates ) ) {
            $rates = get_user_meta( $vendor_id, 'wcfmmp_shipping_rates', true );
        }
        foreach ( (array) $rates as $rate ) {
            if ( ! is_array( $rate ) ) {
                continue;
            }
            $code = mdo_gmf_country_code_v1( (string) ( $rate['wcfmmp_country_to'] ?? '' ) );
            if ( '' !== $code ) {
                $codes[] = $code;
            }
        }
    } elseif ( function_exists( 'mdo_catalog_public_vendor_country_codes_20260820' ) ) {
        $codes = (array) mdo_catalog_public_vendor_country_codes_20260820( $vendor_id );
    }

    $codes = (array) apply_filters( 'mdo_shipping_vendor_country_codes', $codes, $vendor_id, $type );
    $codes = array_values( array_unique( array_filter( array_map( 'mdo_gmf_country_code_v1', $codes ) ) ) );
    sort( $codes );
    return $codes;
}
