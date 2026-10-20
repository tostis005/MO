<?php
/**
 * Tropitop-specific WooCommerce shipping guard.
 *
 * The Spanish mainland weight quotes were captured from Tropitop's original
 * cart on 2026-10-20. Only ES mainland is enabled after explicit verification
 * of rates and cart calculation; all other source-advertised destinations
 * remain blocked until their independent postal-code quotes are verified.
 *
 * Never modify rates for another WCFM vendor.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Tropitop_Shipping {
    public static function init(): void {
        add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_rates' ), 9998, 2 );
        add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout' ), 10, 2 );
        add_filter( 'mdo_shipping_vendor_can_ship_to', array( __CLASS__, 'vendor_can_ship' ), 10, 4 );
    }

    public static function is_ready(): bool {
        return '1' === (string) get_option( 'mdo_tropitop_shipping_rates_verified', '0' );
    }

    public static function vendor_id(): int {
        global $wpdb;
        $table = MDO_Database::table( 'suppliers' );
        return absint( $wpdb->get_var( $wpdb->prepare(
            "SELECT vendor_user_id FROM {$table} WHERE code = %s LIMIT 1", 'tropitop'
        ) ) );
    }

    /**
     * Only validated region for actual checkout.
     * No country-only shipping quotes: need a postcode before checkout.
     */
    public static function allowed( string $country, string $postcode ): bool {
        $code = strtoupper( preg_replace( '/\s+/', '', $postcode ) );
        if ( strtoupper( trim( $country ) ) !== 'ES' ) { return false; }
        if ( ! preg_match( '/^\d{5}$/', $code ) ) { return false; }
        return ! in_array( substr( $code, 0, 2 ), array( '07', '35', '38', '51', '52' ), true );
    }

    public static function vendor_can_ship( bool $available, int $vendor_id, array $destination, string $type ): bool {
        unset( $type );
        if ( self::vendor_id() !== $vendor_id ) { return $available; }
        return $available && self::is_ready() && self::allowed(
            (string) ( $destination['country'] ?? '' ),
            (string) ( $destination['postcode'] ?? '' )
        );
    }

    private static function is_vendor_package( array $package ): bool {
        $vendor = self::vendor_id();
        if ( ! $vendor || empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) { return false; }
        if ( ! empty( $package['vendor_id'] ) && absint( $package['vendor_id'] ) !== $vendor ) { return false; }
        foreach ( $package['contents'] as $item ) {
            $id = absint( $item['product_id'] ?? 0 );
            if ( ! $id || (int) get_post_field( 'post_author', $id ) !== $vendor ) { return false; }
        }
        return true;
    }

    private static function contains_tropitop( array $items ): bool {
        $vendor = self::vendor_id();
        if ( ! $vendor ) { return false; }
        foreach ( $items as $item ) {
            $id = absint( $item['product_id'] ?? 0 );
            if ( $id && (int) get_post_field( 'post_author', $id ) === $vendor ) { return true; }
        }
        return false;
    }

    /** Observed bands; conservative upper amounts prevent undercharging. */
    public static function cost_for_weight( float $kg ): ?float {
        if ( $kg <= 0.0 ) { return null; }
        if ( $kg <= 5.0001 ) { return 2.95; }
        if ( $kg <= 10.0001 ) { return 5.95; }
        if ( $kg <= 15.0001 ) { return 7.95; }
        if ( $kg <= 20.0001 ) { return 10.95; }
        if ( $kg <= 25.0001 ) { return 12.95; }
        if ( $kg <= 30.0001 ) { return 15.95; }
        // The 36kg price was checked, but intermediate 30–35kg and >36kg
        // were not. Do not guess amounts for unmeasured ranges.
        if ( abs( $kg - 36.0 ) < 0.0001 ) { return 20.95; }
        return null;
    }

    public static function filter_rates( array $rates, array $package ): array {
        if ( ! self::is_vendor_package( $package ) ) { return $rates; }
        $dest = (array) ( $package['destination'] ?? array() );
        if ( ! self::is_ready() || ! self::allowed(
            (string) ( $dest['country'] ?? '' ), (string) ( $dest['postcode'] ?? '' )
        ) ) { return array(); }

        $kg = 0.0;
        foreach ( $package['contents'] as $item ) {
            $product = $item['data'] ?? null;
            if ( ! $product instanceof WC_Product ) {
                $product = wc_get_product( (int) ( $item['variation_id'] ?? $item['product_id'] ?? 0 ) );
            }
            if ( ! $product || ! is_numeric( $product->get_weight() ) || (float) $product->get_weight() <= 0 ) {
                return array();
            }
            $kg += (float) wc_get_weight( (float) $product->get_weight(), 'kg' ) *
                max( 1, (int) ( $item['quantity'] ?? 1 ) );
        }
        $cost = self::cost_for_weight( $kg );
        if ( null === $cost ) { return array(); }
        $selected = null;
        foreach ( $rates as $key => $rate ) {
            if ( is_object( $rate ) && method_exists( $rate, 'get_method_id' ) &&
                (string) $rate->get_method_id() === 'wcfmmp_product_shipping_by_zone' ) {
                $selected = $key;
                break;
            }
        }
        if ( null === $selected ) { return array(); }
        $rate = $rates[ $selected ];
        $rate->set_label( 'Envío Tropitop — península' );
        $rate->set_cost( wc_format_decimal( $cost, 2 ) );
        if ( method_exists( $rate, 'set_taxes' ) ) { $rate->set_taxes( array() ); }
        return array( $selected => $rate );
    }

    public static function validate_checkout( $data, $errors ): void {
        if ( ! $errors instanceof WP_Error || ! function_exists( 'WC' ) || ! WC()->cart ||
            ! self::contains_tropitop( WC()->cart->get_cart() ) ) { return; }
        if ( ! self::is_ready() ) {
            $errors->add( 'mdo_tropitop_rates_missing',
                'Los gastos de envío de Tropitop están pendientes de verificación; sus productos no pueden comprarse todavía.' );
            return;
        }
        $ship = ! empty( $data['ship_to_different_address'] );
        $country = (string) ( $data[ $ship ? 'shipping_country' : 'billing_country' ] ?? '' );
        $postcode = (string) ( $data[ $ship ? 'shipping_postcode' : 'billing_postcode' ] ?? '' );
        if ( ! self::allowed( $country, $postcode ) ) {
            $errors->add( 'mdo_tropitop_destination',
                'Tropitop sólo admite ahora envíos a España peninsular. Selecciona una dirección válida o retira sus productos.' );
        }
    }
}
