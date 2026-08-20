<?php
/**
 * Plugin Name: MDO English Category Query Normalizer
 * Description: Repairs English WooCommerce category requests and the erroneous global post__in list, while preserving EMDO visibility/filter rules. Disabled by default and testable per request.
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_EN_CATEGORY_NORMALIZER_OPTION_20260821 = 'mdo_en_category_query_normalizer_enabled_20260821';
$GLOBALS['mdo_en_category_diag_20260821'] = array();

function mdo_en_category_normalizer_is_english_20260821(): bool {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

function mdo_en_category_normalizer_test_request_20260821(): bool {
	return isset( $_GET['mdo_cat_fix_test'] ) && '1' === (string) wp_unslash( $_GET['mdo_cat_fix_test'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function mdo_en_category_normalizer_enabled_20260821(): bool {
	return '1' === (string) get_option( MDO_EN_CATEGORY_NORMALIZER_OPTION_20260821, '0' );
}

function mdo_en_category_normalizer_active_20260821(): bool {
	return mdo_en_category_normalizer_enabled_20260821() || mdo_en_category_normalizer_test_request_20260821();
}

function mdo_en_category_normalizer_find_term_20260821( string $requested_slug ): ?WP_Term {
	$requested_slug = sanitize_title( $requested_slug );
	if ( '' === $requested_slug || ! taxonomy_exists( 'product_cat' ) ) {
		return null;
	}
	$canonical = get_term_by( 'slug', $requested_slug, 'product_cat' );
	if ( $canonical instanceof WP_Term ) {
		return $canonical;
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$english_slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
		$english_name = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_name', true ) );
		if ( ( '' !== $english_slug && $requested_slug === $english_slug ) || ( '' !== $english_name && $requested_slug === $english_name ) ) {
			return $term;
		}
	}
	return null;
}

/** @return int[] */
function mdo_en_category_visible_ids_20260821( int $term_id ): array {
	static $cache = array();
	$term_id = absint( $term_id );
	if ( $term_id <= 0 ) {
		return array();
	}
	$scope = function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' ) && elmercado_catalog_counts_can_view_disabled_010217() ? 'admin' : 'public';
	$key = $scope . ':' . $term_id;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$term_ids = array( $term_id );
	$children = get_term_children( $term_id, 'product_cat' );
	if ( ! is_wp_error( $children ) ) {
		$term_ids = array_values( array_unique( array_merge( $term_ids, array_filter( array_map( 'absint', (array) $children ) ) ) ) );
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
		$excluded = array_values( array_filter( array_map( 'absint', (array) elmercado_catalog_counts_excluded_authors_010217() ) ) );
		if ( $excluded ) {
			$sql .= ' AND p.post_author NOT IN (' . implode( ',', $excluded ) . ')';
		}
	}
	$sql .= ' ORDER BY p.ID DESC';
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $wpdb->get_col( $sql ) ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( class_exists( 'MDO_Catalog_Ranking' ) && is_callable( array( 'MDO_Catalog_Ranking', 'rank_products' ) ) && $ids ) {
		$ranked = MDO_Catalog_Ranking::rank_products( $ids );
		if ( $ranked ) {
			$ids = $ranked;
		}
	}
	$cache[ $key ] = $ids;
	return $ids;
}

function mdo_en_category_has_narrowing_filters_20260821(): bool {
	foreach ( array_keys( $_GET ) as $raw_key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = sanitize_key( (string) $raw_key );
		if ( 'mdo_cat_fix_test' === $key ) {
			continue;
		}
		if ( in_array( $key, array( 'min_price', 'max_price', 'vendor_id', 's', 'product_tag' ), true ) || 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) ) {
			return true;
		}
	}
	return false;
}

