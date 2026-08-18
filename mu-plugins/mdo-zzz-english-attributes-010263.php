<?php
/**
 * Plugin Name: MDO English Attributes
 * Description: English-only WooCommerce attribute labels, values and canonical public Variety URLs.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdoea_en_010263(): bool {
	if ( function_exists( 'mdo_en_is_request' ) ) { return mdo_en_is_request(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return 1 === preg_match( '#^/en(?:/|$)#i', (string) wp_parse_url( $uri, PHP_URL_PATH ) );
}

function mdoea_root_010263(): string {
	return rtrim( (string) get_option( 'home' ), '/' );
}

function mdoea_attribute_labels_010263(): array {
	return array(
		'pa_alimentacion'  => 'Feeding',
		'pa_calidad'       => 'Quality',
		'pa_con-dop'       => 'PDO',
		'pa_curacion'      => 'Curing time',
		'pa_dop'           => 'Protected Designation of Origin',
		'pa_origen'        => 'Origin',
		'pa_peso'          => 'Weight',
		'pa_preparacion'   => 'Preparation',
		'pa_productor'     => 'Producer',
		'pa_rango-peso'    => 'Weight',
		'pa_raza-iberica'  => 'Iberian breed',
		'pa_tamano'        => 'Size',
		'pa_tipo-pieza'    => 'Cut',
		'pa_tipo-producto' => 'Product type',
		'pa_variedad'      => 'Variety',
	);
}

function mdoea_stored_label_en_010263( string $label ): string {
	$map = array(
		'Alimentación' => 'Feeding',
		'Calidad' => 'Quality',
		'Con DOP' => 'PDO',
		'Curación' => 'Curing time',
		'Denominación de origen' => 'Protected Designation of Origin',
		'Origen' => 'Origin',
		'Peso' => 'Weight',
		'Preparación' => 'Preparation',
		'Productor' => 'Producer',
		'Raza ibérica' => 'Iberian breed',
		'Tamaño' => 'Size',
		'Tipo de pieza' => 'Cut',
		'Tipo de producto' => 'Product type',
		'Variedad' => 'Variety',
	);
	return $map[ $label ] ?? $label;
}

/** Pure value translator so QA can exercise the same transformation without changing Spanish source data. */
function mdoea_translate_custom_attribute_value_010263( string $value ): string {
	$value = (string) preg_replace_callback(
		'/\bpieza\s+de\s+(\d+(?:[.,]\d+)?)\s*kg\b/iu',
		static function ( array $m ): string {
			return str_replace( ',', '.', $m[1] ) . ' kg piece';
		},
		$value
	);
	return $value;
}

add_filter( 'woocommerce_attribute_label', static function ( string $label, string $name, $product = null ): string {
	if ( ! mdoea_en_010263() ) { return $label; }
	$map = mdoea_attribute_labels_010263();
	$key = taxonomy_exists( $name ) ? $name : ( taxonomy_exists( 'pa_' . sanitize_title( $name ) ) ? 'pa_' . sanitize_title( $name ) : $name );
	return $map[ $key ] ?? mdoea_stored_label_en_010263( $label );
}, PHP_INT_MAX, 3 );

/* Translate custom (non-taxonomy) attribute labels/values only in the English storefront. */
add_filter( 'woocommerce_display_product_attributes', static function ( array $rows, $product ): array {
	if ( ! mdoea_en_010263() ) { return $rows; }
	foreach ( $rows as $key => $row ) {
		if ( isset( $row['label'] ) ) {
			$rows[ $key ]['label'] = mdoea_stored_label_en_010263( (string) $row['label'] );
		}
		if ( isset( $row['value'] ) ) {
			$rows[ $key ]['value'] = mdoea_translate_custom_attribute_value_010263( (string) $row['value'] );
		}
	}
	return $rows;
}, PHP_INT_MAX, 2 );

/* Catch theme/filter UIs that print the stored attribute label directly. */
add_filter( 'gettext', static function ( string $translated, string $text, string $domain ): string {
	if ( ! mdoea_en_010263() ) { return $translated; }
	return mdoea_stored_label_en_010263( $text ) !== $text ? mdoea_stored_label_en_010263( $text ) : $translated;
}, PHP_INT_MAX, 3 );

function mdoea_find_variety_english_010263( string $slug ) {
	$slug = sanitize_title( $slug );
	if ( '' === $slug || ! taxonomy_exists( 'pa_variedad' ) ) { return null; }
	$terms = get_terms( array(
		'taxonomy' => 'pa_variedad',
		'hide_empty' => false,
		'number' => 2,
		'meta_query' => array(
			'relation' => 'AND',
			array( 'key' => '_en_US_slug', 'value' => $slug, 'compare' => '=' ),
			array( 'key' => '_en_US_published', 'value' => '1', 'compare' => '=' ),
		),
	) );
	return ! is_wp_error( $terms ) && 1 === count( $terms ) ? $terms[0] : null;
}

