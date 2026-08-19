<?php
/**
 * Plugin Name: MDO - Internal MENTTA category
 * Description: Keeps the MENTTA WooCommerce category tree available to integrations/admins but hidden from the public storefront.
 * Version: 1.2.0
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

/**
 * Resolve MENTTA plus every descendant directly from the taxonomy table.
 * This deliberately avoids get_terms()/get_term_children() to prevent
 * recursion while the public term-query filters below are executing.
 */
function mdo_mentta_internal_term_ids() {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}

	$root_id = mdo_mentta_marker_term_id();
	if ( ! $root_id ) {
		$ids = array();
		return $ids;
	}

	global $wpdb;
	$ids      = array( (int) $root_id );
	$frontier = array( (int) $root_id );
	$guard    = 0;

	while ( $frontier && $guard < 20 ) {
		$placeholders = implode( ',', array_fill( 0, count( $frontier ), '%d' ) );
		$sql = "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s AND parent IN ($placeholders)";
		$params = array_merge( array( 'product_cat' ), $frontier );
		$children = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
		$children = array_values( array_unique( array_map( 'intval', (array) $children ) ) );
		$children = array_values( array_diff( $children, $ids ) );

		if ( ! $children ) {
			break;
		}

		$ids      = array_merge( $ids, $children );
		$frontier = $children;
		++$guard;
	}

	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
	return $ids;
}

/** Resolve IDs/slugs/names/tt_ids for the whole internal tree without term APIs. */
function mdo_mentta_internal_term_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	$ids = mdo_mentta_internal_term_ids();
	$map = array(
		'ids'    => $ids,
		'tt_ids' => array(),
		'slugs'  => array(),
		'names'  => array(),
	);

	if ( ! $ids ) {
		return $map;
	}

	global $wpdb;
	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$sql = "SELECT t.term_id, t.slug, t.name, tt.term_taxonomy_id
		FROM {$wpdb->terms} t
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
		WHERE tt.taxonomy = %s AND t.term_id IN ($placeholders)";
	$params = array_merge( array( 'product_cat' ), $ids );
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

	foreach ( (array) $rows as $row ) {
		$map['tt_ids'][] = (int) $row->term_taxonomy_id;
		$map['slugs'][]  = strtolower( (string) $row->slug );
		$map['names'][]  = strtolower( trim( (string) $row->name ) );
	}

	$map['tt_ids'] = array_values( array_unique( $map['tt_ids'] ) );
	$map['slugs']  = array_values( array_unique( $map['slugs'] ) );
	$map['names']  = array_values( array_unique( $map['names'] ) );
	return $map;
}

/**
 * Hide the internal category on storefront requests, including frontend AJAX.
 * Keep wp-admin, WP-CLI and REST management requests untouched.
 */
function mdo_mentta_should_hide_publicly() {
	$doing_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

	if ( $doing_ajax ) {
		// admin-ajax.php reports is_admin()=true even when called by the public shop.
		// Only preserve MENTTA for AJAX calls that genuinely originate in wp-admin.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
		$path    = strtolower( (string) wp_parse_url( $referer, PHP_URL_PATH ) );
		if ( '' !== $path && false !== strpos( $path, '/wp-admin/' ) ) {
			return false;
		}
		return true;
	}

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

/** Hide MENTTA and all descendants from public category lists/grids/widgets. */
function mdo_mentta_hide_from_public_term_queries( $args, $taxonomies ) {
	if ( ! mdo_mentta_should_hide_publicly() || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $args;
	}

	$internal_ids = mdo_mentta_internal_term_ids();
	if ( ! $internal_ids ) {
		return $args;
	}

	$exclude = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : array();
	$args['exclude'] = array_values( array_unique( array_merge( $exclude, $internal_ids ) ) );

	return $args;
}
add_filter( 'get_terms_args', 'mdo_mentta_hide_from_public_term_queries', 20, 2 );

/** Final safety net for public term-query results. */
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

	$map = mdo_mentta_internal_term_map();
	if ( ! $map['ids'] ) {
		return $terms;
	}

	$fields = isset( $args['fields'] ) ? (string) $args['fields'] : 'all';

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $map, $fields ) {
				if ( $term instanceof WP_Term ) {
					return ! in_array( (int) $term->term_id, $map['ids'], true );
				}

				if ( 'ids' === $fields ) {
					return ! in_array( (int) $term, $map['ids'], true );
				}

				if ( 'tt_ids' === $fields ) {
					return ! in_array( (int) $term, $map['tt_ids'], true );
				}

				if ( 'slugs' === $fields ) {
					return ! in_array( strtolower( (string) $term ), $map['slugs'], true );
				}

				if ( 'names' === $fields ) {
					return ! in_array( strtolower( trim( (string) $term ) ), $map['names'], true );
				}

				return true;
			}
		)
	);
}
add_filter( 'get_terms', 'mdo_mentta_hide_from_public_term_results', 20, 4 );

