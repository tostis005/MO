<?php
/**
 * Plugin Name: MDO — Destinos de envío por productor
 * Description: Exige un país expresamente configurado por cada productor; informa y permite retirar sólo los artículos incompatibles.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Vendor_Country_Guard {
    private static $countries = array();

    public static function init(): void {
        add_filter( 'woocommerce_cart_shipping_packages', array( __CLASS__, 'version_packages' ), PHP_INT_MAX );
        add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_rates' ), PHP_INT_MAX, 2 );
        add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout' ), PHP_INT_MAX, 2 );
        add_action( 'woocommerce_store_api_cart_errors', array( __CLASS__, 'validate_store_api' ), PHP_INT_MAX, 2 );
        add_action( 'woocommerce_before_cart_totals', array( __CLASS__, 'cart_panel' ), 5 );
        add_action( 'template_redirect', array( __CLASS__, 'remove_incompatible' ), 5 );
    }

    private static function country( $code ): string {
        $code = strtoupper( trim( (string) $code ) );
        return preg_match( '/^[A-Z]{2}$/D', $code ) ? $code : '';
    }

    public static function mode( int $vendor ): string {
        $meta = get_user_meta( $vendor, '_wcfmmp_shipping', true );
        if ( ! is_array( $meta ) || 'no' === ( $meta['_wcfmmp_user_shipping_enable'] ?? '' ) ) { return ''; }
        return (string) ( $meta['_wcfmmp_user_shipping_type'] ?? '' );
    }

    private static function codes_from_locations( array $locations ): array {
        $codes = array();
        foreach ( $locations as $location ) {
            $loc = (array) $location;
            $type = $loc['type'] ?? $loc['location_type'] ?? '';
            $code = strtoupper( (string) ( $loc['code'] ?? $loc['location_code'] ?? '' ) );
            if ( 'country' === $type ) { $codes[] = self::country( $code ); }
            elseif ( 'state' === $type ) { $codes[] = self::country( explode( ':', $code )[0] ); }
            elseif ( 'continent' === $type && WC()->countries ) {
                $continents = WC()->countries->get_continents();
                $codes = array_merge( $codes, (array) ( $continents[$code]['countries'] ?? array() ) );
            }
        }
        return array_values( array_unique( array_filter( array_map( array( __CLASS__, 'country' ), $codes ) ) ) );
    }

    /** An enabled placeholder with no saved settings is not a configured tariff. */
    private static function configured_method( array $row ): bool {
        $method = strtolower( (string) ( $row['method_id'] ?? '' ) );
        if ( '' === $method || false !== strpos( $method, 'local_pickup' ) ) { return false; }
        $settings = maybe_unserialize( $row['settings'] ?? null );
        if ( ! is_array( $settings ) || ! $settings ) { return false; }
        if ( 'flat_rate' === $method ) {
            foreach ( $settings as $key => $value ) {
                if ( ( 'cost' === $key || 0 === strpos( (string) $key, 'class_cost_' ) ) && is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                    return true; // Explicit zero and native formulas are both valid.
                }
            }
            return false;
        }
        return true;
    }

    /** Only vendor-owned explicit destinations; never the WooCommerce catch-all or global rates. */
    public static function allowed_countries( int $vendor ): array {
        if ( isset( self::$countries[$vendor] ) ) { return self::$countries[$vendor]; }
        $codes = array();
        $mode = self::mode( $vendor );
        if ( 'by_zone' === $mode && class_exists( 'WC_Shipping_Zones' ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
            $locations = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT zone_id, method_id, settings FROM {$table} WHERE vendor_id = %d AND is_enabled = 1 AND zone_id > 0", $vendor
            ), ARRAY_A );
            $ids = array();
            foreach ( (array) $rows as $row ) {
                if ( self::configured_method( $row ) ) { $ids[(int) $row['zone_id']] = true; }
            }
            $overrides = $wpdb->get_results( $wpdb->prepare(
                "SELECT zone_id, location_code, location_type FROM {$locations} WHERE vendor_id = %d", $vendor
            ), ARRAY_A );
            if ( ! empty( $wpdb->last_error ) ) { return self::$countries[$vendor] = array(); }
            $by_zone = array();
            foreach ( (array) $overrides as $loc ) { $by_zone[(int) $loc['zone_id']][] = $loc; }
            foreach ( array_keys( $ids ) as $id ) {
                $zone = WC_Shipping_Zones::get_zone( $id );
                if ( ! $zone || (int) $zone->get_id() !== $id ) { continue; }
                $zone_codes = self::codes_from_locations( (array) $zone->get_zone_locations() );
                $vendor_codes = self::codes_from_locations( $by_zone[$id] ?? array() );
                if ( $vendor_codes ) { $zone_codes = array_intersect( $zone_codes, $vendor_codes ); }
                $codes = array_merge( $codes, $zone_codes );
            }
        } elseif ( in_array( $mode, array( 'by_country', 'by_weight' ), true ) ) {
            $key = 'by_country' === $mode ? '_wcfmmp_shipping_rates' : '_wcfmmp_country_weight_rates';
            $rows = get_user_meta( $vendor, $key, true );
            if ( ! is_array( $rows ) ) { $rows = get_user_meta( $vendor, ltrim( $key, '_' ), true ); }
            foreach ( (array) $rows as $row ) {
                if ( ! is_array( $row ) ) { continue; }
                $code = self::country( $row['wcfmmp_country_to'] ?? $row['wcfmmp_weight_country'] ?? $row['wcfmmp_country'] ?? '' );
                if ( $code ) { $codes[] = $code; }
            }
        }
        $codes = array_values( array_unique( array_filter( $codes ) ) );
        sort( $codes );
        return self::$countries[$vendor] = $codes;
    }

    public static function can_ship( int $vendor, string $country ): bool {
        return $vendor > 0 && in_array( self::country( $country ), self::allowed_countries( $vendor ), true );
    }

    private static function vendor( array $item ): int {
        $id = absint( $item['product_id'] ?? 0 );
        if ( ! $id && isset( $item['data'] ) && $item['data'] instanceof WC_Product ) {
            $id = $item['data']->get_parent_id() ?: $item['data']->get_id();
        }
        return $id ? (int) get_post_field( 'post_author', $id ) : 0;
    }

    private static function needs_shipping( array $item ): bool {
        $product = $item['data'] ?? null;
        return ! $product instanceof WC_Product || $product->needs_shipping();
    }

    /** Return precisely the affected cart keys, preserving valid products and variations. */
    public static function incompatible( array $items, string $country ): array {
        $country = self::country( $country );
        if ( '' === $country ) { return array(); } // Wait until the customer has chosen a destination.
        $blocked = array();
        foreach ( $items as $key => $item ) {
            if ( ! self::needs_shipping( $item ) ) { continue; }
            $vendor = self::vendor( $item );
            if ( ! self::can_ship( $vendor, $country ) ) { $blocked[$key] = array( 'vendor' => $vendor, 'item' => $item ); }
        }
        return $blocked;
    }

    public static function version_packages( array $packages ): array {
        foreach ( $packages as &$package ) {
            $country = (string) ( $package['destination']['country'] ?? '' );
            $package['mdo_country_guard_v1'] = array_keys( self::incompatible( (array) ( $package['contents'] ?? array() ), $country ) );
        }
        unset( $package );
        return $packages;
    }

    public static function filter_rates( array $rates, array $package ): array {
        $country = (string) ( $package['destination']['country'] ?? '' );
        return self::incompatible( (array) ( $package['contents'] ?? array() ), $country ) ? array() : $rates;
    }

    private static function current_country( $cart ): string {
        $customer = $cart && method_exists( $cart, 'get_customer' ) ? $cart->get_customer() : WC()->customer;
        return $customer ? self::country( $customer->get_shipping_country() ) : '';
    }

    private static function producer_name( int $vendor ): string {
        if ( $vendor && function_exists( 'wcfmmp_get_store' ) ) {
            $store = wcfmmp_get_store( $vendor );
            if ( $store && is_callable( array( $store, 'get_shop_name' ) ) ) {
                $name = $store->get_shop_name();
                if ( $name ) { return wp_strip_all_tags( $name ); }
            }
        }
        $user = $vendor ? get_userdata( $vendor ) : false;
        return $user ? $user->display_name : 'Productor';
    }

    private static function item_name( array $item ): string {
        $product = $item['data'] ?? null;
        return $product instanceof WC_Product ? wp_strip_all_tags( $product->get_name() ) : get_the_title( absint( $item['product_id'] ?? 0 ) );
    }

    private static function country_name( string $code ): string {
        $countries = WC()->countries ? WC()->countries->get_countries() : array();
        return (string) ( $countries[$code] ?? $code );
    }

    private static function english(): bool {
        return 0 === strpos( strtolower( (string) determine_locale() ), 'en' );
    }

    private static function message( array $blocked, string $country ): string {
        $labels = array();
        foreach ( $blocked as $entry ) {
            $labels[] = self::producer_name( $entry['vendor'] ) . ': ' . self::item_name( $entry['item'] );
        }
        return sprintf( self::english()
            ? 'These products cannot be shipped to %1$s: %2$s. Remove them from your cart or change your delivery address to continue.'
            : 'Estos productos no se pueden enviar a %1$s: %2$s. Retíralos del carrito o cambia la dirección de entrega para continuar.',
            self::country_name( $country ), implode( '; ', array_unique( $labels ) ) );
    }

    public static function validate_checkout( $data, $errors ): void {
        if ( ! is_array( $data ) || ! $errors instanceof WP_Error || ! WC()->cart ) { return; }
        $ship = ! empty( $data['ship_to_different_address'] ) && ! wc_ship_to_billing_address_only();
        $country = self::country( $data[$ship ? 'shipping_country' : 'billing_country'] ?? '' );
        $blocked = self::incompatible( WC()->cart->get_cart(), $country );
        if ( $blocked ) { $errors->add( 'mdo_vendor_country', self::message( $blocked, $country ) ); }
    }

    public static function validate_store_api( $errors, $cart ): void {
        if ( ! $errors instanceof WP_Error || ! $cart instanceof WC_Cart ) { return; }
        $country = self::current_country( $cart );
        $blocked = self::incompatible( $cart->get_cart(), $country );
        if ( $blocked ) { $errors->add( 'mdo_vendor_country', self::message( $blocked, $country ) ); }
    }

    /** This hook is inside the classic cart totals fragment, including AJAX recalculation. */
    public static function cart_panel(): void {
        if ( ! WC()->cart ) { return; }
        $country = self::current_country( WC()->cart );
        $blocked = self::incompatible( WC()->cart->get_cart(), $country );
        if ( ! $blocked ) { return; }
        $en = self::english();
        $groups = array();
        foreach ( $blocked as $key => $entry ) { $groups[$entry['vendor']][$key] = $entry['item']; }
        ?>
        <section class="mdo-country-guard" role="alert" style="box-sizing:border-box;width:100%;margin:0 0 16px;padding:16px;border:1px solid #efb6b1;border-left:4px solid #b42318;border-radius:10px;background:#fff5f3;color:#672119;text-align:left;overflow-wrap:anywhere">
            <strong style="display:block;color:#8c2119;font-size:1.05em"><?php echo esc_html( sprintf( $en ? 'Products not available for delivery to %s' : 'Productos no disponibles para envío a %s', self::country_name( $country ) ) ); ?></strong>
            <?php foreach ( $groups as $vendor => $items ) : ?>
                <p style="margin:12px 0 3px;color:#672119"><strong><?php echo esc_html( self::producer_name( $vendor ) ); ?></strong></p>
                <ul style="margin:0 0 8px;padding-left:20px;color:#672119;list-style:disc">
                    <?php foreach ( $items as $key => $item ) : ?>
                        <li style="margin:4px 0;color:#672119"><?php echo esc_html( self::item_name( $item ) . ' × ' . (int) ( $item['quantity'] ?? 1 ) ); ?> —
                            <a style="color:#8c2119;text-decoration:underline" href="<?php echo esc_url( wc_get_cart_remove_url( $key ) ); ?>" aria-label="<?php echo esc_attr( ( $en ? 'Remove ' : 'Eliminar ' ) . self::item_name( $item ) ); ?>"><?php echo esc_html( $en ? 'Remove' : 'Eliminar' ); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
            <p style="margin:10px 0;color:#672119"><?php echo esc_html( $en ? 'Remove these products or change the delivery country. The rest of your cart will remain unchanged.' : 'Retira estos productos o cambia el país de entrega. El resto de tu carrito se conservará.' ); ?></p>
            <form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post" style="margin:0">
                <?php wp_nonce_field( 'mdo_remove_incompatible', 'mdo_country_nonce' ); ?>
                <input type="hidden" name="mdo_country_action" value="remove">
                <input type="hidden" name="mdo_country_destination" value="<?php echo esc_attr( $country ); ?>">
                <button type="submit" class="button" style="box-sizing:border-box;width:100%;white-space:normal;padding:12px;background:#8c2119;color:#fff;border:0;border-radius:8px"><?php echo esc_html( $en ? 'Remove unavailable products' : 'Eliminar productos no disponibles' ); ?></button>
            </form>
        </section>
        <?php
    }

    /** A deliberate POST, nonce protected; recompute against the current cart and country. */
    public static function remove_incompatible(): void {
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'remove' !== ( $_POST['mdo_country_action'] ?? '' ) || ! function_exists( 'is_cart' ) || ! is_cart() || ! WC()->cart ) { return; }
        $nonce = isset( $_POST['mdo_country_nonce'] ) && is_string( $_POST['mdo_country_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mdo_country_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'mdo_remove_incompatible' ) ) { return; }
        $country = self::current_country( WC()->cart );
        $submitted = isset( $_POST['mdo_country_destination'] ) && is_string( $_POST['mdo_country_destination'] ) ? self::country( wp_unslash( $_POST['mdo_country_destination'] ) ) : '';
        if ( $submitted === $country && '' !== $country ) {
            $blocked = self::incompatible( WC()->cart->get_cart(), $country );
            foreach ( array_keys( $blocked ) as $key ) { WC()->cart->remove_cart_item( $key ); }
            if ( $blocked ) {
                WC()->cart->calculate_totals();
                wc_add_notice( self::english() ? 'Unavailable products were removed. The rest of your cart is unchanged.' : 'Se han retirado los productos no disponibles. El resto de tu carrito se conserva.', 'success' );
            }
        } else {
            wc_add_notice( self::english() ? 'The delivery country changed. Review your cart before removing products.' : 'El país de entrega ha cambiado. Revisa el carrito antes de retirar productos.', 'notice' );
        }
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }
}
MDO_Vendor_Country_Guard::init();
