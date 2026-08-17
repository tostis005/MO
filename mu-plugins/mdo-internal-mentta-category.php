<?php
/**
 * Plugin Name: MDO - Internal MENTTA category
 * Description: Keeps the MENTTA WooCommerce category available to integrations/admins but hidden from the public storefront.
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MDO_MENTTA_CATEGORY_SLUG' ) ) {
	define( 'MDO_MENTTA_CATEGORY_SLUG', 'mentta' );
}

/**
 * Resolve the marker term ID without using get_terms(), because this function is
 * itself called from a get_terms_args filter on public requests.
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
 * Final safety net for term-query results. Some builders/widgets alter the query
 * arguments after get_terms_args; filtering the returned terms keeps MENTTA out
 * of frontend grids such as the categories block on the homepage.
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

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) use ( $term_id ) {
				return ! ( $term instanceof WP_Term ) || (int) $term->term_id !== $term_id;
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

/**
 * Homepage DOM fallback for category widgets/builders that render a stored term
 * selection rather than querying product_cat at request time.
 */
function mdo_mentta_hide_homepage_rendered_card() {
	if ( ! mdo_mentta_should_hide_publicly() || ! is_front_page() ) {
		return;
	}
	?>
	<script id="mdo-hide-mentta-home-card">
	(function () {
		function hideMenttaCards() {
			document.querySelectorAll('a[href*="/product-category/mentta"]').forEach(function (link) {
				var card = link.closest('li.product-category, .product-category, .wc-block-product-category, .elementor-loop-item, .elementor-grid-item');
				if (card) {
					card.remove();
				} else {
					link.remove();
				}
			});
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', hideMenttaCards);
		} else {
			hideMenttaCards();
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_mentta_hide_homepage_rendered_card', 100 );

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
