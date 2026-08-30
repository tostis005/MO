<?php
/** Publish and verify the first bilingual legume nutrition batch in production. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$seed_dir = (string) getenv( 'EMDO_LEGUME_SEED_DIR' );
if ( '' === trim( $seed_dir ) || ! is_dir( $seed_dir ) ) {
	WP_CLI::error( 'EMDO_LEGUME_SEED_DIR is missing or invalid.' );
}
$seed_dir = rtrim( $seed_dir, '/\\' );
$module   = $seed_dir . '/blog-legume-nutrition-seed-010267.php';
if ( ! is_readable( $module ) ) { WP_CLI::error( 'Legume nutrition seed module is missing.' ); }
require_once $module;

foreach ( array( 'elmercado_legume_blog_category_id_010267', 'elmercado_blog_generic_image_id_010267', 'elmercado_upsert_legume_article_010267' ) as $fn ) {
	if ( ! function_exists( $fn ) ) { WP_CLI::error( 'Required publication function is unavailable: ' . $fn ); }
}

function emdo_legume_batch1_articles( string $seed_dir ): array {
	$encoded = '';
	for ( $part = 1; $part <= 4; $part++ ) {
		$file = $seed_dir . '/content-seeds/legume-nutrition-010267-' . $part . '.b64';
		if ( ! is_readable( $file ) ) { WP_CLI::error( 'Missing editorial payload part ' . $part . '.' ); }
		$encoded .= (string) file_get_contents( $file );
	}
	$encoded = preg_replace( '/[^A-Za-z0-9+\/=]/', '', $encoded );
	$compressed = is_string( $encoded ) ? base64_decode( $encoded, true ) : false;
	if ( false === $compressed ) { WP_CLI::error( 'Editorial payload is not valid base64.' ); }
	$serialized = gzdecode( $compressed );
	if ( false === $serialized ) { WP_CLI::error( 'Editorial payload cannot be decompressed.' ); }
	$articles = unserialize( $serialized, array( 'allowed_classes' => false ) );
	if ( ! is_array( $articles ) || 5 !== count( $articles ) ) { WP_CLI::error( 'Editorial payload must contain exactly five articles.' ); }
	return $articles;
}

$articles    = emdo_legume_batch1_articles( $seed_dir );
$category_id = elmercado_legume_blog_category_id_010267();
$image_id    = elmercado_blog_generic_image_id_010267();
if ( $category_id <= 0 ) { WP_CLI::error( 'Legumbres category could not be resolved.' ); }
if ( $image_id <= 0 ) { WP_CLI::error( 'Generic provisional blog image could not be resolved.' ); }

/* Proven Falang convention used by the successful editorial batches. */
update_term_meta( $category_id, '_en_US_name', 'Legumes' );
update_term_meta( $category_id, '_en_US_slug', 'legumes' );
update_term_meta( $category_id, '_en_US_published', '1' );

$rows   = array();
$errors = array();
foreach ( $articles as $article ) {
	$post_id = elmercado_upsert_legume_article_010267( $article, $category_id, $image_id );
	if ( $post_id <= 0 ) { WP_CLI::error( 'Could not publish article: ' . (string) ( $article['slug'] ?? '' ) ); }

	$en_title   = wp_strip_all_tags( (string) ( $article['en_title'] ?? '' ) );
	$en_slug    = sanitize_title( (string) ( $article['en_slug'] ?? '' ) );
	$en_excerpt = wp_strip_all_tags( (string) ( $article['en_excerpt'] ?? '' ) );
	$en_content = (string) ( $article['en_content'] ?? '' );
	if ( '' === $en_title || '' === $en_slug || '' === $en_content ) {
		WP_CLI::error( 'Incomplete English article data: ' . (string) ( $article['slug'] ?? '' ) );
	}

	update_post_meta( $post_id, '_en_US_post_title', $en_title );
	update_post_meta( $post_id, '_en_US_post_name', $en_slug );
	update_post_meta( $post_id, '_en_US_post_excerpt', $en_excerpt );
	update_post_meta( $post_id, '_en_US_post_content', $en_content );
	update_post_meta( $post_id, '_en_US_ready', '1' );
	update_post_meta( $post_id, '_en_US_published', '1' );

	if ( ! empty( $article['seo_title'] ) ) { update_post_meta( $post_id, '_emdo_seo_title', (string) $article['seo_title'] ); }
	if ( ! empty( $article['seo_desc'] ) ) { update_post_meta( $post_id, '_emdo_seo_description', (string) $article['seo_desc'] ); }
	if ( ! empty( $article['en_seo_title'] ) ) { update_post_meta( $post_id, '_en_US_seo_title', (string) $article['en_seo_title'] ); }
	if ( ! empty( $article['en_seo_desc'] ) ) { update_post_meta( $post_id, '_en_US_seo_description', (string) $article['en_seo_desc'] ); }

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) { $errors[] = 'Missing post: ' . (string) $article['slug']; continue; }
	if ( 'publish' !== $post->post_status ) { $errors[] = 'Post is not published: ' . (string) $article['slug']; }
	if ( trim( (string) $post->post_title ) !== trim( (string) $article['title'] ) ) { $errors[] = 'Spanish title mismatch: ' . (string) $article['slug']; }
	if ( ! has_category( $category_id, $post_id ) ) { $errors[] = 'Legumbres category missing: ' . (string) $article['slug']; }
	if ( (int) get_post_thumbnail_id( $post_id ) <= 0 ) { $errors[] = 'Featured image missing: ' . (string) $article['slug']; }

	$checks = array(
		'_en_US_post_title'   => $en_title,
		'_en_US_post_name'    => $en_slug,
		'_en_US_post_excerpt' => $en_excerpt,
		'_en_US_post_content' => $en_content,
		'_en_US_published'    => '1',
	);
	foreach ( $checks as $key => $expected ) {
		if ( trim( (string) get_post_meta( $post_id, $key, true ) ) !== trim( (string) $expected ) ) {
			$errors[] = 'Falang meta mismatch ' . $key . ': ' . (string) $article['slug'];
		}
	}

	$rows[] = array(
		'id'           => $post_id,
		'slug'         => (string) $article['slug'],
		'en_slug'      => $en_slug,
		'title'        => (string) $article['title'],
		'en_title'     => $en_title,
		'permalink'    => (string) get_permalink( $post_id ),
		'en_permalink' => (string) home_url( '/en/' . $en_slug . '/' ),
		'thumbnail_id' => (int) get_post_thumbnail_id( $post_id ),
	);
}

if ( defined( 'ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267' ) && defined( 'ELMERCADO_LEGUME_NUTRITION_SEED_VERSION_010267' ) ) {
	update_option( ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267, ELMERCADO_LEGUME_NUTRITION_SEED_VERSION_010267, false );
}
flush_rewrite_rules( false );
wp_cache_flush();

$result = array(
	'verified'    => empty( $errors ) && 5 === count( $rows ),
	'count'       => count( $rows ),
	'category_id' => $category_id,
	'posts'       => $rows,
	'errors'      => $errors,
);
echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( ! $result['verified'] ) { WP_CLI::error( 'Production publication verification failed.' ); }
