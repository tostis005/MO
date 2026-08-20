<?php
/**
 * Plugin Name: MDO English Category Query Normalizer
 * Description: Test-gated English category normalizer with request diagnostics. Disabled by default.
 * Version: 1.2.0
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
		if ( ! mdo_en_category_normalizer_enabled_20260821() && ! $test ) {
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

add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( ! mdo_en_category_normalizer_test_request_20260821() || ! $query->is_main_query() ) {
			return;
		}
		$GLOBALS['mdo_en_category_diag_20260821']['main_query'] = array(
			'is_tax'       => $query->is_tax(),
			'is_product_cat' => $query->is_tax( 'product_cat' ),
			'product_cat'  => (string) $query->get( 'product_cat' ),
			'taxonomy'     => (string) $query->get( 'taxonomy' ),
			'term'         => (string) $query->get( 'term' ),
			'post__in'     => array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) ),
			'tax_query'    => $query->get( 'tax_query' ),
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
