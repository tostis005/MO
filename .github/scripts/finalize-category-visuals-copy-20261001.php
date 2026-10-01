<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! taxonomy_exists( 'product_cat' ) ) {
	throw new RuntimeException( 'WooCommerce product categories unavailable.' );
}

$visual_dir = rtrim( (string) getenv( 'EMDO_CATEGORY_VISUAL_DIR' ), '/' );
if ( '' === $visual_dir || ! is_dir( $visual_dir ) ) {
	throw new RuntimeException( 'Category visual directory unavailable.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$categories = array(
	'pescados-mariscos' => array(
		'es_description' => '<p>Selección de pescados y mariscos de productores especializados, con opciones frescas, ahumadas, elaboradas y en conserva según disponibilidad. Aquí encontrarás desde lomos y loncheados hasta huevas y otras especialidades del mar.</p>',
		'en_name'        => 'Fish and seafood',
		'en_slug'        => 'fish-and-seafood',
		'en_description' => '<p>A selection of fish and seafood from specialist producers, with fresh, smoked, prepared and preserved options depending on availability. Discover fillets, sliced products, roe and other seafood specialities.</p>',
		'en_summary'     => 'Fish and seafood in different formats, from smoked fish and roe to other seafood specialities.',
		'file'           => $visual_dir . '/pescados-mariscos.webp',
		'filename'       => 'categoria-pescados-mariscos.webp',
		'title'          => 'Pescados y mariscos — salmón ahumado sobre pizarra',
		'alt'            => 'Salmón ahumado sobre pizarra — Pescados y mariscos',
		'visual_key'     => 'category-pescados-mariscos-20261001',
	),
	'foie-pates-untables' => array(
		'es_description' => '<p>Selección de foie gras, patés, mousses, rillettes y otras especialidades untables de productores seleccionados. Diferentes recetas, formatos y presentaciones pensadas para aperitivos, entrantes, tablas y ocasiones especiales.</p>',
		'en_name'        => 'Foie gras, pâtés and spreads',
		'en_slug'        => 'foie-gras-pates-spreads',
		'en_description' => '<p>A selection of foie gras, pâtés, mousses, rillettes and other spreads from selected producers. Different recipes, formats and presentations for appetisers, starters, sharing boards and special occasions.</p>',
		'en_summary'     => 'Foie gras, pâtés, mousses, rillettes and other spreads for serving, sharing or pairing.',
		'file'           => $visual_dir . '/foie-pates-untables.webp',
		'filename'       => 'categoria-foie-pates-untables.webp',
		'title'          => 'Foie, patés y untables — selección sobre pizarra',
		'alt'            => 'Foie gras y paté sobre pizarra — Foie, patés y untables',
		'visual_key'     => 'category-foie-pates-untables-20261001',
	),
);

$find_or_import_attachment = static function ( array $config ): int {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => '_emdo_category_visual_key',
			'meta_value'     => (string) $config['visual_key'],
		)
	);
	if ( $existing ) {
		$attachment_id = absint( $existing[0] );
		wp_update_post(
			array(
				'ID'         => $attachment_id,
				'post_title' => (string) $config['title'],
			)
		);
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $config['alt'] );
		return $attachment_id;
	}

	$source = (string) $config['file'];
	if ( ! is_readable( $source ) || filesize( $source ) < 1000 ) {
		throw new RuntimeException( 'Visual source unavailable: ' . $source );
	}

	$bits = wp_upload_bits( (string) $config['filename'], null, file_get_contents( $source ) );
	if ( ! empty( $bits['error'] ) ) {
		throw new RuntimeException( 'Could not upload visual: ' . (string) $bits['error'] );
	}

	$filetype = wp_check_filetype( basename( (string) $bits['file'] ), null );
	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => (string) $bits['url'],
			'post_mime_type' => (string) ( $filetype['type'] ?? 'image/webp' ),
			'post_title'     => (string) $config['title'],
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		(string) $bits['file']
	);
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		throw new RuntimeException( 'Could not create visual attachment.' );
	}

	$metadata = wp_generate_attachment_metadata( (int) $attachment_id, (string) $bits['file'] );
	if ( is_array( $metadata ) && $metadata ) {
		wp_update_attachment_metadata( (int) $attachment_id, $metadata );
	}
	update_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', (string) $config['alt'] );
	update_post_meta( (int) $attachment_id, '_emdo_category_visual_key', (string) $config['visual_key'] );
	return (int) $attachment_id;
};

$result = array(
	'batch'      => '20261001-category-visuals-copy',
	'categories' => array(),
);

foreach ( $categories as $slug => $config ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( 'Missing product category: ' . $slug );
	}

	$updated = wp_update_term(
		(int) $term->term_id,
		'product_cat',
		array(
			'description' => (string) $config['es_description'],
		)
	);
	if ( is_wp_error( $updated ) ) {
		throw new RuntimeException( 'Could not update category ' . $slug . ': ' . $updated->get_error_message() );
	}

	$attachment_id = $find_or_import_attachment( $config );
	update_term_meta( (int) $term->term_id, 'thumbnail_id', $attachment_id );
	update_term_meta( (int) $term->term_id, '_en_US_published', '1' );
	update_term_meta( (int) $term->term_id, '_en_US_name', (string) $config['en_name'] );
	update_term_meta( (int) $term->term_id, '_en_US_slug', (string) $config['en_slug'] );
	update_term_meta( (int) $term->term_id, '_en_US_description', (string) $config['en_description'] );
	update_term_meta( (int) $term->term_id, '_emdo_en_hub_summary', (string) $config['en_summary'] );

	clean_term_cache( (int) $term->term_id, 'product_cat' );
	$term = get_term( (int) $term->term_id, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( 'Could not reload category: ' . $slug );
	}

	$stored_thumbnail = absint( get_term_meta( (int) $term->term_id, 'thumbnail_id', true ) );
	$image_url        = $stored_thumbnail ? wp_get_attachment_url( $stored_thumbnail ) : '';
	if ( $stored_thumbnail !== $attachment_id || ! $image_url ) {
		throw new RuntimeException( 'Category thumbnail verification failed: ' . $slug );
	}

	$result['categories'][ $slug ] = array(
		'term_id'          => (int) $term->term_id,
		'name'             => (string) $term->name,
		'description'      => (string) $term->description,
		'attachment_id'    => $attachment_id,
		'image_url'        => (string) $image_url,
		'en_published'     => (string) get_term_meta( (int) $term->term_id, '_en_US_published', true ),
		'en_name'          => (string) get_term_meta( (int) $term->term_id, '_en_US_name', true ),
		'en_slug'          => (string) get_term_meta( (int) $term->term_id, '_en_US_slug', true ),
		'en_description'   => (string) get_term_meta( (int) $term->term_id, '_en_US_description', true ),
		'en_hub_summary'   => (string) get_term_meta( (int) $term->term_id, '_emdo_en_hub_summary', true ),
	);
}

wp_cache_flush();

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