add_filter(
	'request',
	static function ( array $query_vars ): array {
		if ( is_admin() || ! mdo_en_category_normalizer_is_english_20260821() ) {
			return $query_vars;
		}
		$test = mdo_en_category_normalizer_test_request_20260821();
		if ( $test ) {
			$GLOBALS['mdo_en_category_diag_20260821']['request_in'] = $query_vars;
		}
		if ( ! mdo_en_category_normalizer_active_20260821() ) {
			return $query_vars;
		}

		$requested = '';
		if ( isset( $query_vars['product_cat'] ) ) {
			$requested = (string) $query_vars['product_cat'];
		} elseif ( isset( $query_vars['taxonomy'], $query_vars['term'] ) && 'product_cat' === (string) $query_vars['taxonomy'] ) {
			$requested = (string) $query_vars['term'];
		}
		$GLOBALS['mdo_en_category_diag_20260821']['requested'] = $requested;
		if ( '' === trim( $requested ) ) {
			return $query_vars;
		}

		$term = mdo_en_category_normalizer_find_term_20260821( $requested );
		if ( $term instanceof WP_Term ) {
			$GLOBALS['mdo_en_category_diag_20260821']['resolved_term'] = array( 'id' => (int) $term->term_id, 'slug' => (string) $term->slug, 'name' => (string) $term->name );
		}
		if ( ! $term instanceof WP_Term || sanitize_title( $requested ) === (string) $term->slug ) {
			return $query_vars;
		}
		if ( array_key_exists( 'product_cat', $query_vars ) ) {
			$query_vars['product_cat'] = (string) $term->slug;
		}
		if ( isset( $query_vars['taxonomy'] ) && 'product_cat' === (string) $query_vars['taxonomy'] ) {
			$query_vars['term'] = (string) $term->slug;
		}
		$query_vars['mdo_en_category_normalized_20260821'] = 1;
		if ( $test ) {
			$GLOBALS['mdo_en_category_diag_20260821']['request_out'] = $query_vars;
		}
		return $query_vars;
	},
	PHP_INT_MAX
);

/* Register the repair after theme/plugin pre_get_posts callbacks have already
 * been attached, so a later global ranking list cannot overwrite it. */
add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'pre_get_posts',
			static function ( WP_Query $query ): void {
				if ( is_admin() || ! $query->is_main_query() || ! mdo_en_category_normalizer_is_english_20260821() || ! mdo_en_category_normalizer_active_20260821() || ! $query->is_tax( 'product_cat' ) ) {
					return;
				}
				$slug = sanitize_title( (string) $query->get( 'product_cat' ) );
				$term = $slug ? get_term_by( 'slug', $slug, 'product_cat' ) : false;
				if ( ! $term instanceof WP_Term ) {
					return;
				}

				$valid = mdo_en_category_visible_ids_20260821( (int) $term->term_id );
				$current_raw = array_map( 'absint', (array) $query->get( 'post__in' ) );
				$current = array_values( array_filter( $current_raw ) );
				$lookup = array_fill_keys( $valid, true );
				$intersection = $current ? array_values( array_filter( $current, static fn( int $id ): bool => isset( $lookup[ $id ] ) ) ) : array();

				if ( $intersection ) {
					$replacement = $intersection;
				} elseif ( mdo_en_category_has_narrowing_filters_20260821() && $current_raw ) {
					/* An explicit active filter may legitimately produce no matches. */
					$replacement = array( 0 );
				} else {
					$replacement = $valid ?: array( 0 );
				}
				$query->set( 'post__in', $replacement );

				if ( mdo_en_category_normalizer_test_request_20260821() ) {
					$GLOBALS['mdo_en_category_diag_20260821']['post_in_repair'] = array(
						'term_id'        => (int) $term->term_id,
						'canonical_slug' => (string) $term->slug,
						'before_count'   => count( $current ),
						'before_sample'  => array_slice( $current, 0, 12 ),
						'valid_count'    => count( $valid ),
						'valid_sample'   => array_slice( $valid, 0, 12 ),
						'after_count'    => count( array_filter( $replacement ) ),
						'after_sample'   => array_slice( array_values( array_filter( $replacement ) ), 0, 12 ),
					);
				}
			},
			PHP_INT_MAX
		);
	},
	PHP_INT_MAX
);

add_filter(
	'posts_request',
	static function ( string $sql, WP_Query $query ): string {
		if ( mdo_en_category_normalizer_test_request_20260821() && $query->is_main_query() ) {
			$GLOBALS['mdo_en_category_diag_20260821']['sql'] = substr( $sql, 0, 8000 );
		}
		return $sql;
	},
	PHP_INT_MAX,
	2
);

add_action(
	'shutdown',
	static function (): void {
		if ( ! mdo_en_category_normalizer_test_request_20260821() ) {
			return;
		}
		$json = wp_json_encode( $GLOBALS['mdo_en_category_diag_20260821'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( is_string( $json ) ) {
			echo "\n<!--MDO_CAT_DIAG:" . base64_encode( $json ) . "-->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
);
