<?php
/**
 * Plugin Name: MDO Catalog Query Coherence
 * Description: Keeps catalogue membership, result counts and ranking coherent. Default Spain without postcode never filters vendors by shipping.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Catalog_Query_Coherence_20260822 {
	private const RANK_QUERY_VAR = '_mdo_catalog_rank_order_20260822';

	/** @var array<int,array<string,mixed>> */
	private static array $snapshots = array();

	public static function init(): void {
		/* Capture the eligible catalogue universe immediately before the destination/ranking hook (1600). */
		add_action( 'pre_get_posts', array( __CLASS__, 'snapshot_catalog_query' ), 1500 );

		/* Repair membership after destination ranking (1600) and explicit Recommended ordering (1700). */
		add_action( 'pre_get_posts', array( __CLASS__, 'restore_catalog_membership' ), 1800 );

		/* Apply the saved rank as ORDER BY only; it must never become a post__in whitelist. */
		add_filter( 'posts_orderby', array( __CLASS__, 'apply_rank_as_order_only' ), PHP_INT_MAX, 2 );
	}

	public static function snapshot_catalog_query( WP_Query $query ): void {
		if ( ! self::is_catalog_query( $query ) ) {
			return;
		}

		self::$snapshots[ spl_object_id( $query ) ] = array(
			'post__in'       => $query->get( 'post__in' ),
			'author__not_in' => $query->get( 'author__not_in' ),
			'orderby'        => $query->get( 'orderby' ),
			'order'          => $query->get( 'order' ),
		);
	}

	public static function restore_catalog_membership( WP_Query $query ): void {
		if ( ! self::is_catalog_query( $query ) ) {
			return;
		}

		$key = spl_object_id( $query );
		if ( ! isset( self::$snapshots[ $key ] ) ) {
			return;
		}
		$snapshot = self::$snapshots[ $key ];

		/*
		 * Spain without a postcode is the neutral/default catalogue. Shipping
		 * destination logic is not allowed to add vendor exclusions in this state.
		 * Keep all exclusions that already existed before destination handling
		 * (e.g. publication/vendor visibility rules), but discard shipping additions.
		 */
		if ( self::is_default_spain_without_postcode() ) {
			$query->set( 'author__not_in', $snapshot['author__not_in'] );
		}

		/*
		 * MDO_Catalog_Destination_Frontend currently expresses ranking by replacing
		 * post__in with the ranked IDs and setting orderby=post__in. That makes the
		 * rank a whitelist and can reduce a 107-product catalogue to a few cards.
		 * Save those IDs strictly as an ordering preference, then restore the exact
		 * eligible product universe captured before ranking ran.
		 */
		if ( 'post__in' !== (string) $query->get( 'orderby' ) ) {
			return;
		}

		$ranked = self::normalise_ids( $query->get( 'post__in' ) );

		$query->set( self::RANK_QUERY_VAR, $ranked );
		$query->set( 'post__in', $snapshot['post__in'] );
		$query->set( 'orderby', $snapshot['orderby'] );
		$query->set( 'order', $snapshot['order'] );
	}

	public static function apply_rank_as_order_only( string $orderby, WP_Query $query ): string {
		if ( ! self::is_catalog_query( $query ) ) {
			return $orderby;
		}

		$ranked = self::normalise_ids( $query->get( self::RANK_QUERY_VAR ) );
		if ( ! $ranked ) {
			return $orderby;
		}

		global $wpdb;
		$list = implode( ',', $ranked );
		$rank_sql = sprintf(
			'CASE WHEN %1$s.ID IN (%2$s) THEN 0 ELSE 1 END ASC, FIELD(%1$s.ID,%2$s) ASC',
			$wpdb->posts,
			$list
		);

		return '' !== trim( $orderby ) ? $rank_sql . ', ' . $orderby : $rank_sql;
	}

	private static function is_default_spain_without_postcode(): bool {
		if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
			$destination = MDO_Catalog_Destination_Frontend::current_destination();
			$country  = strtoupper( trim( (string) ( $destination['country'] ?? 'ES' ) ) );
			$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );
			return 'ES' === $country && '' === $postcode;
		}

		$country = isset( $_COOKIE['mdo_shipping_country'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_country'] ) ) )
			: 'ES';
		$postcode = isset( $_COOKIE['mdo_shipping_postcode'] )
			? trim( sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_postcode'] ) ) )
			: '';

		return 'ES' === $country && '' === $postcode;
	}

	private static function is_catalog_query( WP_Query $query ): bool {
		if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
			return false;
		}

		if ( function_exists( 'elmercado_catalog_is_main_query_010224' ) ) {
			return (bool) elmercado_catalog_is_main_query_010224( $query );
		}

		if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
			return true;
		}
		if ( $query->is_tax() && 0 === strpos( (string) $query->get( 'taxonomy' ), 'pa_' ) ) {
			return true;
		}

		$post_type = $query->get( 'post_type' );
		return 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
	}

	/** @return int[] */
	private static function normalise_ids( $ids ): array {
		$normalised = array_map( 'absint', (array) $ids );
		return array_values( array_unique( array_filter( $normalised, static function ( int $id ): bool {
			return $id > 0;
		} ) ) );
	}
}

MDO_Catalog_Query_Coherence_20260822::init();
