<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backend shipping-destination resolver for EMDO.
 *
 * This class deliberately does not render anything on the storefront. It only
 * exposes the data and eligibility methods that the future destination selector
 * and shop filter will consume.
 */
final class MDO_Shipping_Destinations {
	private const DEFAULT_COUNTRY = 'ES';
	private const CACHE_KEY       = 'mdo_supported_shipping_countries_v1';
	private const CACHE_TTL       = 30 * MINUTE_IN_SECONDS;

	public static function init(): void {
		add_action( 'added_user_meta', array( __CLASS__, 'maybe_invalidate_from_user_meta' ), 10, 4 );
		add_action( 'updated_user_meta', array( __CLASS__, 'maybe_invalidate_from_user_meta' ), 10, 4 );
		add_action( 'deleted_user_meta', array( __CLASS__, 'maybe_invalidate_from_user_meta' ), 10, 4 );
		add_action( 'mdo_shipping_destinations_invalidate', array( __CLASS__, 'invalidate_cache' ) );
	}

	/** Default destination requested for the future storefront selector. */
	public static function default_destination(): array {
		return array(
			'country'  => self::DEFAULT_COUNTRY,
			'postcode' => '',
		);
	}

	/**
	 * Normalise a customer-selected destination. No geolocation is performed.
	 * An empty/invalid country falls back to Spain.
	 */
	public static function normalize_destination( string $country = self::DEFAULT_COUNTRY, string $postcode = '' ): array {
		$country = self::normalize_country_code( $country );
		if ( '' === $country ) {
			$country = self::DEFAULT_COUNTRY;
		}

		if ( function_exists( 'wc_format_postcode' ) ) {
			$postcode = wc_format_postcode( $postcode, $country );
		} else {
			$postcode = strtoupper( preg_replace( '/\s+/', '', sanitize_text_field( $postcode ) ) );
		}

		return array(
			'country'  => $country,
			'postcode' => trim( (string) $postcode ),
		);
	}

	/**
	 * Countries that at least one active WCFM producer can actually ship to.
	 * The result is code => label, with Spain first and the rest alphabetically.
	 */
	public static function supported_countries( bool $refresh = false ): array {
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$codes = array();
		foreach ( self::vendor_ids() as $vendor_id ) {
			foreach ( self::vendor_country_codes( $vendor_id ) as $code ) {
				$codes[ $code ] = true;
			}
		}

		$labels = array();
		foreach ( array_keys( $codes ) as $code ) {
			$labels[ $code ] = self::country_name( $code );
		}

		uasort(
			$labels,
			static function( string $a, string $b ): int {
				return strcasecmp( remove_accents( $a ), remove_accents( $b ) );
			}
		);

		if ( isset( $labels[ self::DEFAULT_COUNTRY ] ) ) {
			$spain  = array( self::DEFAULT_COUNTRY => $labels[ self::DEFAULT_COUNTRY ] );
			$labels = $spain + $labels;
		}

		$labels = (array) apply_filters( 'mdo_shipping_supported_countries', $labels );
		set_transient( self::CACHE_KEY, $labels, self::CACHE_TTL );

		return $labels;
	}

	/** Country codes available for one vendor from their live WCFM configuration. */
	public static function vendor_country_codes( int $vendor_id ): array {
		$vendor_id = absint( $vendor_id );
		if ( ! $vendor_id ) {
			return array();
		}

		$shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
		$type     = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';
		$codes    = 'by_country' === $type
			? self::country_rate_codes( $vendor_id )
			: self::zone_country_codes( $vendor_id );

		$codes = array_values( array_unique( array_filter( array_map( array( __CLASS__, 'normalize_country_code' ), $codes ) ) ) );
		sort( $codes );

		return (array) apply_filters( 'mdo_shipping_vendor_country_codes', $codes, $vendor_id, $type );
	}

	/** Whether a producer can ship to the selected country/postcode. */
	public static function vendor_can_ship_to( int $vendor_id, string $country = self::DEFAULT_COUNTRY, string $postcode = '' ): bool {
		$destination = self::normalize_destination( $country, $postcode );
		$vendor_id   = absint( $vendor_id );
		if ( ! $vendor_id ) {
			return false;
		}

		$shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
		$type     = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';

		if ( 'by_country' === $type ) {
			$available = in_array( $destination['country'], self::country_rate_codes( $vendor_id ), true );
			return (bool) apply_filters( 'mdo_shipping_vendor_can_ship_to', $available, $vendor_id, $destination, $type );
		}

		$available = self::zones_match_destination( $vendor_id, $destination );
		return (bool) apply_filters( 'mdo_shipping_vendor_can_ship_to', $available, $vendor_id, $destination, $type );
	}

