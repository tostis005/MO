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
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $limit, 'UTF-8' );
	}
	return substr( $text, 0, $limit );
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
			return array(
				'id'   => (int) $term->term_id,
				'name' => (string) $term->name,
				'slug' => (string) $term->slug,
			);
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
			'key'       => (string) $key,
			'name'      => $name,
			'taxonomy'  => (bool) $attribute->is_taxonomy(),
			'visible'   => (bool) $attribute->get_visible(),
			'variation' => (bool) $attribute->get_variation(),
			'options'   => $options,
		);
	}
	return $rows;
}

function emdo_audit_context_meta( int $product_id ): array {
	$all = get_post_meta( $product_id );
	$out = array();
	foreach ( $all as $key => $values ) {
		$needle = strtolower( (string) $key );
		if ( ! preg_match( '/(?:mdo|emdo|supplier|vendor|source|import|wcfm)/', $needle ) ) {
			continue;
		}
		$clean = array();
		foreach ( (array) $values as $value ) {
			$value = maybe_unserialize( $value );
			if ( is_scalar( $value ) || null === $value ) {
				$clean[] = emdo_audit_text( (string) $value, 1200 );
			} else {
				$encoded = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				$clean[] = emdo_audit_text( (string) $encoded, 2400 );
			}
		}
		$out[ (string) $key ] = $clean;
	}
	ksort( $out );
	return $out;
}

$default_category_id = (int) get_option( 'default_product_cat', 0 );
$default_category = $default_category_id > 0 ? get_term( $default_category_id, 'product_cat' ) : null;

$statuses = array( 'publish', 'private', 'draft', 'pending' );
$ids = wc_get_products(
	array(
		'limit'  => -1,
		'return' => 'ids',
		'status' => $statuses,
		'orderby' => 'ID',
		'order'   => 'ASC',
	)
);

$candidates = array();
foreach ( array_map( 'intval', $ids ) as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) ) {
		continue;
	}

	$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $product->get_category_ids() ) ) ) );
	$meaningful_category_ids = $category_ids;
	if ( $default_category_id > 0 ) {
		$meaningful_category_ids = array_values( array_diff( $meaningful_category_ids, array( $default_category_id ) ) );
	}
	if ( $meaningful_category_ids ) {
		continue;
	}

	$author_id = (int) get_post_field( 'post_author', $product_id );
	$author = $author_id > 0 ? get_userdata( $author_id ) : false;
	$vendor_name = '';
	if ( function_exists( 'wcfm_get_vendor_store_name' ) && $author_id > 0 ) {
		$vendor_name = (string) wcfm_get_vendor_store_name( $author_id );
	}
	if ( '' === trim( $vendor_name ) && $author_id > 0 ) {
		$vendor_name = (string) get_user_meta( $author_id, 'store_name', true );
	}

	$taxonomy_terms = array();
	foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy => $object ) {
		if ( in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class' ), true ) ) {
			continue;
		}
		$terms = emdo_audit_term_rows( $product_id, (string) $taxonomy );
		if ( $terms ) {
			$taxonomy_terms[ (string) $taxonomy ] = $terms;
		}
	}

	$candidates[] = array(
		'id'          => $product_id,
		'status'      => (string) $product->get_status(),
		'type'        => (string) $product->get_type(),
		'name'        => (string) $product->get_name(),
		'slug'        => (string) $product->get_slug(),
		'sku'         => (string) $product->get_sku(),
		'permalink'   => (string) get_permalink( $product_id ),
		'author_id'   => $author_id,
		'author'      => $author ? (string) $author->display_name : '',
		'vendor_name' => $vendor_name,
		'categories'  => emdo_audit_term_rows( $product_id, 'product_cat' ),
		'tags'        => emdo_audit_term_rows( $product_id, 'product_tag' ),
		'attributes'  => emdo_audit_attributes( $product ),
		'taxonomies'  => $taxonomy_terms,
		'short_description' => emdo_audit_text( $product->get_short_description(), 5000 ),
		'description' => emdo_audit_text( $product->get_description(), 8000 ),
		'context_meta' => emdo_audit_context_meta( $product_id ),
	);
}

$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$category_rows = array();
if ( ! is_wp_error( $categories ) ) {
	foreach ( $categories as $term ) {
		$category_rows[] = array(
			'id'     => (int) $term->term_id,
			'name'   => (string) $term->name,
			'slug'   => (string) $term->slug,
			'parent' => (int) $term->parent,
			'count'  => (int) $term->count,
		);
	}
}

$attribute_schema = array();
if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
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
			'id'       => (int) $attribute->attribute_id,
			'name'     => (string) $attribute->attribute_label,
			'slug'     => (string) $attribute->attribute_name,
			'taxonomy' => $taxonomy,
			'terms'    => $term_rows,
		);
	}
}

$report = array(
	'generated_at' => current_time( 'mysql' ),
	'site_url' => (string) get_option( 'siteurl' ),
	'total_products_scanned' => count( $ids ),
	'candidate_count' => count( $candidates ),
	'default_product_category' => $default_category instanceof WP_Term ? array(
		'id' => (int) $default_category->term_id,
		'name' => (string) $default_category->name,
		'slug' => (string) $default_category->slug,
	) : null,
	'classifiers_available' => array(
		'ham'         => class_exists( 'MDO_Ham_Taxonomy' ),
		'cured'       => class_exists( 'MDO_Cured_Catalog' ),
		'adobados'    => class_exists( 'MDO_Adobados_Catalog' ),
		'accessories' => class_exists( 'MDO_Accessories_Catalog' ),
	),
	'categories' => $category_rows,
	'attribute_schema' => $attribute_schema,
	'candidates' => $candidates,
);

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
