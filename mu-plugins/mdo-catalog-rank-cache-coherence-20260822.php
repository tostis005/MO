<?php
/**
 * Plugin Name: MDO Catalog Rank Cache Coherence
 * Description: Invalidates an incomplete/stale daily catalogue rank before the public catalogue query consumes it.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Catalog_Rank_Cache_Coherence_20260822 {
	private const RANK_CACHE_PREFIX = 'mdo_catalog_rank_v3_';

	public static function init(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'ensure_complete_rank_cache' ), 1500 );
	}

	public static function ensure_complete_rank_cache( WP_Query $query ): void {
		if ( is_admin() || ! self::is_catalog_query( $query ) ) {
			return;
		}

		$key    = self::RANK_CACHE_PREFIX . gmdate( 'Ymd' );
		$cached = get_transient( $key );
		if ( ! is_array( $cached ) || ! $cached ) {
			return;
		}

		$cached_ids    = self::normalize_ids( $cached );
		$published_ids = self::published_product_ids();
		$cached_set    = $cached_ids;
		$published_set = $published_ids;
		sort( $cached_set, SORT_NUMERIC );
		sort( $published_set, SORT_NUMERIC );

		if ( $cached_set !== $published_set ) {
			delete_transient( $key );
		}
	}

	private static function published_product_ids(): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY ID ASC",
				'product',
				'publish'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return self::normalize_ids( (array) $ids );
	}

	private static function normalize_ids( array $ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	private static function is_catalog_query( WP_Query $query ): bool {
		if ( ! $query->is_main_query() || $query->is_singular() ) {
			return false;
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
}

MDO_Catalog_Rank_Cache_Coherence_20260822::init();
