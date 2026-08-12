<?php
/**
 * Export exhaustivo de Jamones y Paletas para auditoría.
 * Ejecutar con WP-CLI: wp eval-file <este archivo>
 * Reexport post-deploy: 2026-08-12.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
if ( ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "WooCommerce no disponible\n" );
	exit( 1 );
}

global $wpdb;

function emo_audit_term_map( int $product_id ): array {
	$out = array();
	foreach ( get_object_taxonomies( 'product', 'names' ) as $taxonomy ) {
		$terms = wp_get_post_terms( $product_id, $taxonomy );
		if ( is_wp_error( $terms ) || ! $terms ) {
			continue;
		}
		$out[ $taxonomy ] = array_map(
			static fn( WP_Term $term ): array => array( 'id' => (int) $term->term_id, 'name' => $term->name, 'slug' => $term->slug ),
			$terms
		);
	}
	return $out;
}

function emo_audit_attributes( WC_Product $product ): array {
	$out = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		$name   = $attribute->get_name();
		$values = $attribute->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) ) : $attribute->get_options();
		$out[ $name ] = array(
			'id'        => (int) $attribute->get_id(),
			'values'    => array_values( array_map( 'strval', (array) $values ) ),
			'visible'   => (bool) $attribute->get_visible(),
			'variation' => (bool) $attribute->get_variation(),
			'position'  => (int) $attribute->get_position(),
		);
	}
	return $out;
}

function emo_audit_variations( WC_Product $product ): array {
	if ( ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$out = array();
	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}
		$out[] = array(
			'id'             => (int) $variation->get_id(),
			'status'         => $variation->get_status(),
			'sku'            => $variation->get_sku(),
			'attributes'     => $variation->get_attributes(),
			'description'    => $variation->get_description(),
			'price'          => $variation->get_price( 'edit' ),
			'regular_price'  => $variation->get_regular_price( 'edit' ),
			'sale_price'     => $variation->get_sale_price( 'edit' ),
			'weight'         => $variation->get_weight( 'edit' ),
			'stock_status'   => $variation->get_stock_status(),
			'manage_stock'   => $variation->get_manage_stock(),
			'stock_quantity' => $variation->get_stock_quantity(),
		);
	}
	return $out;
}

function emo_audit_yith( int $product_id ): array {
	global $wpdb;
	$addons = $wpdb->prefix . 'yith_wapo_addons';
	$assoc  = $wpdb->prefix . 'yith_wapo_blocks_assoc';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $addons ) ) !== $addons || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $assoc ) ) !== $assoc ) {
		return array();
	}
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT a.id, a.block_id, a.settings, a.options FROM {$addons} a INNER JOIN {$assoc} x ON x.rule_id = a.block_id WHERE x.type = 'product' AND x.object = %s ORDER BY a.id ASC",
			(string) $product_id
		),
		ARRAY_A
	);
	$out = array();
	foreach ( (array) $rows as $row ) {
		$out[] = array(
			'id'       => isset( $row['id'] ) ? (int) $row['id'] : null,
			'block_id' => isset( $row['block_id'] ) ? (int) $row['block_id'] : null,
			'settings' => maybe_unserialize( $row['settings'] ?? '' ),
			'options'  => maybe_unserialize( $row['options'] ?? '' ),
		);
	}
	return $out;
}

function emo_audit_source( int $product_id ): array {
	global $wpdb;
	$source_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
	if ( $source_id <= 0 || ! class_exists( 'MDO_Database' ) ) {
		return array();
	}
	$table = MDO_Database::table( 'source_products' );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $source_id ), ARRAY_A );
	if ( ! is_array( $row ) ) {
		return array();
	}
	if ( isset( $row['source_payload'] ) ) {
		$decoded = json_decode( (string) $row['source_payload'], true );
		$row['source_payload'] = is_array( $decoded ) ? $decoded : $row['source_payload'];
	}
	return $row;
}

function emo_audit_is_target( WC_Product $product ): bool {
	if ( get_post_meta( $product->get_id(), '_emdo_ham_taxonomy_snapshot', true ) ) {
		return true;
	}
	$haystack = remove_accents( strtolower( $product->get_name() ) );
	$cats = wp_get_post_terms( $product->get_id(), 'product_cat' );
	if ( ! is_wp_error( $cats ) ) {
		foreach ( $cats as $cat ) {
			$haystack .= ' ' . remove_accents( strtolower( $cat->name . ' ' . $cat->slug ) );
		}
	}
	return (bool) ( preg_match( '/\b(?:jamon(?:es)?|paleta(?:s)?)\b/u', $haystack ) && ! preg_match( '/\b(?:jamonero|cuchillo|funda|soporte)\b/u', $haystack ) );
}

$items = array();
$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft', 'pending' ) ) );
foreach ( array_map( 'intval', $ids ) as $id ) {
	$product = wc_get_product( $id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) || ! emo_audit_is_target( $product ) ) {
		continue;
	}
	$author = get_user_by( 'id', (int) get_post_field( 'post_author', $id ) );
	$items[] = array(
		'id'                   => $id,
		'status'               => $product->get_status(),
		'type'                 => $product->get_type(),
		'slug'                 => $product->get_slug(),
		'permalink'            => get_permalink( $id ),
		'name'                 => $product->get_name(),
		'sku'                  => $product->get_sku(),
		'description'          => $product->get_description(),
		'short_description'    => $product->get_short_description(),
		'price'                => $product->get_price( 'edit' ),
		'regular_price'        => $product->get_regular_price( 'edit' ),
		'sale_price'           => $product->get_sale_price( 'edit' ),
		'weight'               => $product->get_weight( 'edit' ),
		'stock_status'         => $product->get_stock_status(),
		'vendor'               => $author ? array( 'id' => (int) $author->ID, 'name' => $author->display_name ) : null,
		'terms'                => emo_audit_term_map( $id ),
		'attributes'           => emo_audit_attributes( $product ),
		'variations'           => emo_audit_variations( $product ),
		'yith_addons'          => emo_audit_yith( $id ),
		'source'               => emo_audit_source( $id ),
		'classification_json'  => get_post_meta( $id, '_emdo_ham_taxonomy_snapshot', true ),
		'source_product_id'    => (int) get_post_meta( $id, '_emdo_source_product_id', true ),
		'yoast_title'          => (string) get_post_meta( $id, '_yoast_wpseo_title', true ),
		'yoast_description'    => (string) get_post_meta( $id, '_yoast_wpseo_metadesc', true ),
		'rankmath_title'       => (string) get_post_meta( $id, 'rank_math_title', true ),
		'rankmath_description' => (string) get_post_meta( $id, 'rank_math_description', true ),
	);
}
usort( $items, static fn( array $a, array $b ): int => strcasecmp( $a['name'], $b['name'] ) );
echo wp_json_encode( array( 'generated_at' => current_time( 'mysql' ), 'count' => count( $items ), 'items' => $items ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
