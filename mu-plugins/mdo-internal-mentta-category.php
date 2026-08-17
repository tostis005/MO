<?php
/**
 * Plugin Name: MDO - Internal MENTTA category
 * Description: Keeps the MENTTA WooCommerce category available to integrations/admins but hidden from the public storefront.
 * Version: 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MDO_MENTTA_CATEGORY_SLUG' ) ) {
	define( 'MDO_MENTTA_CATEGORY_SLUG', 'mentta' );
}

/**
 * Resolve the marker term ID without using get_terms(), because this function is
 * itself called from term-query filters on public requests.
 */
function mdo_mentta_marker_term_id() {
	static $term_id = null;

	if ( null !== $term_id ) {
		return $term_id;
	}

	global $wpdb;
	$term_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT t.term_id
			 FROM {$wpdb->terms} t
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			 WHERE t.slug = %s AND tt.taxonomy = %s
			 LIMIT 1",
			MDO_MENTTA_CATEGORY_SLUG,
			'product_cat'
		)
	);

	return $term_id;
}

/** Resolve the marker term_taxonomy_id for queries that request tt_ids. */
function mdo_mentta_marker_term_taxonomy_id() {
	static $tt_id = null;

	if ( null !== $tt_id ) {
		return $tt_id;
	}

	$term_id = mdo_mentta_marker_term_id();
	if ( ! $term_id ) {
		$tt_id = 0;
		return $tt_id;
	}

	global $wpdb;
	$tt_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = %s LIMIT 1",
			$term_id,
			'product_cat'
		)
	);

	return $tt_id;
}

/**
 * Only hide the category on normal public storefront requests.
 * Admin, WP-CLI and REST requests remain untouched so MENTTA can read it.
 */
function mdo_mentta_should_hide_publicly() {
	if ( is_admin() ) {
		return false;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	return true;
}

/** Hide MENTTA from public category lists, grids, widgets and filters. */
function mdo_mentta_hide_from_public_term_queries( $args, $taxonomies ) {
	if ( ! mdo_mentta_should_hide_publicly() || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $args;
	}

	$term_id = mdo_mentta_marker_term_id();
	if ( ! $term_id ) {
		return $args;
	}

	$exclude   = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : array();
	$exclude[] = $term_id;
	$args['exclude'] = array_values( array_unique( $exclude ) );

	return $args;
}
add_filter( 'get_terms_args', 'mdo_mentta_hide_from_public_term_queries', 20, 2 );

/**
 * Final safety net for term-query results. Handles WP_Term objects as well as
 * builders that request ids, tt_ids, slugs or names from get_terms().
 */
function mdo_mentta_hide_from_public_term_results( $terms, $taxonomies, $args, $term_query ) {
	if ( ! mdo_mentta_should_hide_publicly() || ! is_array( $terms ) ) {
		return $terms;
	}

	$requested_taxonomies = array_merge(
		(array) $taxonomies,
		isset( $args['taxonomy'] ) ? (array) $args['taxonomy'] : array()
	);

	if ( ! in_array( 'product_cat', $requested_taxonomies, true ) ) {
		return $terms;
	}

	$term_id = mdo_mentta_marker_term_id();
	if ( ! $term_id ) {
		return $terms;
	}

	$fields = isset( $args['fields'] ) ? (string) $args['fields'] : 'all';
	$tt_id  = mdo_mentta_marker_term_taxonomy_id();

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $term_id, $tt_id, $fields ) {
				if ( $term instanceof WP_Term ) {
					return (int) $term->term_id !== $term_id;
				}

				if ( 'ids' === $fields ) {
					return (int) $term !== $term_id;
				}

				if ( 'tt_ids' === $fields ) {
					return ! $tt_id || (int) $term !== $tt_id;
				}

				if ( 'slugs' === $fields ) {
					return MDO_MENTTA_CATEGORY_SLUG !== strtolower( (string) $term );
				}

				if ( 'names' === $fields ) {
					return 'mentta' !== strtolower( trim( (string) $term ) );
				}

				return true;
			}
		)
	);
}
add_filter( 'get_terms', 'mdo_mentta_hide_from_public_term_results', 20, 4 );

/** Hide MENTTA from category labels/links shown on product pages. */
function mdo_mentta_hide_from_public_product_terms( $terms, $post_id, $taxonomy ) {
	if ( 'product_cat' !== $taxonomy || ! mdo_mentta_should_hide_publicly() || ! is_array( $terms ) ) {
		return $terms;
	}

	$term_id = mdo_mentta_marker_term_id();
	if ( ! $term_id ) {
		return $terms;
	}

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $term_id ) {
				return ! ( $term instanceof WP_Term ) || (int) $term->term_id !== $term_id;
			}
		)
	);
}
add_filter( 'get_the_terms', 'mdo_mentta_hide_from_public_product_terms', 20, 3 );

/** Hide any menu item that points to the internal category. */
function mdo_mentta_hide_public_menu_items( $items ) {
	if ( ! mdo_mentta_should_hide_publicly() || ! is_array( $items ) ) {
		return $items;
	}

	$term_id = mdo_mentta_marker_term_id();

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $term_id ) {
				if ( $term_id && isset( $item->object, $item->object_id ) && 'product_cat' === $item->object && (int) $item->object_id === $term_id ) {
					return false;
				}

				if ( isset( $item->url ) ) {
					$path = (string) wp_parse_url( (string) $item->url, PHP_URL_PATH );
					if ( preg_match( '#/mentta/?$#i', untrailingslashit( $path ) . '/' ) ) {
						return false;
					}
				}

				return true;
			}
		)
	);
}
add_filter( 'wp_get_nav_menu_items', 'mdo_mentta_hide_public_menu_items', 20 );

/** Do not expose a browsable public archive for the internal category. */
function mdo_mentta_block_public_archive() {
	if ( ! mdo_mentta_should_hide_publicly() || ! function_exists( 'is_product_category' ) || ! is_product_category( MDO_MENTTA_CATEGORY_SLUG ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'mdo_mentta_block_public_archive', 1 );
