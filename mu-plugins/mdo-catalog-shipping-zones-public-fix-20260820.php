<?php
/**
 * Plugin Name: MDO Catalog Shipping Zones Public Fix
 * Description: Resolves public catalogue destinations from the real WCFM vendor-zone method table without requiring a logged-in WCFM user.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCFM's public helper authorizes the requested vendor against the current user.
 * On an anonymous storefront request that returns an empty zone list, even though
 * the vendor-zone methods exist in WCFM's own table. Read those associations
 * directly and combine them with WooCommerce's canonical zones instead.
 */
function mdo_catalog_public_zone_rows_20260820( int $vendor_id ): array {
	global $wpdb;
	$vendor_id = absint( $vendor_id );
	if ( ! $vendor_id ) {
		return array();
	}

	$table = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT zone_id, method_id, is_enabled FROM {$table} WHERE vendor_id = %d AND is_enabled = 1 ORDER BY zone_id ASC, instance_id ASC",
			$vendor_id
		),
		ARRAY_A
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	return is_array( $rows ) ? $rows : array();
}

function mdo_catalog_public_zone_ids_20260820( int $vendor_id ): array {
	$zone_ids = array();
	foreach ( mdo_catalog_public_zone_rows_20260820( $vendor_id ) as $row ) {
		$method = strtolower( trim( (string) ( $row['method_id'] ?? '' ) ) );
		if ( '' === $method || false !== strpos( $method, 'local_pickup' ) ) {
			continue;
		}
		$zone_ids[] = absint( $row['zone_id'] ?? 0 );
	}
	return array_values( array_unique( $zone_ids ) );
}

function mdo_catalog_public_zone_object_20260820( int $zone_id ) {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}
	try {
		return WC_Shipping_Zones::get_zone( $zone_id );
	} catch ( Throwable $e ) {
		return null;
	}
}

function mdo_catalog_public_location_value_20260820( $location, string $key ): string {
	if ( is_object( $location ) && isset( $location->{$key} ) ) {
		return (string) $location->{$key};
	}
	if ( is_array( $location ) && isset( $location[ $key ] ) ) {
		return (string) $location[ $key ];
	}
	return '';
}

function mdo_catalog_public_continent_countries_20260820( string $continent ): array {
	$continent = strtoupper( trim( $continent ) );
	$countries_obj = function_exists( 'WC' ) && WC()->countries ? WC()->countries : ( class_exists( 'WC_Countries' ) ? new WC_Countries() : null );
	if ( ! $countries_obj || ! is_callable( array( $countries_obj, 'get_continents' ) ) ) {
		return array();
	}
	$continents = $countries_obj->get_continents();
	$codes = (array) ( $continents[ $continent ]['countries'] ?? array() );
	return array_values( array_filter( array_map( static function( $code ) {
		$code = strtoupper( trim( (string) $code ) );
		return preg_match( '/^[A-Z]{2}$/', $code ) ? $code : '';
	}, $codes ) ) );
}

function mdo_catalog_public_zone_country_codes_20260820( int $zone_id ): array {
	$zone = mdo_catalog_public_zone_object_20260820( $zone_id );
	if ( ! $zone || ! is_callable( array( $zone, 'get_zone_locations' ) ) ) {
		return array();
	}

	$codes = array();
	foreach ( (array) $zone->get_zone_locations() as $location ) {
		$type = strtolower( trim( mdo_catalog_public_location_value_20260820( $location, 'type' ) ) );
		$code = strtoupper( trim( mdo_catalog_public_location_value_20260820( $location, 'code' ) ) );
		if ( 'country' === $type && preg_match( '/^[A-Z]{2}$/', $code ) ) {
			$codes[] = $code;
		} elseif ( 'state' === $type && preg_match( '/^([A-Z]{2}):/', $code, $m ) ) {
			$codes[] = $m[1];
		} elseif ( 'continent' === $type && '' !== $code ) {
			$codes = array_merge( $codes, mdo_catalog_public_continent_countries_20260820( $code ) );
		}
	}
	return array_values( array_unique( $codes ) );
}

function mdo_catalog_spanish_postcode_state_20260820( string $postcode ): string {
	$zip = preg_replace( '/\D+/', '', $postcode );
	if ( strlen( $zip ) < 2 ) {
		return '';
	}
	$map = array(
		'01'=>'VI','02'=>'AB','03'=>'A','04'=>'AL','05'=>'AV','06'=>'BA','07'=>'PM','08'=>'B','09'=>'BU','10'=>'CC',
		'11'=>'CA','12'=>'CS','13'=>'CR','14'=>'CO','15'=>'C','16'=>'CU','17'=>'GI','18'=>'GR','19'=>'GU','20'=>'SS',
		'21'=>'H','22'=>'HU','23'=>'J','24'=>'LE','25'=>'L','26'=>'LO','27'=>'LU','28'=>'M','29'=>'MA','30'=>'MU',
		'31'=>'NA','32'=>'OR','33'=>'O','34'=>'P','35'=>'GC','36'=>'PO','37'=>'SA','38'=>'TF','39'=>'S','40'=>'SG',
		'41'=>'SE','42'=>'SO','43'=>'T','44'=>'TE','45'=>'TO','46'=>'V','47'=>'VA','48'=>'BI','49'=>'ZA','50'=>'Z',
		'51'=>'CE','52'=>'ML',
	);
	return (string) ( $map[ substr( $zip, 0, 2 ) ] ?? '' );
}

