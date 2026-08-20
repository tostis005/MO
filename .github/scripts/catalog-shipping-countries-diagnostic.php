<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$out = array(
    'generated_at' => gmdate( 'c' ),
    'siteurl'      => get_option( 'siteurl' ),
    'wcfm_function' => array(),
    'woocommerce_zones' => array(),
    'vendors' => array(),
    'resolver_supported_countries' => null,
    'frontend_supported_countries' => null,
);

if ( function_exists( 'wcfmmp_get_shipping_zone' ) ) {
    try {
        $rf = new ReflectionFunction( 'wcfmmp_get_shipping_zone' );
        $out['wcfm_function'] = array(
            'exists' => true,
            'file' => $rf->getFileName(),
            'start' => $rf->getStartLine(),
            'end' => $rf->getEndLine(),
            'parameters' => array_map(
                static function ( ReflectionParameter $p ) {
                    return array(
                        'name' => $p->getName(),
                        'optional' => $p->isOptional(),
                        'default' => $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null,
                    );
                },
                $rf->getParameters()
            ),
        );
        $lines = @file( $rf->getFileName() );
        if ( is_array( $lines ) ) {
            $start = max( 0, $rf->getStartLine() - 3 );
            $len = max( 1, $rf->getEndLine() - $start + 3 );
            $out['wcfm_function']['source'] = implode( '', array_slice( $lines, $start, $len ) );
        }
    } catch ( Throwable $e ) {
        $out['wcfm_function'] = array( 'exists' => true, 'error' => $e->getMessage() );
    }
} else {
    $out['wcfm_function'] = array( 'exists' => false );
}

if ( class_exists( 'WC_Shipping_Zones' ) ) {
    $zones = WC_Shipping_Zones::get_zones();
    foreach ( $zones as $zone_id => $zone ) {
        $out['woocommerce_zones'][ (string) $zone_id ] = array(
            'zone_id' => $zone['zone_id'] ?? $zone_id,
            'zone_name' => $zone['zone_name'] ?? '',
            'zone_order' => $zone['zone_order'] ?? null,
            'zone_locations' => $zone['zone_locations'] ?? array(),
            'shipping_methods' => array_map(
                static function ( $method ) {
                    return array(
                        'id' => is_object( $method ) ? ( $method->id ?? '' ) : '',
                        'instance_id' => is_object( $method ) ? ( $method->instance_id ?? '' ) : '',
                        'enabled' => is_object( $method ) ? ( $method->enabled ?? '' ) : '',
                        'title' => is_object( $method ) ? ( $method->title ?? '' ) : '',
                    );
                },
                (array) ( $zone['shipping_methods'] ?? array() )
            ),
        );
    }
    try {
        $rest = WC_Shipping_Zones::get_zone( 0 );
        if ( $rest ) {
            $out['woocommerce_zones']['0'] = array(
                'zone_id' => 0,
                'zone_name' => $rest->get_zone_name(),
                'zone_locations' => $rest->get_zone_locations(),
                'shipping_methods' => array_map(
                    static function ( $method ) {
                        return array(
                            'id' => $method->id ?? '',
                            'instance_id' => $method->instance_id ?? '',
                            'enabled' => $method->enabled ?? '',
                            'title' => $method->title ?? '',
                        );
                    },
                    (array) $rest->get_shipping_methods( true )
                ),
            );
        }
    } catch ( Throwable $e ) {
        $out['woocommerce_zones']['0_error'] = $e->getMessage();
    }
}

$vendor_ids = get_users( array( 'role' => 'wcfm_vendor', 'fields' => 'ids' ) );
foreach ( (array) $vendor_ids as $vendor_id ) {
    $vendor_id = absint( $vendor_id );
    $user = get_userdata( $vendor_id );
    $shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
    $rates_a = get_user_meta( $vendor_id, '_wcfmmp_shipping_rates', true );
    $rates_b = get_user_meta( $vendor_id, 'wcfmmp_shipping_rates', true );
    $profile = get_user_meta( $vendor_id, '_wcfmmp_profile_settings', true );

    $entry = array(
        'id' => $vendor_id,
        'name' => $user ? $user->display_name : '',
        'shipping' => $shipping,
        'rates_underscore' => $rates_a,
        'rates_plain' => $rates_b,
        'profile_shipping_keys' => array(),
        'resolver_country_codes' => null,
        'wcfm_calls' => array(),
    );
    if ( is_array( $profile ) ) {
        foreach ( $profile as $k => $v ) {
            if ( false !== stripos( (string) $k, 'shipping' ) || false !== stripos( (string) $k, 'country' ) ) {
                $entry['profile_shipping_keys'][ $k ] = $v;
            }
        }
    }
    if ( class_exists( 'MDO_Shipping_Destinations' ) ) {
        try { $entry['resolver_country_codes'] = MDO_Shipping_Destinations::vendor_country_codes( $vendor_id ); }
        catch ( Throwable $e ) { $entry['resolver_country_codes'] = array( 'error' => $e->getMessage() ); }
    }
    if ( function_exists( 'wcfmmp_get_shipping_zone' ) ) {
        foreach ( array(
            'empty_vendor' => array( '', $vendor_id ),
            'vendor_empty' => array( $vendor_id, '' ),
            'vendor_only'  => array( $vendor_id ),
            'empty_only'   => array( '' ),
        ) as $label => $args ) {
            try {
                $value = call_user_func_array( 'wcfmmp_get_shipping_zone', $args );
                $entry['wcfm_calls'][ $label ] = $value;
            } catch ( Throwable $e ) {
                $entry['wcfm_calls'][ $label ] = array( 'error' => get_class( $e ) . ': ' . $e->getMessage() );
            }
        }
    }
    $out['vendors'][] = $entry;
}

if ( class_exists( 'MDO_Shipping_Destinations' ) ) {
    try { $out['resolver_supported_countries'] = MDO_Shipping_Destinations::supported_countries( true ); }
    catch ( Throwable $e ) { $out['resolver_supported_countries'] = array( 'error' => $e->getMessage() ); }
}
if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
    try { $out['frontend_supported_countries'] = MDO_Catalog_Destination_Frontend::supported_countries(); }
    catch ( Throwable $e ) { $out['frontend_supported_countries'] = array( 'error' => $e->getMessage() ); }
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
