<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft', 'pending' ) ) );
$out = array();
$knife = array();
foreach ( array_map( 'intval', $ids ) as $id ) {
	$product = wc_get_product( $id );
	if ( ! $product || $product->is_type( 'variation' ) ) { continue; }
	$name = html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$normalized = remove_accents( strtolower( $name ) );
	if ( preg_match( '/\b(?:jamon(?:es)?|paleta(?:s)?)\b/u', $normalized ) && str_contains( $normalized, 'cuchillo' ) ) {
		$knife[] = array(
			'id' => $id,
			'name' => $product->get_name(),
			'status' => $product->get_status(),
			'categories' => wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) ),
			'quality' => taxonomy_exists( 'pa_calidad' ) ? wc_get_product_terms( $id, 'pa_calidad', array( 'fields' => 'names' ) ) : array(),
			'prep' => taxonomy_exists( 'pa_preparacion' ) ? wc_get_product_terms( $id, 'pa_preparacion', array( 'fields' => 'names' ) ) : array(),
		);
	}
	$version = (string) get_post_meta( $id, '_emdo_ham_audit_version', true );
	if ( '2026-08-12.2' !== $version ) { continue; }
	$out[] = array(
		'id' => $id,
		'name' => $product->get_name(),
		'status' => $product->get_status(),
		'categories' => wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) ),
		'quality' => taxonomy_exists( 'pa_calidad' ) ? wc_get_product_terms( $id, 'pa_calidad', array( 'fields' => 'names' ) ) : array(),
		'race' => taxonomy_exists( 'pa_raza-iberica' ) ? wc_get_product_terms( $id, 'pa_raza-iberica', array( 'fields' => 'names' ) ) : array(),
		'prep' => taxonomy_exists( 'pa_preparacion' ) ? wc_get_product_terms( $id, 'pa_preparacion', array( 'fields' => 'names' ) ) : array(),
		'audit' => json_decode( (string) get_post_meta( $id, '_emdo_ham_audit', true ), true ),
	);
}
usort( $out, static fn( array $a, array $b ): int => $a['id'] <=> $b['id'] );
usort( $knife, static fn( array $a, array $b ): int => $a['id'] <=> $b['id'] );
echo wp_json_encode( array( 'count' => count( $out ), 'items' => $out, 'knife_count' => count( $knife ), 'knife_items' => $knife ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