function mdoea_variety_en_slug_010263( WP_Term $term ): string {
	$slug = sanitize_title( wp_strip_all_tags( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) ) );
	return $slug;
}

function mdoea_variety_url_010263( WP_Term $term ): string {
	$slug = mdoea_variety_en_slug_010263( $term );
	return '' === $slug ? '' : mdoea_root_010263() . '/en/variety/' . rawurlencode( $slug ) . '/';
}

function mdoea_public_parts_010263(): array {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$parts = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	if ( isset( $parts[0] ) && strtolower( (string) $parts[0] ) === 'en' ) { array_shift( $parts ); }
	return array_map( 'sanitize_title', $parts );
}

add_action( 'parse_request', static function ( WP $wp ): void {
	if ( ! mdoea_en_010263() ) { return; }
	$parts = mdoea_public_parts_010263();
	if ( count( $parts ) !== 2 || $parts[0] !== 'variety' ) { return; }
	$term = mdoea_find_variety_english_010263( $parts[1] );
	if ( ! $term instanceof WP_Term ) {
		$native = get_term_by( 'slug', $parts[1], 'pa_variedad' );
		$term = $native instanceof WP_Term ? $native : null;
	}
	if ( ! $term instanceof WP_Term ) { return; }
	foreach ( array( 'error','name','pagename','page_id','p','attachment','product','product_cat','product_tag','category_name','post_type' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
	$wp->query_vars['pa_variedad'] = $term->slug;
}, PHP_INT_MAX );

add_filter( 'term_link', static function ( string $url, WP_Term $term, string $taxonomy ): string {
	if ( ! mdoea_en_010263() || $taxonomy !== 'pa_variedad' ) { return $url; }
	$target = mdoea_variety_url_010263( $term );
	return '' !== $target ? $target : $url;
}, PHP_INT_MAX, 3 );

add_filter( 'redirect_canonical', static function ( $redirect ) {
	if ( ! mdoea_en_010263() ) { return $redirect; }
	$parts = mdoea_public_parts_010263();
	if ( count( $parts ) === 2 && $parts[0] === 'variety' && mdoea_find_variety_english_010263( $parts[1] ) instanceof WP_Term ) { return false; }
	return $redirect;
}, PHP_INT_MAX );

add_action( 'template_redirect', static function (): void {
	if ( ! mdoea_en_010263() || is_admin() || wp_doing_ajax() ) { return; }
	$parts = mdoea_public_parts_010263();
	if ( count( $parts ) !== 2 || ! in_array( $parts[0], array( 'variedad', 'variety' ), true ) ) { return; }

	$term = mdoea_find_variety_english_010263( $parts[1] );
	if ( ! $term instanceof WP_Term ) {
		$native = get_term_by( 'slug', $parts[1], 'pa_variedad' );
		$term = $native instanceof WP_Term ? $native : null;
	}
	if ( ! $term instanceof WP_Term ) { return; }
	$target = mdoea_variety_url_010263( $term );
	if ( '' === $target ) { return; }
	$current = mdoea_root_010263() . '/en/' . $parts[0] . '/' . rawurlencode( $parts[1] ) . '/';
	if ( untrailingslashit( $current ) !== untrailingslashit( $target ) ) {
		wp_safe_redirect( $target, 301, 'MDO English Variety canonical' );
		exit;
	}
}, 4 );

/* Last-mile rewrite for hard-coded attribute archive anchors. */
add_action( 'template_redirect', static function (): void {
	if ( ! mdoea_en_010263() || is_admin() || wp_doing_ajax() ) { return; }
	ob_start( static function ( string $html ): string {
		return (string) preg_replace_callback(
			'#href=("|\')([^"\']*/en/)(?:variedad|variety)/([^/"\']+)/?\1#i',
			static function ( array $m ): string {
				$slug = sanitize_title( $m[3] );
				$term = mdoea_find_variety_english_010263( $slug );
				if ( ! $term instanceof WP_Term ) {
					$native = get_term_by( 'slug', $slug, 'pa_variedad' );
					$term = $native instanceof WP_Term ? $native : null;
				}
				if ( ! $term instanceof WP_Term ) { return $m[0]; }
				$target = mdoea_variety_url_010263( $term );
				return '' !== $target ? 'href=' . $m[1] . esc_url( $target ) . $m[1] : $m[0];
			},
			$html
		);
	} );
}, 150 );
