<?php
/**
 * Tropitop shipping safeguard. No guessed price is ever substituted for
 * the supplier's destination/weight-dependent shipping rates.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Tropitop_Shipping {
    private const COUNTRIES = array( 'ES', 'PT', 'FR', 'NL', 'LU', 'PL', 'DK', 'SE', 'AT', 'RO', 'FI' );

    public static function init(): void {
        add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_rates' ), 9998, 2 );
        add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout' ), 10, 2 );
        add_filter( 'mdo_shipping_vendor_can_ship_to', array( __CLASS__, 'vendor_can_ship' ), 10, 4 );
    }

    public static function is_ready(): bool {
        // Enabled only after the country+weight matrix has been checked in
        // the vendor's WCFM settings against real source checkout quotes.
        return (bool) get_option( 'mdo_tropitop_shipping_rates_verified', false );
    }

    public static function vendor_id(): int {
        global $wpdb;
        $table = MDO_Database::table( 'suppliers' );
        return absint( $wpdb->get_var( $wpdb->prepare(
            "SELECT vendor_user_id FROM {$table} WHERE code = %s LIMIT 1", 'tropitop'
        ) ) );
    }

    public static function allowed( string $country, string $postcode ): bool {
        $country = strtoupper( trim( $country ) );
        $code = preg_replace( '/\s+/', '', strtoupper( $postcode ) );
        if ( ! in_array( $country, self::COUNTRIES, true ) ) { return false; }
        if ( 'ES' === $country ) {
            if ( strlen( $code ) < 2 ) { return false; } // postcode required for islands
            return ! in_array( substr( $code, 0, 2 ), array( '07', '35', '38', '51', '52' ), true );
        }
        if ( 'PT' === $country ) {
            if ( strlen( $code ) < 4 ) { return false; }
            $first = (int) substr( $code, 0, 4 );
            return $first < 9000; // Madeira and Azores
        }
        return true;
    }

    public static function vendor_can_ship( bool $available, int $vendor_id, array $destination, string $type ): bool {
        unset( $type );
        if ( self::vendor_id() !== $vendor_id ) { return $available; }
        return $available && self::is_ready() && self::allowed(
            (string) ( $destination['country'] ?? '' ),
            (string) ( $destination['postcode'] ?? '' )
        );
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

    public static function filter_rates( array $rates, array $package ): array {
        $vendor = self::vendor_id();
        if ( ! $vendor ) { return $rates; }
        $package_vendor = absint( $package['vendor_id'] ?? 0 );
        $contains = $package_vendor ? $package_vendor === $vendor : self::contains_tropitop( (array) ( $package['contents'] ?? array() ) );
        if ( ! $contains ) { return $rates; }
        $dst = (array) ( $package['destination'] ?? array() );
        if ( ! self::is_ready() || ! self::allowed( (string) ( $dst['country'] ?? '' ), (string) ( $dst['postcode'] ?? '' ) ) ) {
            return array();
        }
        return $rates;
    }

    public static function validate_checkout( $data, $errors ): void {
        if ( ! $errors instanceof WP_Error || ! function_exists( 'WC' ) || ! WC()->cart ||
            ! self::contains_tropitop( WC()->cart->get_cart() ) ) { return; }
        if ( ! self::is_ready() ) {
            $errors->add( 'mdo_tropitop_rates_missing',
                'Los gastos de envío de Tropitop están pendientes de verificación y sus productos todavía no están disponibles para comprar.' );
            return;
        }
        $ship = ! empty( $data['ship_to_different_address'] );
        $country = (string) ( $data[ $ship ? 'shipping_country' : 'billing_country' ] ?? '' );
        $postcode = (string) ( $data[ $ship ? 'shipping_postcode' : 'billing_postcode' ] ?? '' );
        if ( ! self::allowed( $country, $postcode ) ) {
            $errors->add( 'mdo_tropitop_destination',
                'Tropitop no realiza envíos a este destino. Selecciona una dirección admitida o elimina sus productos del carrito.' );
        }
    }
}
