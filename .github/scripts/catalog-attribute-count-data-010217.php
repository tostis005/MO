<?php
/**
 * Genera expectativas de conteo de atributos para la categoría de embutidos.
 * Se ejecuta con `wp eval-file` dentro del staging.
 */

if ( ! function_exists( 'elmercado_catalog_filter_profiles' ) || ! function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
	fwrite( STDERR, "Missing catalog/WCFM helpers.\n" );
	exit( 20 );
}

$category = get_term_by( 'slug', 'embutidos-y-curados', 'product_cat' );
if ( ! $category instanceof WP_Term ) {
	fwrite( STDERR, "Missing embutidos-y-curados category.\n" );
	exit( 21 );
}

$children = get_term_children( (int) $category->term_id, 'product_cat' );
if ( is_wp_error( $children ) ) {
	$children = array();
}
$category_ids = array_values(
	array_unique(
		array_filter(
			array_merge( array( (int) $category->term_id ), array_map( 'absint', (array) $children ) )
		)
	)
);

$profiles = elmercado_catalog_filter_profiles();
$profile  = isset( $profiles['cured'] ) && is_array( $profiles['cured'] ) ? $profiles['cured'] : null;
if ( ! $profile || empty( $profile['attributes'] ) ) {
	fwrite( STDERR, "Missing cured filter profile.\n" );
	exit( 22 );
}

$disabled = array_values( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) );
$admins   = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( ! $admins ) {
	fwrite( STDERR, "Administrator user not found.\n" );
	exit( 23 );
}

/**
 * Cuenta productos publicados de cada término de atributo dentro de la familia
 * de categoría, deduplicando productos que estén asignados a varias hijas.
 *
 * @param int[] $excluded_authors Autores a excluir.
 * @return array<string,array<int,int>> Taxonomía => term_id => count.
 */
$compute = static function ( array $excluded_authors ) use ( $category_ids, $profile ): array {
	global $wpdb;

	$results = array();
	if ( ! $category_ids ) {
		return $results;
	}

	$cat_placeholders = implode( ',', array_fill( 0, count( $category_ids ), '%d' ) );
	$author_clause    = '';
	if ( $excluded_authors ) {
		$author_clause = ' AND p.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded_authors ) ) . ')';
	}

	foreach ( array_keys( (array) $profile['attributes'] ) as $attribute_slug ) {
		$taxonomy = wc_attribute_taxonomy_name( (string) $attribute_slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$sql = "SELECT attr_tt.term_id, COUNT(DISTINCT p.ID) AS product_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} cat_tr ON cat_tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} cat_tt ON cat_tt.term_taxonomy_id = cat_tr.term_taxonomy_id
			INNER JOIN {$wpdb->term_relationships} attr_tr ON attr_tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} attr_tt ON attr_tt.term_taxonomy_id = attr_tr.term_taxonomy_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND cat_tt.taxonomy = 'product_cat'
			AND cat_tt.term_id IN ({$cat_placeholders})
			AND attr_tt.taxonomy = %s{$author_clause}
			GROUP BY attr_tt.term_id";

		$prepare_args = array_merge( $category_ids, array( $taxonomy ) );
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, ...$prepare_args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results[ $taxonomy ] = array();
		foreach ( (array) $rows as $row ) {
			$term_id = isset( $row->term_id ) ? absint( $row->term_id ) : 0;
			$count   = isset( $row->product_count ) ? absint( $row->product_count ) : 0;
			if ( $term_id > 0 ) {
				$results[ $taxonomy ][ $term_id ] = $count;
			}
		}
	}

	return $results;
};

$public_counts = $compute( $disabled );
$admin_counts  = $compute( array() );
$rows          = array();

foreach ( (array) $profile['attributes'] as $attribute_slug => $label ) {
	$taxonomy = wc_attribute_taxonomy_name( (string) $attribute_slug );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		continue;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		continue;
	}

	foreach ( (array) $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$rows[] = array(
			'attribute' => (string) $attribute_slug,
			'label'     => (string) $label,
			'taxonomy'  => $taxonomy,
			'term_id'   => (int) $term->term_id,
			'slug'      => (string) $term->slug,
			'name'      => (string) $term->name,
			'public'    => (int) ( $public_counts[ $taxonomy ][ $term->term_id ] ?? 0 ),
			'admin'     => (int) ( $admin_counts[ $taxonomy ][ $term->term_id ] ?? 0 ),
		);
	}
}

$payload = array(
	'category'             => array(
		'id'   => (int) $category->term_id,
		'slug' => (string) $category->slug,
		'url'  => get_term_link( $category ),
	),
	'disabled_vendor_ids'  => $disabled,
	'rows'                 => $rows,
);

echo '__ATTRIBUTE_ROWS__=' . base64_encode( wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . "\n";