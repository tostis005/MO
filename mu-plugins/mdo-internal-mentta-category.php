<?php
/**
 * Plugin Name: MDO - Internal MENTTA category
 * Description: Keeps the MENTTA product category internal and limits the MENTTA marketplace feed to products assigned to it.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MDO_MENTTA_CATEGORY_SLUG' ) ) {
	define( 'MDO_MENTTA_CATEGORY_SLUG', 'mentta' );
}

/**
 * Return the internal MENTTA category term, when it exists.
 *
 * @return WP_Term|false
 */
function mdo_mentta_get_marker_term() {
	return get_term_by( 'slug', MDO_MENTTA_CATEGORY_SLUG, 'product_cat' );
}

/**
 * Return the marker term ID.
 *
 * @return int
 */
function mdo_mentta_get_marker_term_id() {
	$term = mdo_mentta_get_marker_term();
	return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

/**
 * Administrators/product managers must still be able to assign the marker.
 * WP-CLI also needs unfiltered access for maintenance and deployment checks.
 *
 * @return bool
 */
function mdo_mentta_is_management_context() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	if ( is_user_logged_in() && ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_products' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Remember which MENTTA REST endpoint is about to execute.
 * The product-query flag is one-shot so unrelated WC_Product_Query calls in the
 * same request cannot accidentally inherit the feed restriction.
 */
function mdo_mentta_capture_rest_route( $response, $handler, $request ) {
	if ( ! $request instanceof WP_REST_Request ) {
		return $response;
	}

	$route = (string) $request->get_route();
	if ( 0 === strpos( $route, '/mentta_marketplace/' ) ) {
		$GLOBALS['mdo_mentta_rest_route'] = $route;
		if ( '/mentta_marketplace/products' === rtrim( $route, '/' ) ) {
			$GLOBALS['mdo_mentta_product_query_pending'] = true;
		}
	}

	return $response;
}
add_filter( 'rest_request_before_callbacks', 'mdo_mentta_capture_rest_route', 10, 3 );

/**
 * Limit the MENTTA products endpoint to products explicitly assigned to the
 * internal MENTTA category. Existing product_id requests are intersected with
 * the allow-list, so an unmarked product can never be fetched individually.
 */
function mdo_mentta_filter_product_query_args( $args ) {
	if ( empty( $GLOBALS['mdo_mentta_product_query_pending'] ) ) {
		return $args;
	}

	$GLOBALS['mdo_mentta_product_query_pending'] = false;

	$term_id = mdo_mentta_get_marker_term_id();
	$allowed = array();

	if ( $term_id ) {
		$object_ids = get_objects_in_term( $term_id, 'product_cat' );
		if ( ! is_wp_error( $object_ids ) ) {
			$allowed = array_values( array_unique( array_filter( array_map( 'absint', $object_ids ) ) ) );
		}
	}

	if ( ! empty( $args['include'] ) ) {
		$requested = wp_parse_id_list( $args['include'] );
		$allowed   = array_values( array_intersect( $requested, $allowed ) );
	}

	// post__in => array( 0 ) is an intentional empty result. An empty array
	// would mean "no include restriction" in WP_Query and could expose all products.
	$args['include'] = $allowed ? $allowed : array( 0 );

	return $args;
}
add_filter( 'woocommerce_product_object_query_args', 'mdo_mentta_filter_product_query_args', 20 );

/**
 * Do not expose the marker as one of the product's commercial categories in
 * MENTTA's payload. It is selection metadata only.
 */
function mdo_mentta_hide_marker_from_mentta_product_terms( $terms, $object_ids, $taxonomies, $args ) {
	$route = isset( $GLOBALS['mdo_mentta_rest_route'] ) ? rtrim( (string) $GLOBALS['mdo_mentta_rest_route'], '/' ) : '';
	if ( '/mentta_marketplace/products' !== $route || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $terms;
	}

	$term_id = mdo_mentta_get_marker_term_id();
	if ( ! $term_id || ! is_array( $terms ) ) {
		return $terms;
	}

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $term_id ) {
				if ( $term instanceof WP_Term ) {
					return (int) $term->term_id !== $term_id;
				}
				if ( is_numeric( $term ) ) {
					return (int) $term !== $term_id;
				}
				return true;
			}
		)
	);
}
add_filter( 'wp_get_object_terms', 'mdo_mentta_hide_marker_from_mentta_product_terms', 20, 4 );

/**
 * Hide the internal category from public category queries (shop filters,
 * category grids, widgets, sitemaps and unauthenticated REST taxonomy lists).
 */
function mdo_mentta_hide_marker_from_term_queries( $args, $taxonomies ) {
	if ( mdo_mentta_is_management_context() || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $args;
	}

	$term_id = mdo_mentta_get_marker_term_id();
	if ( ! $term_id ) {
		return $args;
	}

	$exclude   = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : array();
	$exclude[] = $term_id;
	$args['exclude'] = array_values( array_unique( $exclude ) );

	return $args;
}
add_filter( 'get_terms_args', 'mdo_mentta_hide_marker_from_term_queries', 20, 2 );

/**
 * Hide the marker from product category output on the storefront.
 */
function mdo_mentta_hide_marker_from_product_terms( $terms, $post_id, $taxonomy ) {
	if ( 'product_cat' !== $taxonomy || mdo_mentta_is_management_context() || ! is_array( $terms ) ) {
		return $terms;
	}

	$term_id = mdo_mentta_get_marker_term_id();
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
add_filter( 'get_the_terms', 'mdo_mentta_hide_marker_from_product_terms', 20, 3 );

/**
 * Remove menu items that point at the internal category, including a custom
 * URL that may have been added manually.
 */
function mdo_mentta_hide_marker_menu_items( $items ) {
	if ( mdo_mentta_is_management_context() || ! is_array( $items ) ) {
		return $items;
	}

	$term_id = mdo_mentta_get_marker_term_id();
	$needle  = '/product-category/' . MDO_MENTTA_CATEGORY_SLUG;

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $term_id, $needle ) {
				if ( $term_id && isset( $item->object, $item->object_id ) && 'product_cat' === $item->object && (int) $item->object_id === $term_id ) {
					return false;
				}
				if ( isset( $item->url ) && false !== strpos( (string) $item->url, $needle ) ) {
					return false;
				}
				return true;
			}
		)
	);
}
add_filter( 'wp_get_nav_menu_items', 'mdo_mentta_hide_marker_menu_items', 20 );

/**
 * The marker must never have a public archive page.
 */
function mdo_mentta_block_public_archive() {
	if ( mdo_mentta_is_management_context() || ! function_exists( 'is_product_category' ) || ! is_product_category( MDO_MENTTA_CATEGORY_SLUG ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'mdo_mentta_block_public_archive', 1 );
