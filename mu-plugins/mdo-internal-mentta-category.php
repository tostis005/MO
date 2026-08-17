<?php
/**
 * Plugin Name: MDO - Internal MENTTA category
 * Description: Keeps the MENTTA WooCommerce category available to integrations/admins but hidden from the public storefront.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MDO_MENTTA_CATEGORY_SLUG' ) ) {
	define( 'MDO_MENTTA_CATEGORY_SLUG', 'mentta' );
}

function mdo_mentta_marker_term_id() {
	$term = get_term_by( 'slug', MDO_MENTTA_CATEGORY_SLUG, 'product_cat' );
	return $term instanceof WP_Term ? (int) $term->term_id : 0;
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
	$needle  = '/product-category/' . MDO_MENTTA_CATEGORY_SLUG;

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $term_id, $needle ) {
				if ( $term_id && isset( $item->object, $item->object_id ) && 'product_cat' === $item->object && (int) $item->object_id === $term_id ) {
					return false;
				}
				return ! isset( $item->url ) || false === strpos( (string) $item->url, $needle );
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
