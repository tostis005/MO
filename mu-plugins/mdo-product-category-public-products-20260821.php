<?php
/**
 * Plugin Name: MDO Product Category Public Products
 * Description: Prevents false-empty WooCommerce product-category archives while preserving EMDO catalogue visibility rules.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return int[] */
function mdo_category_public_valid_ids_20260821( int $term_id ): array {
	static $cache = array();
	$term_id = absint( $term_id );
	if ( $term_id <= 0 ) {
		return array();
	}

	$scope = function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' ) && elmercado_catalog_counts_can_view_disabled_010217() ? 'admin' : 'public';
	$key   = $scope . ':' . $term_id;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$term_ids = array( $term_id );
	$children = get_term_children( $term_id, 'product_cat' );
	if ( ! is_wp_error( $children ) ) {
		$term_ids = array_values( array_unique( array_merge( $term_ids, array_filter( array_map( 'absint', (array) $children ) ) ) ) );
	}
	if ( ! $term_ids ) {
		$cache[ $key ] = array();
		return array();
	}

	global $wpdb;
	$sql = "SELECT DISTINCT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND tt.taxonomy = 'product_cat'
		AND tt.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . ')';

	if ( function_exists( 'elmercado_catalog_visibility_sql_clause_010218' ) ) {
		$sql .= elmercado_catalog_visibility_sql_clause_010218( 'p' );
	}

	if ( function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
		$excluded = array_values( array_filter( array_map( 'absint', elmercado_catalog_counts_excluded_authors_010217() ) ) );
		if ( $excluded ) {
			$sql .= ' AND p.post_author NOT IN (' . implode( ',', $excluded ) . ')';
		}
	}

	$sql .= ' ORDER BY p.ID DESC';
	$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$cache[ $key ] = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	return $cache[ $key ];
}

function mdo_category_public_has_narrowing_filters_20260821(): bool {
	foreach ( array_keys( $_GET ) as $raw_key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = sanitize_key( (string) $raw_key );
		if ( in_array( $key, array( 'min_price', 'max_price', 'vendor_id', 's', 'product_tag' ), true )
			|| 0 === strpos( $key, 'filter_' )
			|| 0 === strpos( $key, 'query_type_' ) ) {
			return true;
		}
	}
	return false;
}

function mdo_category_public_context_20260821( WP_Query $query ): array {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'product_cat' ) ) {
		return array();
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return array();
	}
	$ids = mdo_category_public_valid_ids_20260821( (int) $term->term_id );
	return array( 'term_id' => (int) $term->term_id, 'ids' => $ids );
}

add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'pre_get_posts',
			static function ( WP_Query $query ): void {
				$context = mdo_category_public_context_20260821( $query );
				if ( ! $context ) {
					return;
				}

				$valid_ids = (array) $context['ids'];
				$current   = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) );
				$current   = array_values( array_diff( $current, array( 0 ) ) );

				if ( $current ) {
					$valid_lookup = array_fill_keys( $valid_ids, true );
					$intersection = array_values( array_filter( $current, static fn( int $id ): bool => isset( $valid_lookup[ $id ] ) ) );
					/* Ranking is ordering, not a second visibility universe. If it accidentally
					 * excludes the whole category, restore the category truth set. */
					$query->set( 'post__in', $intersection ?: ( $valid_ids ?: array( 0 ) ) );
				} else {
					$query->set( 'post__in', $valid_ids ?: array( 0 ) );
				}

				if ( 'post__in' !== (string) $query->get( 'orderby' ) && $valid_ids ) {
					/* Leave WooCommerce's requested/default ordering untouched. */
				}
				$query->set( 'mdo_category_public_loop_20260821', 1 );
				$query->set( 'mdo_category_public_recovery_20260821', mdo_category_public_has_narrowing_filters_20260821() ? 0 : 1 );
			},
			PHP_INT_MAX
		);

		/* A final consistency guard protects the initial category click from a
		 * later plugin rebuilding the query into an empty result. It is deliberately
		 * disabled when the visitor applies a genuine narrowing filter. */
		add_filter(
			'the_posts',
			static function ( array $posts, WP_Query $query ): array {
				if ( is_admin() || $posts || ! $query->get( 'mdo_category_public_loop_20260821' ) || ! $query->get( 'mdo_category_public_recovery_20260821' ) ) {
					return $posts;
				}
				$context = mdo_category_public_context_20260821( $query );
				$ids     = array_values( array_filter( array_map( 'absint', (array) ( $context['ids'] ?? array() ) ) ) );
				if ( ! $ids ) {
					return $posts;
				}

				$per_page = (int) $query->get( 'posts_per_page' );
				if ( $per_page <= 0 ) {
					$per_page = max( 1, (int) get_option( 'posts_per_page', 12 ) );
				}
				$paged    = max( 1, (int) $query->get( 'paged' ), (int) get_query_var( 'paged' ) );
				$page_ids = array_slice( $ids, ( $paged - 1 ) * $per_page, $per_page );
				$recovered = array();
				foreach ( $page_ids as $product_id ) {
					$post = get_post( $product_id );
					if ( $post instanceof WP_Post && 'product' === $post->post_type && 'publish' === $post->post_status ) {
						$recovered[] = $post;
					}
				}
				if ( $recovered ) {
					$query->found_posts   = count( $ids );
					$query->max_num_pages = (int) ceil( count( $ids ) / $per_page );
					return $recovered;
				}
				return $posts;
			},
			PHP_INT_MAX,
			2
		);
	},
	PHP_INT_MAX
);
