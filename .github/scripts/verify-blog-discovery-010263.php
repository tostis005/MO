<?php
/** Production verification for blog discovery/category deployment. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$required = array(
	'jamones-y-paletas',
	'embutidos-y-curados',
	'carnes',
	'aceites',
	'conservas',
	'hortalizas-y-verduras',
	'legumbres',
	'packs-y-lotes',
);

$missing = array();
foreach ( $required as $slug ) {
	if ( ! get_term_by( 'slug', $slug, 'category' ) instanceof WP_Term ) {
		$missing[] = $slug;
	}
}

$authority_ids = get_posts(
	array(
		'post_type'              => 'post',
		'post_status'            => array( 'publish', 'draft', 'future' ),
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'meta_query'             => array( array( 'key' => '_emdo_authority_key', 'compare' => 'EXISTS' ) ),
		'suppress_filters'       => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

$guide = get_term_by( 'slug', 'guias-y-consejos', 'category' );
$guide_offenders = array();
$multi_category_authority = array();
$missing_primary = array();
$canonical_set = array_fill_keys( $required, true );

foreach ( array_map( 'intval', (array) $authority_ids ) as $post_id ) {
	$cats = (array) wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
	$slugs = array();
	foreach ( $cats as $cat ) {
		if ( $cat instanceof WP_Term ) {
			$slugs[] = sanitize_title( $cat->slug );
		}
	}
	$slugs = array_values( array_unique( $slugs ) );
	if ( $guide instanceof WP_Term && in_array( 'guias-y-consejos', $slugs, true ) ) {
		$guide_offenders[] = $post_id;
	}
	if ( 1 !== count( $slugs ) || ! isset( $canonical_set[ $slugs[0] ?? '' ] ) ) {
		$multi_category_authority[] = array( 'id' => $post_id, 'categories' => $slugs );
	}
	$primary = sanitize_title( (string) get_post_meta( $post_id, '_emdo_blog_primary_category', true ) );
	if ( '' === $primary || ! isset( $canonical_set[ $primary ] ) ) {
		$missing_primary[] = $post_id;
	}
}

$report = array(
	'required_categories'       => count( $required ),
	'missing_categories'        => $missing,
	'authority_posts'           => count( $authority_ids ),
	'guide_offenders'           => $guide_offenders,
	'noncanonical_authority'    => $multi_category_authority,
	'missing_primary_authority' => $missing_primary,
	'home_template'             => file_exists( get_stylesheet_directory() . '/home.php' ),
	'category_template'         => file_exists( get_stylesheet_directory() . '/category.php' ),
	'discovery_module'          => file_exists( get_stylesheet_directory() . '/inc/blog-discovery-010263.php' ),
	'discovery_guard'           => file_exists( WP_CONTENT_DIR . '/mu-plugins/mdo-blog-category-guard-010263.php' ),
);

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;

if (
	! empty( $missing ) ||
	! empty( $guide_offenders ) ||
	! empty( $multi_category_authority ) ||
	! empty( $missing_primary ) ||
	! $report['home_template'] ||
	! $report['category_template'] ||
	! $report['discovery_module'] ||
	! $report['discovery_guard']
) {
	throw new RuntimeException( 'Blog discovery verification failed.' );
}
