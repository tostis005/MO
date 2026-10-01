<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$asset_dir = getenv( 'MDO_CATEGORY_ASSET_DIR' );
if ( ! is_string( $asset_dir ) || '' === trim( $asset_dir ) ) {
	throw new Exception( 'MDO_CATEGORY_ASSET_DIR is required.' );
}
$asset_dir = rtrim( $asset_dir, '/' );

$definitions = array(
	'pescados-mariscos' => array(
		'name_es'        => 'Pescados y mariscos',
		'description_es' => 'Pescados, huevas y especialidades del mar seleccionadas de distintos productores, en diferentes formatos y elaboraciones.',
		'name_en'        => 'Fish and seafood',
		'slug_en'        => 'fish-seafood',
		'description_en' => 'Fish, roe and seafood specialities from selected producers, available in different preparations and formats.',
		'hub_en'         => 'Fish, roe and seafood specialities in different preparations and formats.',
		'asset'          => 'category-pescados-mariscos.webp',
		'title'          => 'Categoría Pescados y mariscos',
		'alt'            => 'Salmón ahumado sobre pizarra',
		'marker'         => 'pescados-mariscos-20261001',
	),
	'foie-pates-untables' => array(
		'name_es'        => 'Foie, patés y untables',
		'description_es' => 'Foie gras, patés, mousses, rillettes y otras especialidades untables elaboradas por distintos productores, en diferentes recetas y formatos.',
		'name_en'        => 'Foie, pâtés and spreads',
		'slug_en'        => 'foie-pates-spreads',
		'description_en' => 'Foie gras, pâtés, mousses, rillettes and other spreadable specialities from selected producers, in different recipes and formats.',
		'hub_en'         => 'Foie gras, pâtés, mousses, rillettes and other spreadable specialities from selected producers.',
		'asset'          => 'category-foie-pates-untables.webp',
		'title'          => 'Categoría Foie, patés y untables',
		'alt'            => 'Foie gras, paté y untables sobre pizarra',
		'marker'         => 'foie-pates-untables-20261001',
	),
);

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$result = array(
	'batch' => '20261001-category-images-copy',
	'categories' => array(),
);

foreach ( $definitions as $slug => $def ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		throw new Exception( 'Missing product category: ' . $slug );
	}

	$asset = $asset_dir . '/' . $def['asset'];
	if ( ! is_readable( $asset ) || filesize( $asset ) < 10000 ) {
		throw new Exception( 'Missing or invalid asset: ' . $asset );
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_emdo_category_visual_marker',
			'meta_value'     => $def['marker'],
		)
	);
	foreach ( array_map( 'absint', $existing ) as $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
	}

	$bytes = file_get_contents( $asset );
	if ( false === $bytes ) {
		throw new Exception( 'Could not read asset: ' . $asset );
	}
	$upload = wp_upload_bits( $def['asset'], null, $bytes );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) || empty( $upload['url'] ) ) {
		throw new Exception( 'Upload failed for ' . $slug . ': ' . (string) ( $upload['error'] ?? 'unknown' ) );
	}

	$filetype = wp_check_filetype( basename( $upload['file'] ), null );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'] ?: 'image/webp',
			'post_title'     => $def['title'],
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		throw new Exception( 'Could not create attachment for ' . $slug );
	}
	$attachment_id = (int) $attachment_id;

	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $def['alt'] );
	update_post_meta( $attachment_id, '_emdo_category_visual_marker', $def['marker'] );

	$updated = wp_update_term(
		(int) $term->term_id,
		'product_cat',
		array(
			'name'        => $def['name_es'],
			'description' => $def['description_es'],
		)
	);
	if ( is_wp_error( $updated ) ) {
		throw new Exception( 'Could not update term ' . $slug . ': ' . $updated->get_error_message() );
	}

	update_term_meta( (int) $term->term_id, 'thumbnail_id', $attachment_id );
	update_term_meta( (int) $term->term_id, '_en_US_published', '1' );
	update_term_meta( (int) $term->term_id, '_en_US_name', $def['name_en'] );
	update_term_meta( (int) $term->term_id, '_en_US_slug', $def['slug_en'] );
	update_term_meta( (int) $term->term_id, '_en_US_description', $def['description_en'] );
	update_term_meta( (int) $term->term_id, '_emdo_en_hub_summary', $def['hub_en'] );

	clean_term_cache( (int) $term->term_id, 'product_cat' );
	clean_post_cache( $attachment_id );

	$check = get_term_by( 'id', (int) $term->term_id, 'product_cat' );
	$thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );
	$image_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'full' ) : '';
	$image_meta = $thumbnail_id ? wp_get_attachment_metadata( $thumbnail_id ) : array();

	if ( ! $check instanceof WP_Term || $thumbnail_id !== $attachment_id || '' === $image_url ) {
		throw new Exception( 'Verification failed for ' . $slug );
	}
	if ( '1' !== (string) get_term_meta( (int) $term->term_id, '_en_US_published', true ) ) {
		throw new Exception( 'English publication flag missing for ' . $slug );
	}

	$result['categories'][ $slug ] = array(
		'term_id'          => (int) $term->term_id,
		'name_es'          => (string) $check->name,
		'description_es'   => (string) $check->description,
		'name_en'          => (string) get_term_meta( (int) $term->term_id, '_en_US_name', true ),
		'slug_en'          => (string) get_term_meta( (int) $term->term_id, '_en_US_slug', true ),
		'description_en'   => (string) get_term_meta( (int) $term->term_id, '_en_US_description', true ),
		'hub_en'           => (string) get_term_meta( (int) $term->term_id, '_emdo_en_hub_summary', true ),
		'attachment_id'    => $attachment_id,
		'image_url'        => $image_url,
		'image_width'      => (int) ( $image_meta['width'] ?? 0 ),
		'image_height'     => (int) ( $image_meta['height'] ?? 0 ),
		'alt'              => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
	);
}

flush_rewrite_rules( false );
wp_cache_flush();

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