/** Hide MENTTA and descendants from category labels/links on product pages. */
function mdo_mentta_hide_from_public_product_terms( $terms, $post_id, $taxonomy ) {
	if ( 'product_cat' !== $taxonomy || ! mdo_mentta_should_hide_publicly() || ! is_array( $terms ) ) {
		return $terms;
	}

	$internal_ids = mdo_mentta_internal_term_ids();
	if ( ! $internal_ids ) {
		return $terms;
	}

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $internal_ids ) {
				return ! ( $term instanceof WP_Term ) || ! in_array( (int) $term->term_id, $internal_ids, true );
			}
		)
	);
}
add_filter( 'get_the_terms', 'mdo_mentta_hide_from_public_product_terms', 20, 3 );

/** Hide any menu item pointing to the internal category tree. */
function mdo_mentta_hide_public_menu_items( $items ) {
	if ( ! mdo_mentta_should_hide_publicly() || ! is_array( $items ) ) {
		return $items;
	}

	$map = mdo_mentta_internal_term_map();

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $map ) {
				if ( isset( $item->object, $item->object_id ) && 'product_cat' === $item->object && in_array( (int) $item->object_id, $map['ids'], true ) ) {
					return false;
				}

				if ( isset( $item->url ) ) {
					$path = strtolower( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ) );
					foreach ( $map['slugs'] as $slug ) {
						if ( preg_match( '#/' . preg_quote( $slug, '#' ) . '/?$#i', untrailingslashit( $path ) . '/' ) ) {
							return false;
						}
					}

				return true;
			}
		)
	);
}
add_filter( 'wp_get_nav_menu_items', 'mdo_mentta_hide_public_menu_items', 20 );

/** Remove final custom Home category cards for MENTTA or its mirrored children. */
function mdo_mentta_remove_final_home_card( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'emo-category-card' ) || false === stripos( $html, 'mentta' ) ) {
		return $html;
	}

	$pattern = '~<a\\b[^>]*\\bclass=(?:"[^"]*\\bemo-category-card\\b[^"]*"|\\'[^\\']*\\bemo-category-card\\b[^\\']*\\')[^>]*\\bhref=(?:"[^"]*/mentta(?:-|/)[^\\"]*"|\\'[^\\']*/mentta(?:-|/)[^\\']*\\')[^>]*>.*?</a>~is';
	$filtered = preg_replace( $pattern, '', $html );

	return is_string( $filtered ) ? $filtered : $html;
}

function mdo_mentta_start_home_output_filter() {
	if ( ! mdo_mentta_should_hide_publicly() || ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
		return;
	}

	ob_start( 'mdo_mentta_remove_final_home_card' );
}
add_action( 'template_redirect', 'mdo_mentta_start_home_output_filter', -10000 );

/** Do not expose browsable public archives for MENTTA or any descendant. */
function mdo_mentta_block_public_archive() {
	if ( ! mdo_mentta_should_hide_publicly() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}

	$queried = get_queried_object();
	if ( ! ( $queried instanceof WP_Term ) || 'product_cat' !== $queried->taxonomy ) {
		return;
	}

	if ( ! in_array( (int) $queried->term_id, mdo_mentta_internal_term_ids(), true ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'mdo_mentta_block_public_archive', 1 );
