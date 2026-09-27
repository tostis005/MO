<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_duck_blog_words_010312( string $html ): int {
	$text = trim( preg_replace( '/\\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
	if ( '' === $text ) { return 0; }
	preg_match_all( '/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $matches );
	return count( $matches[0] );
}

function emdo_duck_blog_strip_landing_ctas_010312( string $html ): string {
	$clean = preg_replace(
		'#<div\\b[^>]*class=(["\\x{27}])[^"\\x{27}]*\\bemdo-seo-landing-cta\\b[^"\\x{27}]*\\1[^>]*>.*?</div>#isu',
		'',
		$html
	);
	if ( ! is_string( $clean ) ) { $clean = $html; }
	$clean = preg_replace( '/\\n{3,}/', "\n\n", $clean );
	return is_string( $clean ) ? trim( $clean ) : trim( $html );
}

function emdo_duck_blog_reference_stats_010312(): array {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_emdo_blog_hidden_listing',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$counts = array();
	foreach ( $ids as $id ) {
		$content = (string) get_post_field( 'post_content', (int) $id );
		$words   = emdo_duck_blog_words_010312( $content );
		if ( $words > 0 ) { $counts[] = $words; }
	}

	if ( empty( $counts ) ) {
		return array( 'sample' => 0, 'min' => 0, 'median' => 0, 'max' => 0 );
	}

	sort( $counts, SORT_NUMERIC );
	$n = count( $counts );
	$median = 0 === $n % 2
		? (int) round( ( $counts[ $n / 2 - 1 ] + $counts[ $n / 2 ] ) / 2 )
		: (int) $counts[ (int) floor( $n / 2 ) ];

	return array(
		'sample' => $n,
		'min'    => (int) $counts[0],
		'median' => $median,
		'max'    => (int) $counts[ $n - 1 ],
	);
}

function emdo_duck_blog_default_image_id_010312(): int {
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_attachment_image_alt',
			'meta_value'     => 'Imagen provisional del blog de El Mercado de Origen',
		)
	);
	if ( ! empty( $attachments ) ) {
		return (int) $attachments[0];
	}

	$source = trailingslashit( get_stylesheet_directory() ) . 'assets/images/blog-default.webp';
	if ( ! is_readable( $source ) ) {
		$recent = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		foreach ( $recent as $post_id ) {
			$thumb = (int) get_post_thumbnail_id( (int) $post_id );
			if ( $thumb > 0 ) { return $thumb; }
		}
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) { return 0; }

	$filename = wp_unique_filename( $uploads['path'], 'blog-default.webp' );
	$target   = trailingslashit( $uploads['path'] ) . $filename;
	if ( ! copy( $source, $target ) ) { return 0; }

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/webp',
			'post_title'     => 'Imagen provisional del blog de El Mercado de Origen',
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$target
	);
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $target );
		return 0;
	}

	$attachment_id = (int) $attachment_id;
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Imagen provisional del blog de El Mercado de Origen' );
	$metadata = wp_generate_attachment_metadata( $attachment_id, $target );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}
	return $attachment_id;
}

$reference = emdo_duck_blog_reference_stats_010312();
$image_id  = emdo_duck_blog_default_image_id_010312();

$ids = get_posts(
	array(
		'post_type'      => array( 'page', 'post' ),
		'post_status'    => 'any',
		'posts_per_page' => 100,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_key'       => '_emdo_seo_landing_batch',
		'meta_value'     => '20260927-duck-cluster',
	)
);

if ( 60 !== count( $ids ) ) {
	throw new Exception( 'Expected 60 duck cluster items, found ' . count( $ids ) );
}

$rows = array();

foreach ( $ids as $id ) {
	$id      = (int) $id;
	$current = get_post( $id );
	if ( ! $current instanceof WP_Post ) {
		throw new Exception( 'Missing post object for ID ' . $id );
	}

	$content = emdo_duck_blog_strip_landing_ctas_010312( (string) $current->post_content );
	$words   = emdo_duck_blog_words_010312( $content );

	if ( $words < 900 || $words > 1500 ) {
		throw new Exception( 'Duck article outside editorial size range: ID ' . $id . ' words=' . $words );
	}

	$postarr = array(
		'ID'             => $id,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'post_title'     => (string) $current->post_title,
		'post_name'      => (string) $current->post_name,
		'post_excerpt'   => (string) $current->post_excerpt,
		'post_content'   => $content,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_parent'    => 0,
	);

	$result = wp_update_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $result ) ) {
		throw new Exception( 'Could not convert ID ' . $id . ': ' . $result->get_error_message() );
	}

	/* Sin categoría pública: siguen siendo posts, pero no forman una taxonomía visible. */
	wp_set_object_terms( $id, array(), 'category', false );
	set_post_format( $id, false );

	update_post_meta( $id, '_emdo_blog_hidden_listing', '1' );
	update_post_meta( $id, '_emdo_blog_cluster', 'duck' );
	update_post_meta( $id, '_emdo_blog_editorial_standard', '010312' );
	update_post_meta( $id, '_emdo_seo_landing_hidden_navigation', '1' );
	update_post_meta( $id, '_emdo_seo_landing_updated_at', gmdate( 'c' ) );

	delete_post_meta( $id, '_wp_page_template' );
	delete_post_meta( $id, '_yoast_wpseo_primary_category' );
	delete_post_meta( $id, 'rank_math_primary_category' );

	if ( $image_id > 0 ) {
		set_post_thumbnail( $id, $image_id );
	}

	clean_post_cache( $id );

	$categories = wp_get_post_categories( $id );
	$thumbnail  = (int) get_post_thumbnail_id( $id );
	$type       = (string) get_post_type( $id );
	$status     = (string) get_post_status( $id );

	if ( 'post' !== $type || 'publish' !== $status ) {
		throw new Exception( 'Conversion verification failed for ID ' . $id );
	}
	if ( ! empty( $categories ) ) {
		throw new Exception( 'Duck article unexpectedly has categories: ID ' . $id );
	}
	if ( $thumbnail <= 0 ) {
		throw new Exception( 'Duck article missing featured image: ID ' . $id );
	}

	$rows[] = array(
		'id'         => $id,
		'key'        => (string) get_post_meta( $id, '_emdo_seo_landing_key', true ),
		'title'      => get_the_title( $id ),
		'slug'       => (string) get_post_field( 'post_name', $id ),
		'type'       => $type,
		'status'     => $status,
		'words'      => $words,
		'categories' => count( $categories ),
		'thumbnail'  => $thumbnail,
		'url'        => get_permalink( $id ),
	);
}

flush_rewrite_rules( false );

echo wp_json_encode(
	array(
		'batch'              => '20260927-duck-blog-posts',
		'count'              => count( $rows ),
		'reference_blog'     => $reference,
		'featured_image_id'  => $image_id,
		'posts'              => $rows,
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
) . PHP_EOL;
