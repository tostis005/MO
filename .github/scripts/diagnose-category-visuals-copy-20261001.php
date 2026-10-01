<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$slugs = array( 'pescados-mariscos', 'foie-pates-untables' );
$template = get_stylesheet_directory() . '/page-categorias.php';
$template_contents = is_readable( $template ) ? (string) file_get_contents( $template ) : '';

$out = array(
	'batch' => '20261001-category-visuals-diagnostic',
	'siteurl' => get_option( 'siteurl' ),
	'template' => array(
		'path' => $template,
		'exists' => is_readable( $template ),
		'md5' => is_readable( $template ) ? md5_file( $template ) : '',
		'has_fish_summary' => false !== strpos( $template_contents, 'Pescados, huevas y especialidades del mar en distintos formatos y elaboraciones.' ),
		'has_foie_summary' => false !== strpos( $template_contents, 'Foie gras, patés, mousses, rillettes y otras especialidades untables de distintos productores.' ),
	),
	'terms' => array(),
	'active_plugins' => (array) get_option( 'active_plugins', array() ),
	'cache_files' => array(),
);

foreach ( $slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		$out['terms'][ $slug ] = null;
		continue;
	}
	$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	$meta = $thumb ? wp_get_attachment_metadata( $thumb ) : array();
	$out['terms'][ $slug ] = array(
		'id' => (int) $term->term_id,
		'name' => $term->name,
		'description' => $term->description,
		'count_native' => (int) $term->count,
		'count_visible' => function_exists( 'elmercado_catalog_visible_category_count_010217' ) ? (int) elmercado_catalog_visible_category_count_010217( (int) $term->term_id ) : null,
		'thumbnail_id' => $thumb,
		'image_full' => $thumb ? wp_get_attachment_image_url( $thumb, 'full' ) : '',
		'image_thumb' => $thumb ? wp_get_attachment_image_url( $thumb, 'woocommerce_thumbnail' ) : '',
		'image_meta' => is_array( $meta ) ? array(
			'width' => (int) ( $meta['width'] ?? 0 ),
			'height' => (int) ( $meta['height'] ?? 0 ),
			'file' => (string) ( $meta['file'] ?? '' ),
		) : array(),
		'alt' => $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '',
		'en_published' => (string) get_term_meta( $term->term_id, '_en_US_published', true ),
		'en_name' => (string) get_term_meta( $term->term_id, '_en_US_name', true ),
		'en_slug' => (string) get_term_meta( $term->term_id, '_en_US_slug', true ),
		'en_description' => (string) get_term_meta( $term->term_id, '_en_US_description', true ),
		'en_hub_summary' => (string) get_term_meta( $term->term_id, '_emdo_en_hub_summary', true ),
	);
}

$cache_candidates = array(
	WP_CONTENT_DIR . '/advanced-cache.php',
	WP_CONTENT_DIR . '/object-cache.php',
	WP_CONTENT_DIR . '/cache',
);
foreach ( $cache_candidates as $candidate ) {
	$out['cache_files'][ $candidate ] = file_exists( $candidate );
}

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
