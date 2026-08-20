<?php
/**
 * Plugin Name: MDO Catalog Admin Filter Parity
 * Description: Keeps frontend catalogue filters coherent for administrators while preserving public vendor visibility rules.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Administrators are allowed to audit products from WCFM stores that are not
 * public yet. Reuse the child-theme capability contract when it is available.
 */
function mdo_catalog_admin_filter_can_view_unpublished_vendors_20260820(): bool {
	if ( is_admin() || ! is_user_logged_in() ) {
		return false;
	}

	if ( function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) ) {
		return (bool) elmercado_wcfm_disabled_visibility_can_view_010210();
	}

	return current_user_can( 'manage_options' );
}

/**
 * Product queries that belong to the current global catalogue surface. Besides
 * the main loop this includes the exact-ID query used by continuous loading.
 */
function mdo_catalog_admin_filter_targets_catalog_query_20260820( WP_Query $query ): bool {
	if ( ! mdo_catalog_admin_filter_can_view_unpublished_vendors_20260820() ) {
		return false;
	}

	$is_catalog = ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
	if ( ! $is_catalog ) {
		return false;
	}

	if ( $query->is_main_query() ) {
		return true;
	}

	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
}

/** @return int[] */
function mdo_catalog_admin_filter_disabled_vendor_ids_20260820(): array {
	if ( ! function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'absint', (array) elmercado_wcfm_disabled_vendor_ids_010210() )
			)
		)
	);
}

/** @return int[] */
function mdo_catalog_admin_filter_shipping_excluded_vendor_ids_20260820(): array {
	if ( ! class_exists( 'MDO_Catalog_Destination_Frontend' ) || ! is_callable( array( 'MDO_Catalog_Destination_Frontend', 'excluded_vendor_ids' ) ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'absint', (array) MDO_Catalog_Destination_Frontend::excluded_vendor_ids() )
			)
		)
	);
}

/**
 * Disabled vendors that the administrator may restore without bypassing the
 * separate destination/shipping truth of the catalogue.
 *
 * @return int[]
 */
function mdo_catalog_admin_filter_bypass_vendor_ids_20260820(): array {
	$disabled = mdo_catalog_admin_filter_disabled_vendor_ids_20260820();
	if ( ! $disabled ) {
		return array();
	}

	$shipping_excluded = mdo_catalog_admin_filter_shipping_excluded_vendor_ids_20260820();
	return array_values( array_diff( $disabled, $shipping_excluded ) );
}

/**
 * Published products belonging to admin-visible disabled vendors. These IDs are
 * eligibility additions only: category, attributes, price, stock and shipping
 * restrictions are still applied later by the normal catalogue query.
 *
 * @return int[]
 */
function mdo_catalog_admin_filter_bypass_product_ids_20260820(): array {
	static $cache = null;
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$vendor_ids = mdo_catalog_admin_filter_bypass_vendor_ids_20260820();
	if ( ! $vendor_ids ) {
		$cache = array();
		return $cache;
	}

	global $wpdb;
	$cache = array_values(
		array_unique(
			array_filter(
				array_map(
					'absint',
					(array) $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' AND post_author IN (" . implode( ',', array_map( 'absint', $vendor_ids ) ) . ') ORDER BY ID DESC'
					)
				)
			)
		)
	);

	return $cache;
}

/**
 * Restore the admin-visible seller set after marketplace/public filters have
 * had a chance to alter the query. All category, attribute, price and stock
 * restrictions remain untouched.
 */
function mdo_catalog_admin_filter_restore_query_20260820( WP_Query $query ): void {
	if ( ! mdo_catalog_admin_filter_targets_catalog_query_20260820( $query ) ) {
		return;
	}

	$bypass = mdo_catalog_admin_filter_bypass_vendor_ids_20260820();
	if ( ! $bypass ) {
		return;
	}

	$current_excluded = array_values( array_filter( array_map( 'absint', (array) $query->get( 'author__not_in' ) ) ) );
	if ( $current_excluded ) {
		$query->set( 'author__not_in', array_values( array_diff( $current_excluded, $bypass ) ) );
	}

	/*
	 * The public recommended-order cache intentionally contains only products
	 * eligible for the public catalogue. When that cache becomes post__in, an
	 * admin-only vendor/category is otherwise intersected with an unrelated list
	 * and the SQL returns zero rows. Add the admin-visible products back to the
	 * eligibility list; the existing query keeps deciding which ones actually
	 * match the selected category/vendor/attributes/price/stock.
	 */
	$admin_product_ids = mdo_catalog_admin_filter_bypass_product_ids_20260820();
	$post_in           = array_values( array_unique( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) ) );
	$raw_post_in       = array_values( (array) $query->get( 'post__in' ) );

	if ( 1 === count( $raw_post_in ) && 0 === absint( $raw_post_in[0] ) ) {
		$query->set( 'post__in', $admin_product_ids ?: array( 0 ) );
	} elseif ( $post_in && $admin_product_ids ) {
		$query->set( 'post__in', array_values( array_unique( array_merge( $post_in, $admin_product_ids ) ) ) );
	}

	$requested_vendor = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $requested_vendor <= 0 || ! in_array( $requested_vendor, $bypass, true ) ) {
		return;
	}

	/* A public guard may have neutralised the seller before this late pass. */
	$query->set( 'author', $requested_vendor );
	$query->set( 'author__in', array( $requested_vendor ) );
}

/**
 * If a marketplace layer wrote an author NOT IN directly into SQL, remove only
 * the admin-bypassable disabled IDs and retain every other exclusion (including
 * shipping destination exclusions).
 *
 * @param array<string,string> $clauses SQL clauses generated by WP_Query.
 * @return array<string,string>
 */
function mdo_catalog_admin_filter_restore_sql_20260820( array $clauses, WP_Query $query ): array {
	if ( ! mdo_catalog_admin_filter_targets_catalog_query_20260820( $query ) ) {
		return $clauses;
	}

	$bypass = mdo_catalog_admin_filter_bypass_vendor_ids_20260820();
	if ( ! $bypass || empty( $clauses['where'] ) ) {
		return $clauses;
	}

	global $wpdb;
	$table   = preg_quote( $wpdb->posts, '~' );
	$pattern = '~\s+AND\s+' . $table . '\.post_author\s+NOT\s+IN\s*\(([^)]*)\)~i';

	$clauses['where'] = (string) preg_replace_callback(
		$pattern,
		static function ( array $matches ) use ( $bypass, $wpdb ): string {
			$raw_ids   = isset( $matches[1] ) ? preg_split( '/\s*,\s*/', trim( (string) $matches[1] ) ) : array();
			$ids       = array_values( array_unique( array_filter( array_map( 'absint', (array) $raw_ids ) ) ) );
			$remaining = array_values( array_diff( $ids, $bypass ) );

			if ( ! $remaining ) {
				return '';
			}

			return ' AND ' . $wpdb->posts . '.post_author NOT IN (' . implode( ',', $remaining ) . ')';
		},
		(string) $clauses['where']
	);

	return $clauses;
}

/*
 * Register after theme/plugins have declared their catalogue hooks so this is
 * the final admin-only reconciliation pass.
 */
add_action(
	'wp_loaded',
	static function (): void {
		add_action( 'pre_get_posts', 'mdo_catalog_admin_filter_restore_query_20260820', PHP_INT_MAX );
		add_filter( 'posts_clauses', 'mdo_catalog_admin_filter_restore_sql_20260820', PHP_INT_MAX, 2 );
	},
	PHP_INT_MAX
);
