<?php
/**
 * Plugin Name: MDO English Category Query Normalizer
 * Description: Replaces only the translated English product-category query slug with the canonical WooCommerce slug before WP_Query is built. Disabled by default and testable per request.
 * Version: 1.1.0
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

function mdo_en_category_normalizer_find_term_20260821( string $requested_slug ): ?WP_Term {
	$requested_slug = sanitize_title( $requested_slug );
	if ( '' === $requested_slug || ! taxonomy_exists( 'product_cat' ) ) {
		return null;
	}

	/* If the request already contains the canonical slug, leave it alone. */
	$canonical = get_term_by( 'slug', $requested_slug, 'product_cat' );
	if ( $canonical instanceof WP_Term ) {
		return $canonical;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
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

/**
 * Falang keeps the public English URL, but WooCommerce must query product_cat
 * with the canonical stored slug. Doing this at `request` avoids rebuilding the
 * product loop or running a second SQL query, so the rest of EMDO's stock,
 * vendor, destination and filter rules remain untouched.
 *
 * @param array<string,mixed> $query_vars Parsed request vars.
 * @return array<string,mixed>
 */
add_filter(
	'request',
	static function ( array $query_vars ): array {
		if ( is_admin() || ! mdo_en_category_normalizer_is_english_20260821() ) {
			return $query_vars;
		}
		if ( ! mdo_en_category_normalizer_enabled_20260821() && ! mdo_en_category_normalizer_test_request_20260821() ) {
			return $query_vars;
		}

		$requested = '';
		if ( isset( $query_vars['product_cat'] ) ) {
			$requested = (string) $query_vars['product_cat'];
		} elseif ( isset( $query_vars['taxonomy'], $query_vars['term'] ) && 'product_cat' === (string) $query_vars['taxonomy'] ) {
			$requested = (string) $query_vars['term'];
		}
		if ( '' === trim( $requested ) ) {
			return $query_vars;
		}

		$term = mdo_en_category_normalizer_find_term_20260821( $requested );
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
		return $query_vars;
	},
	PHP_INT_MAX
);
