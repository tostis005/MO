<?php
/**
 * Plugin Name: MDO Catalog Default Spain Safety
 * Description: Keeps the default Spain catalogue neutral: no destination filtering until the customer chooses a postcode or another country.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "España" with no postcode is only the visual default. It must never exclude vendors.
 */
add_filter(
	'mdo_shipping_vendor_can_ship_to',
	static function ( $available, $vendor_id, $destination, $type ) {
		unset( $vendor_id, $type );
		$country  = strtoupper( trim( (string) ( $destination['country'] ?? '' ) ) );
		$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );

		if ( 'ES' === $country && '' === $postcode ) {
			return true;
		}

		return (bool) $available;
	},
	PHP_INT_MAX,
	4
);

/**
 * Extra guard for the default unfiltered shop. The destination frontend uses [0]
 * only as an internal empty-ranking sentinel; never let that blank the initial shop.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$is_catalog = function_exists( 'elmercado_catalog_is_main_query_010224' )
			? elmercado_catalog_is_main_query_010224( $query )
			: ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || 'product' === $query->get( 'post_type' ) );
		if ( ! $is_catalog ) {
			return;
		}

		$country  = isset( $_COOKIE['mdo_shipping_country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_country'] ) ) ) : 'ES';
		$postcode = isset( $_COOKIE['mdo_shipping_postcode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_postcode'] ) ) : '';
		if ( 'ES' !== $country || '' !== trim( $postcode ) ) {
			return;
		}

		$post_in = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) );
		if ( array( 0 ) === $post_in || ( 1 === count( (array) $query->get( 'post__in' ) ) && 0 === absint( reset( $query->query_vars['post__in'] ) ) ) ) {
			$query->set( 'post__in', array() );
			if ( 'post__in' === $query->get( 'orderby' ) ) {
				$query->set( 'orderby', 'menu_order title' );
				$query->set( 'order', 'ASC' );
			}
		}
	},
	PHP_INT_MAX
);
