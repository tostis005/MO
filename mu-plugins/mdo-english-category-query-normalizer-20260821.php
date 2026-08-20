<?php
/**
 * Plugin Name: MDO English Category Query Normalizer
 * Description: Normalizes English WooCommerce category archives to the canonical product_cat term ID. Disabled by default and safely testable per request.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_EN_CATEGORY_NORMALIZER_OPTION_20260821 = 'mdo_en_category_query_normalizer_enabled_20260821';

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

/** Remove only product_cat clauses while preserving every other active filter. */
function mdo_en_category_normalizer_without_product_cat_20260821( array $query ): array {
	$out = array();
	if ( isset( $query['relation'] ) ) {
		$out['relation'] = $query['relation'];
	}

	foreach ( $query as $key => $clause ) {
		if ( 'relation' === $key ) {
			continue;
		}
		if ( ! is_array( $clause ) ) {
			continue;
		}
		if ( isset( $clause['taxonomy'] ) ) {
			if ( 'product_cat' !== (string) $clause['taxonomy'] ) {
				$out[] = $clause;
			}
			continue;
		}
		$nested = mdo_en_category_normalizer_without_product_cat_20260821( $clause );
		$nested_count = count( array_diff_key( $nested, array( 'relation' => true ) ) );
		if ( $nested_count > 0 ) {
			$out[] = $nested;
		}
	}
	return $out;
}

function mdo_en_category_normalizer_term_20260821( WP_Query $query ): ?WP_Term {
	$object = $query->get_queried_object();
	if ( $object instanceof WP_Term && 'product_cat' === $object->taxonomy ) {
		return $object;
	}

	$candidates = array(
		(string) $query->get( 'product_cat' ),
		(string) $query->get( 'term' ),
	);
	foreach ( $candidates as $candidate ) {
		$slug = sanitize_title( $candidate );
		if ( '' === $slug ) {
			continue;
		}
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}

		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $possible ) {
			if ( ! $possible instanceof WP_Term ) {
				continue;
			}
			$en_slug = sanitize_title( (string) get_term_meta( $possible->term_id, '_en_US_slug', true ) );
			$en_name = sanitize_title( (string) get_term_meta( $possible->term_id, '_en_US_name', true ) );
			if ( $slug === $en_slug || $slug === $en_name ) {
				return $possible;
			}
		}
	}
	return null;
}

add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! mdo_en_category_normalizer_is_english_20260821() || ! $query->is_tax( 'product_cat' ) ) {
			return;
		}
		if ( ! mdo_en_category_normalizer_enabled_20260821() && ! mdo_en_category_normalizer_test_request_20260821() ) {
			return;
		}

		$term = mdo_en_category_normalizer_term_20260821( $query );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$tax_query   = mdo_en_category_normalizer_without_product_cat_20260821( (array) $query->get( 'tax_query' ) );
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => array( (int) $term->term_id ),
			'include_children' => true,
			'operator'         => 'IN',
		);

		/* Remove translated-slug query vars so WordPress cannot add a second,
		 * contradictory product_cat clause after this late normalization. */
		$query->set( 'product_cat', '' );
		$query->set( 'taxonomy', '' );
		$query->set( 'term', '' );
		$query->set( 'tax_query', $tax_query );
		$query->tax_query = new WP_Tax_Query( $tax_query );
		$query->queried_object = $term;
		$query->queried_object_id = (int) $term->term_id;
		$query->set( 'mdo_en_category_normalized_20260821', 1 );
	},
	PHP_INT_MAX
);
