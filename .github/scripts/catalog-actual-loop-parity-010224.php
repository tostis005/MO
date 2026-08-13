<?php
/**
 * Fuente de verdad para auditar el catálogo real en staging.
 *
 * Regla de negocio:
 * - producto publicado;
 * - visible en catálogo (no exclude-from-catalog);
 * - siempre con stock (no outofstock);
 * - público: excluye vendedores desactivados/offline;
 * - administrador: incluye también vendedores desactivados/offline.
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$visibility = function_exists( 'wc_get_product_visibility_term_ids' ) ? wc_get_product_visibility_term_ids() : array();
$exclude_catalog_id = isset( $visibility['exclude-from-catalog'] ) ? absint( $visibility['exclude-from-catalog'] ) : 0;
$outofstock_id      = isset( $visibility['outofstock'] ) ? absint( $visibility['outofstock'] ) : 0;
$excluded_terms     = array_values( array_filter( array( $exclude_catalog_id, $outofstock_id ) ) );
$disabled_vendors   = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
	? array_values( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) )
	: array();

$visibility_clause = '';
if ( $excluded_terms ) {
	$visibility_clause = " AND NOT EXISTS (
		SELECT 1
		FROM {$wpdb->term_relationships} vis_tr
		INNER JOIN {$wpdb->term_taxonomy} vis_tt ON vis_tt.term_taxonomy_id = vis_tr.term_taxonomy_id
		WHERE vis_tr.object_id = p.ID
		AND vis_tt.taxonomy = 'product_visibility'
		AND vis_tt.term_id IN (" . implode( ',', $excluded_terms ) . ')
	)';
}

$rows = $wpdb->get_results(
	"SELECT DISTINCT p.ID, p.post_author
	FROM {$wpdb->posts} p
	WHERE p.post_type = 'product'
	AND p.post_status = 'publish'{$visibility_clause}",
	ARRAY_A
); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

$admin_shop = array();
$public_shop = array();
$product_authors = array();
foreach ( (array) $rows as $row ) {
	$id = absint( $row['ID'] ?? 0 );
	$author = absint( $row['post_author'] ?? 0 );
	if ( $id <= 0 ) {
		continue;
	}
	$admin_shop[] = $id;
	$product_authors[ $id ] = $author;
	if ( ! in_array( $author, $disabled_vendors, true ) ) {
		$public_shop[] = $id;
	}
}

sort( $admin_shop, SORT_NUMERIC );
sort( $public_shop, SORT_NUMERIC );
ksort( $product_authors, SORT_NUMERIC );

$category_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
	)
);
if ( is_wp_error( $category_terms ) ) {
	$category_terms = array();
}

$categories = array();
foreach ( $category_terms as $term ) {
	if ( ! $term instanceof WP_Term ) {
		continue;
	}
	$term_ids = array( (int) $term->term_id );
	$children = get_term_children( (int) $term->term_id, 'product_cat' );
	if ( ! is_wp_error( $children ) ) {
		$term_ids = array_values( array_unique( array_merge( $term_ids, array_map( 'absint', (array) $children ) ) ) );
	}
	$ids = $wpdb->get_col(
		"SELECT DISTINCT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND tt.taxonomy = 'product_cat'
		AND tt.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . "){$visibility_clause}"
	); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$admin_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	$public_ids = array_values(
		array_filter(
			$admin_ids,
			static function ( int $id ) use ( $product_authors, $disabled_vendors ): bool {
				return ! in_array( (int) ( $product_authors[ $id ] ?? 0 ), $disabled_vendors, true );
			}
		)
	);
	sort( $admin_ids, SORT_NUMERIC );
	sort( $public_ids, SORT_NUMERIC );
	$categories[] = array(
		'id'     => (int) $term->term_id,
		'name'   => (string) $term->name,
		'slug'   => (string) $term->slug,
		'url'    => get_term_link( $term ),
		'public' => $public_ids,
		'admin'  => $admin_ids,
	);
}

$payload = array(
	'disabled_vendor_ids' => $disabled_vendors,
	'outofstock_term_id'   => $outofstock_id,
	'exclude_catalog_id'   => $exclude_catalog_id,
	'shop'                 => array(
		'url'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' ),
		'public' => $public_shop,
		'admin'  => $admin_shop,
	),
	'categories'           => $categories,
);

echo '__CATALOG_AUDIT__=' . base64_encode( wp_json_encode( $payload ) ) . PHP_EOL;
