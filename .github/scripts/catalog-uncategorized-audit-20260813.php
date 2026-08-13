<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_products' ) ) {
	throw new RuntimeException( 'WooCommerce no está disponible.' );
}

function emdo_audit_text( $value, int $limit = 6000 ): string {
	$text = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( (string) $text );
	return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit, 'UTF-8' ) : substr( $text, 0, $limit );
}

function emdo_audit_norm( string $text ): string {
	$text = remove_accents( strtolower( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	$text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
	return trim( (string) preg_replace( '/\s+/u', ' ', (string) $text ) );
}

function emdo_audit_vendor_name( int $user_id ): string {
	$name = '';
	if ( function_exists( 'wcfm_get_vendor_store_name' ) && $user_id > 0 ) {
		$name = trim( (string) wcfm_get_vendor_store_name( $user_id ) );
	}
	if ( '' === $name && $user_id > 0 ) {
		$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
		if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
			$name = trim( (string) $settings['store_name'] );
		}
	}
	if ( '' === $name && $user_id > 0 ) {
		$name = trim( (string) get_user_meta( $user_id, 'store_name', true ) );
	}
	if ( '' === $name && $user_id > 0 ) {
		$user = get_userdata( $user_id );
		if ( $user instanceof WP_User ) {
			$name = trim( (string) $user->display_name );
		}
	}
	return $name;
}

function emdo_audit_vendor_target( string $vendor ): bool {
	$n = emdo_audit_norm( $vendor );
	return str_contains( $n, '1957' )
		|| str_contains( $n, 'hidalgo de la jara' )
		|| str_contains( $n, 'tolecarnes' )
		|| str_contains( $n, 'tole carnes' )
		|| str_contains( $n, 'el catedratico' )
		|| str_contains( $n, 'puente robles' );
}

function emdo_audit_term_rows( int $product_id, string $taxonomy ): array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = wp_get_object_terms( $product_id, $taxonomy );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return array_values( array_map(
		static function ( WP_Term $term ): array {
			return array( 'id' => (int) $term->term_id, 'name' => (string) $term->name, 'slug' => (string) $term->slug );
		},
		$terms
	) );
}

function emdo_audit_attributes( WC_Product $product ): array {
	$rows = array();
	foreach ( $product->get_attributes() as $key => $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		$name = (string) $attribute->get_name();
		$options = array();
		if ( $attribute->is_taxonomy() && taxonomy_exists( $name ) ) {
			foreach ( array_map( 'intval', $attribute->get_options() ) as $term_id ) {
				$term = get_term( $term_id, $name );
				if ( $term instanceof WP_Term ) {
					$options[] = array( 'id' => (int) $term->term_id, 'name' => (string) $term->name, 'slug' => (string) $term->slug );
				}
			}
		} else {
			$options = array_values( array_map( 'strval', $attribute->get_options() ) );
		}
		$rows[] = array(
			'key' => (string) $key,
			'name' => $name,
			'taxonomy' => (bool) $attribute->is_taxonomy(),
			'visible' => (bool) $attribute->get_visible(),
			'variation' => (bool) $attribute->get_variation(),
			'options' => $options,
		);
	}
	return $rows;
}

$ids = wc_get_products(
	array(
		'limit' => -1,
		'return' => 'ids',
		'status' => array( 'publish' ),
		'orderby' => 'ID',
		'order' => 'ASC',
	)
);

$products = array();
$vendors = array();
foreach ( array_map( 'intval', $ids ) as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) ) {
		continue;
	}
	$author_id = (int) get_post_field( 'post_author', $product_id );
	$vendor_name = emdo_audit_vendor_name( $author_id );
	if ( ! emdo_audit_vendor_target( $vendor_name ) ) {
		continue;
	}
	$vendors[ $vendor_name ] = ( $vendors[ $vendor_name ] ?? 0 ) + 1;
	$products[] = array(
		'id' => $product_id,
		'status' => (string) $product->get_status(),
		'type' => (string) $product->get_type(),
		'name' => (string) $product->get_name(),
		'slug' => (string) $product->get_slug(),
		'sku' => (string) $product->get_sku(),
		'catalog_visibility' => (string) $product->get_catalog_visibility(),
		'author_id' => $author_id,
		'vendor_name' => $vendor_name,
		'categories' => emdo_audit_term_rows( $product_id, 'product_cat' ),
		'attributes' => emdo_audit_attributes( $product ),
		'short_description' => emdo_audit_text( $product->get_short_description(), 5000 ),
		'description' => emdo_audit_text( $product->get_description(), 8000 ),
	);
}
ksort( $vendors, SORT_NATURAL | SORT_FLAG_CASE );

$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$category_rows = array();
if ( ! is_wp_error( $categories ) ) {
	foreach ( $categories as $term ) {
		$category_rows[] = array( 'id' => (int) $term->term_id, 'name' => (string) $term->name, 'slug' => (string) $term->slug, 'parent' => (int) $term->parent, 'count' => (int) $term->count );
	}
}

$attribute_schema = array();
foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
	$taxonomy = wc_attribute_taxonomy_name( (string) $attribute->attribute_name );
	$terms = taxonomy_exists( $taxonomy ) ? get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) : array();
	$term_rows = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$term_rows[] = array( 'id' => (int) $term->term_id, 'name' => (string) $term->name, 'slug' => (string) $term->slug, 'count' => (int) $term->count );
		}
	}
	$attribute_schema[] = array(
		'id' => (int) $attribute->attribute_id,
		'name' => (string) $attribute->attribute_label,
		'slug' => (string) $attribute->attribute_name,
		'taxonomy' => $taxonomy,
		'terms' => $term_rows,
	);
}

$report = array(
	'generated_at' => current_time( 'mysql' ),
	'site_url' => (string) get_option( 'siteurl' ),
	'published_products_scanned' => count( $ids ),
	'target_product_count' => count( $products ),
	'target_vendors' => $vendors,
	'classifiers_available' => array(
		'ham' => class_exists( 'MDO_Ham_Taxonomy' ),
		'cured' => class_exists( 'MDO_Cured_Catalog' ),
		'adobados' => class_exists( 'MDO_Adobados_Catalog' ),
		'accessories' => class_exists( 'MDO_Accessories_Catalog' ),
	),
	'categories' => $category_rows,
	'attribute_schema' => $attribute_schema,
	'products' => $products,
);

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
