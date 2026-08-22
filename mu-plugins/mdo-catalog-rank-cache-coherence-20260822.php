<?php
/**
 * Plugin Name: MDO Catalog Rank Cache Coherence
 * Description: Invalidates an incomplete/stale daily catalogue rank before the public catalogue query consumes it and clears stale catalogue page caches at the same time.
 * Version: 1.1.0
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
			self::purge_page_caches();
		}
	}

	/**
	 * A stale rank can already have been rendered into page-cache entries for
	 * /tienda/page/N/ and /en/shop/page/N/. Clearing the transient alone repairs
	 * the next uncached query but does not remove those old HTML responses.
	 *
	 * This runs only when an incoherent rank has actually been detected.
	 */
	private static function purge_page_caches(): void {
		wp_cache_flush();

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache( true );
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		if ( function_exists( 'breeze_clear_all_cache' ) ) {
			breeze_clear_all_cache();
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
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