	/** Product-level convenience method; WCFM vendors are the product authors. */
	public static function product_can_ship_to( int $product_id, string $country = self::DEFAULT_COUNTRY, string $postcode = '' ): bool {
		$product_id = absint( $product_id );
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		$vendor_id = (int) get_post_field( 'post_author', $product_id );
		return self::vendor_can_ship_to( $vendor_id, $country, $postcode );
	}

	public static function invalidate_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	public static function maybe_invalidate_from_user_meta( $meta_id, $user_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $user_id, $meta_value );
		$key = (string) $meta_key;
		if ( 0 === strpos( $key, '_wcfmmp_shipping' ) || 'wcfmmp_shipping_rates' === $key ) {
			self::invalidate_cache();
		}
	}

	private static function vendor_ids(): array {
		$ids = get_users(
			array(
				'role'   => 'wcfm_vendor',
				'fields' => 'ids',
			)
		);

		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		return (array) apply_filters( 'mdo_shipping_destination_vendor_ids', $ids );
	}

	private static function country_rate_codes( int $vendor_id ): array {
		$rates = get_user_meta( $vendor_id, '_wcfmmp_shipping_rates', true );
		if ( empty( $rates ) ) {
			$rates = get_user_meta( $vendor_id, 'wcfmmp_shipping_rates', true );
		}
		if ( ! is_array( $rates ) ) {
			return array();
		}

		$codes = array();
		foreach ( $rates as $rate ) {
			if ( ! is_array( $rate ) ) {
				continue;
			}
			$code = self::normalize_country_code( (string) ( $rate['wcfmmp_country_to'] ?? '' ) );
			if ( $code ) {
				$codes[] = $code;
			}
		}

		return array_values( array_unique( $codes ) );
	}

	private static function zone_country_codes( int $vendor_id ): array {
		$zones = self::shipping_zones( $vendor_id );
		$codes = array();

		foreach ( $zones as $zone ) {
			if ( ! self::zone_has_delivery_method( $zone ) ) {
				continue;
			}
			$codes = array_merge( $codes, self::country_codes_from_zone( $zone ) );
		}

		return array_values( array_unique( $codes ) );
	}

	private static function zones_match_destination( int $vendor_id, array $destination ): bool {
		foreach ( self::shipping_zones( $vendor_id ) as $zone ) {
			if ( ! self::zone_has_delivery_method( $zone ) ) {
				continue;
			}
			if ( ! in_array( $destination['country'], self::country_codes_from_zone( $zone ), true ) ) {
				continue;
			}
			if ( '' !== $destination['postcode'] && ! self::zone_matches_postcode( $zone, $destination['country'], $destination['postcode'] ) ) {
				continue;
			}
			return true;
		}

		return false;
	}

	private static function shipping_zones( int $vendor_id ): array {
		if ( ! function_exists( 'wcfmmp_get_shipping_zone' ) ) {
			return array();
		}
		$zones = wcfmmp_get_shipping_zone( '', $vendor_id );
		return is_array( $zones ) ? $zones : array();
	}

	private static function zone_has_delivery_method( $zone ): bool {
		$methods = self::get_value( $zone, 'shipping_methods', array() );
		if ( ! is_array( $methods ) ) {
			return false;
		}

		foreach ( $methods as $method ) {
			$enabled = strtolower( (string) self::get_value( $method, 'is_enabled', self::get_value( $method, 'enabled', 1 ) ) );
			if ( in_array( $enabled, array( '0', 'no', 'false' ), true ) ) {
				continue;
			}
			$method_id = strtolower( (string) self::get_value( $method, 'method_id', self::get_value( $method, 'id', '' ) ) );
			if ( false !== strpos( $method_id, 'local_pickup' ) ) {
				continue;
			}
			return true;
		}

		return false;
	}

	private static function country_codes_from_zone( $zone ): array {
		$codes     = array();
		$locations = self::get_value( $zone, 'zone_locations', self::get_value( $zone, 'locations', array() ) );
		$locations = is_array( $locations ) ? $locations : array();

		foreach ( $locations as $location ) {
			$type = strtolower( (string) self::get_value( $location, 'location_type', self::get_value( $location, 'type', '' ) ) );
			$code = trim( (string) self::get_value( $location, 'location_code', self::get_value( $location, 'code', '' ) ) );

			if ( 'country' === $type ) {
				$country = self::normalize_country_code( $code );
				if ( $country ) {
					$codes[] = $country;
				}
				continue;
			}

			if ( 'state' === $type && false !== strpos( $code, ':' ) ) {
				$country = self::normalize_country_code( strtok( $code, ':' ) );
				if ( $country ) {
					$codes[] = $country;
				}
				continue;
			}

			if ( 'continent' === $type && $code ) {
				$codes = array_merge( $codes, self::continent_country_codes( $code ) );
			}
		}

		if ( empty( $codes ) && self::zone_looks_spanish( $zone ) ) {
			$codes[] = self::DEFAULT_COUNTRY;
		}

		return array_values( array_unique( array_filter( $codes ) ) );
	}

	private static function continent_country_codes( string $continent_code ): array {
		$continent_code = strtoupper( trim( $continent_code ) );
		$countries      = function_exists( 'WC' ) && WC()->countries ? WC()->countries : ( class_exists( 'WC_Countries' ) ? new WC_Countries() : null );
		if ( ! $countries || ! is_callable( array( $countries, 'get_continents' ) ) ) {
			return array();
		}

		$continents = $countries->get_continents();
		$list       = $continents[ $continent_code ]['countries'] ?? array();
		return array_values( array_filter( array_map( array( __CLASS__, 'normalize_country_code' ), (array) $list ) ) );
	}

	private static function zone_matches_postcode( $zone, string $country, string $postcode ): bool {
		$locations = self::get_value( $zone, 'zone_locations', self::get_value( $zone, 'locations', array() ) );
		$locations = is_array( $locations ) ? $locations : array();
		$patterns  = array();

		foreach ( $locations as $location ) {
			$type = strtolower( (string) self::get_value( $location, 'location_type', self::get_value( $location, 'type', '' ) ) );
			if ( 'postcode' === $type ) {
				$patterns[] = trim( (string) self::get_value( $location, 'location_code', self::get_value( $location, 'code', '' ) ) );
			}
		}

		if ( $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( self::postcode_matches_pattern( $postcode, $pattern ) ) {
					return true;
				}
			}
			return false;
		}

		if ( self::DEFAULT_COUNTRY === $country ) {
			$state_rule = self::spanish_state_postcode_match( $zone, $postcode );
			if ( null !== $state_rule ) {
				return $state_rule;
			}

			$spanish_rule = self::spanish_zone_postcode_match( $zone, $postcode );
			if ( null !== $spanish_rule ) {
				return $spanish_rule;
			}
		}

		return true;
	}

	private static function postcode_matches_pattern( string $postcode, string $pattern ): bool {
		$postcode = strtoupper( preg_replace( '/\s+/', '', $postcode ) );
		$pattern  = strtoupper( preg_replace( '/\s+/', '', $pattern ) );
		if ( '' === $postcode || '' === $pattern ) {
			return false;
		}

		if ( false !== strpos( $pattern, '...' ) ) {
			list( $from, $to ) = array_pad( explode( '...', $pattern, 2 ), 2, '' );
			if ( ctype_digit( $postcode ) && ctype_digit( $from ) && ctype_digit( $to ) ) {
				$value = (int) $postcode;
				return $value >= (int) $from && $value <= (int) $to;
			}
		}

		if ( false !== strpos( $pattern, '*' ) ) {
			$regex = '/^' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';
			return 1 === preg_match( $regex, $postcode );
		}

		return $postcode === $pattern;
	}

	/** Match WooCommerce Spain state zones against the first two postcode digits. */
	private static function spanish_state_postcode_match( $zone, string $postcode ): ?bool {
		$zip = preg_replace( '/\D+/', '', $postcode );
		if ( strlen( $zip ) < 2 ) {
			return null;
		}

		$prefix_to_state = array(
			'01' => 'VI', '02' => 'AB', '03' => 'A',  '04' => 'AL', '05' => 'AV', '06' => 'BA',
			'07' => 'PM', '08' => 'B',  '09' => 'BU', '10' => 'CC', '11' => 'CA', '12' => 'CS',
			'13' => 'CR', '14' => 'CO', '15' => 'C',  '16' => 'CU', '17' => 'GI', '18' => 'GR',
			'19' => 'GU', '20' => 'SS', '21' => 'H',  '22' => 'HU', '23' => 'J',  '24' => 'LE',
			'25' => 'L',  '26' => 'LO', '27' => 'LU', '28' => 'M',  '29' => 'MA', '30' => 'MU',
			'31' => 'NA', '32' => 'OR', '33' => 'O',  '34' => 'P',  '35' => 'GC', '36' => 'PO',
			'37' => 'SA', '38' => 'TF', '39' => 'S',  '40' => 'SG', '41' => 'SE', '42' => 'SO',
			'43' => 'T',  '44' => 'TE', '45' => 'TO', '46' => 'V',  '47' => 'VA', '48' => 'BI',
			'49' => 'ZA', '50' => 'Z',  '51' => 'CE', '52' => 'ML',
		);
		$selected_state = $prefix_to_state[ substr( $zip, 0, 2 ) ] ?? '';
		if ( '' === $selected_state ) {
			return null;
		}

		$locations = self::get_value( $zone, 'zone_locations', self::get_value( $zone, 'locations', array() ) );
		$locations = is_array( $locations ) ? $locations : array();
		$states    = array();
		foreach ( $locations as $location ) {
			$type = strtolower( (string) self::get_value( $location, 'location_type', self::get_value( $location, 'type', '' ) ) );
			$code = strtoupper( trim( (string) self::get_value( $location, 'location_code', self::get_value( $location, 'code', '' ) ) ) );
			if ( 'state' === $type && 0 === strpos( $code, 'ES:' ) ) {
				$states[] = substr( $code, 3 );
			}
		}

		if ( empty( $states ) ) {
			return null;
		}

		return in_array( $selected_state, $states, true );
	}

	/**
	 * Resolve the Spain-only regions the future optional postcode field needs.
	 * Returns null when the zone name does not describe one of these broad areas.
	 */
	private static function spanish_zone_postcode_match( $zone, string $postcode ): ?bool {
		$name = self::normalized_zone_name( $zone );
		$zip  = preg_replace( '/\D+/', '', $postcode );
		if ( strlen( $zip ) < 2 ) {
			return null;
		}
		$prefix = substr( $zip, 0, 2 );

		$is_canary   = in_array( $prefix, array( '35', '38' ), true );
		$is_balearic = '07' === $prefix;
		$is_ceuta    = '51' === $prefix;
		$is_melilla  = '52' === $prefix;
		$is_special  = $is_canary || $is_balearic || $is_ceuta || $is_melilla;

		if ( false !== strpos( $name, 'peninsula' ) || false !== strpos( $name, 'mainland spain' ) ) {
			return ! $is_special;
		}
		if ( false !== strpos( $name, 'canarias' ) || false !== strpos( $name, 'canary' ) ) {
			return $is_canary;
		}
		if ( false !== strpos( $name, 'baleares' ) || false !== strpos( $name, 'balear' ) ) {
			return $is_balearic;
		}
		if ( false !== strpos( $name, 'ceuta' ) ) {
			return $is_ceuta;
		}
		if ( false !== strpos( $name, 'melilla' ) ) {
			return $is_melilla;
		}

		return null;
	}

	private static function zone_looks_spanish( $zone ): bool {
		$name = self::normalized_zone_name( $zone );
		foreach ( array( 'espana', 'spain', 'peninsula', 'canarias', 'canary', 'baleares', 'balear', 'ceuta', 'melilla' ) as $token ) {
			if ( false !== strpos( $name, $token ) ) {
				return true;
			}
		}
		return false;
	}

	private static function normalized_zone_name( $zone ): string {
		$name = (string) self::get_value( $zone, 'zone_name', self::get_value( $zone, 'name', '' ) );
		return remove_accents( strtolower( trim( $name ) ) );
	}

	private static function country_name( string $code ): string {
		$countries = function_exists( 'WC' ) && WC()->countries ? WC()->countries : ( class_exists( 'WC_Countries' ) ? new WC_Countries() : null );
		if ( $countries && is_callable( array( $countries, 'get_countries' ) ) ) {
			$list = $countries->get_countries();
			if ( isset( $list[ $code ] ) ) {
				return (string) $list[ $code ];
			}
		}
		return $code;
	}

	private static function normalize_country_code( string $code ): string {
		$code = strtoupper( trim( $code ) );
		return 1 === preg_match( '/^[A-Z]{2}$/', $code ) ? $code : '';
	}

	private static function get_value( $source, string $key, $default = null ) {
		if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
			return $source[ $key ];
		}
		if ( is_object( $source ) && isset( $source->{$key} ) ) {
			return $source->{$key};
		}
		return $default;
	}
}