function mdo_catalog_postcode_pattern_match_20260820( string $postcode, string $pattern ): bool {
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
		return 1 === preg_match( '/^' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i', $postcode );
	}
	return $postcode === $pattern;
}

function mdo_catalog_public_zone_matches_20260820( int $zone_id, string $country, string $postcode = '' ): bool {
	$country = strtoupper( trim( $country ) );
	$postcode = trim( $postcode );
	$zone = mdo_catalog_public_zone_object_20260820( $zone_id );
	if ( ! $zone || ! is_callable( array( $zone, 'get_zone_locations' ) ) ) {
		return false;
	}

	$locations = (array) $zone->get_zone_locations();
	if ( 0 === $zone_id && empty( $locations ) ) {
		/* Do not expose an unrestricted "rest of world" destination as every country. */
		return false;
	}

	$country_match = false;
	$state_rules = array();
	$postcode_rules = array();
	foreach ( $locations as $location ) {
		$type = strtolower( trim( mdo_catalog_public_location_value_20260820( $location, 'type' ) ) );
		$code = strtoupper( trim( mdo_catalog_public_location_value_20260820( $location, 'code' ) ) );
		if ( 'country' === $type && $code === $country ) {
			$country_match = true;
		} elseif ( 'state' === $type && 0 === strpos( $code, $country . ':' ) ) {
			$country_match = true;
			$state_rules[] = substr( $code, 3 );
		} elseif ( 'continent' === $type && in_array( $country, mdo_catalog_public_continent_countries_20260820( $code ), true ) ) {
			$country_match = true;
		} elseif ( 'postcode' === $type ) {
			$postcode_rules[] = $code;
		}
	}
	if ( ! $country_match ) {
		return false;
	}
	if ( '' === $postcode ) {
		return true;
	}
	if ( $postcode_rules ) {
		foreach ( $postcode_rules as $pattern ) {
			if ( mdo_catalog_postcode_pattern_match_20260820( $postcode, $pattern ) ) {
				return true;
			}
		}
		return false;
	}
	if ( 'ES' === $country && $state_rules ) {
		$state = mdo_catalog_spanish_postcode_state_20260820( $postcode );
		return '' !== $state && in_array( $state, $state_rules, true );
	}
	return true;
}

function mdo_catalog_public_vendor_country_codes_20260820( int $vendor_id ): array {
	$codes = array();
	foreach ( mdo_catalog_public_zone_ids_20260820( $vendor_id ) as $zone_id ) {
		$codes = array_merge( $codes, mdo_catalog_public_zone_country_codes_20260820( $zone_id ) );
	}
	$codes = array_values( array_unique( array_filter( $codes ) ) );
	sort( $codes );
	return $codes;
}

add_filter( 'mdo_shipping_vendor_country_codes', static function( $codes, $vendor_id, $type ) {
	if ( 'by_zone' !== (string) $type ) {
		return $codes;
	}
	$resolved = mdo_catalog_public_vendor_country_codes_20260820( absint( $vendor_id ) );
	return $resolved ?: (array) $codes;
}, 100, 3 );

add_filter( 'mdo_shipping_vendor_can_ship_to', static function( $available, $vendor_id, $destination, $type ) {
	if ( 'by_zone' !== (string) $type ) {
		return (bool) $available;
	}
	$country  = strtoupper( trim( (string) ( $destination['country'] ?? 'ES' ) ) );
	$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );
	if ( 'ES' === $country && '' === $postcode ) {
		return true;
	}
	foreach ( mdo_catalog_public_zone_ids_20260820( absint( $vendor_id ) ) as $zone_id ) {
		if ( mdo_catalog_public_zone_matches_20260820( $zone_id, $country, $postcode ) ) {
			return true;
		}
	}
	return false;
}, 100, 4 );

/* Remove the previously cached empty country list once after this resolver is installed. */
add_action( 'plugins_loaded', static function(): void {
	$version = '20260820-public-zone-v1';
	if ( get_option( 'mdo_catalog_public_zone_fix_version' ) !== $version ) {
		delete_transient( 'mdo_supported_shipping_countries_v1' );
		update_option( 'mdo_catalog_public_zone_fix_version', $version, false );
	}
}, 1 );
