<?php
/**
 * Publish and verify the first bilingual legume nutrition batch in production.
 * Executed only through the production-safe GitHub Actions launcher.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$seed_dir = getenv( 'EMDO_LEGUME_SEED_DIR' );
if ( ! is_string( $seed_dir ) || '' === trim( $seed_dir ) || ! is_dir( $seed_dir ) ) {
	WP_CLI::error( 'EMDO_LEGUME_SEED_DIR is missing or invalid.' );
}
$seed_dir = rtrim( $seed_dir, '/\\' );

$module = $seed_dir . '/blog-legume-nutrition-seed-010267.php';
if ( ! function_exists( 'elmercado_seed_legume_nutrition_posts_010267' ) ) {
	if ( ! is_readable( $module ) ) {
		WP_CLI::error( 'Legume nutrition seed module is missing.' );
	}
	require_once $module;
}

if ( ! function_exists( 'elmercado_seed_legume_nutrition_posts_010267' ) ) {
	WP_CLI::error( 'Legume nutrition seed function is unavailable.' );
}

/** Decode the same editorial payload used by the theme seed. */
function emdo_legume_batch1_articles( string $seed_dir ): array {
	$encoded = '';
	for ( $part = 1; $part <= 4; $part++ ) {
		$file = $seed_dir . '/content-seeds/legume-nutrition-010267-' . $part . '.b64';
		if ( ! is_readable( $file ) ) {
			WP_CLI::error( 'Missing editorial payload part ' . $part . '.' );
		}
		$encoded .= trim( (string) file_get_contents( $file ) );
	}

	$compressed = base64_decode( $encoded, true );
	if ( false === $compressed ) {
		WP_CLI::error( 'Editorial payload is not valid base64.' );
	}
	$serialized = gzdecode( $compressed );
	if ( false === $serialized ) {
		WP_CLI::error( 'Editorial payload cannot be decompressed.' );
	}
	$articles = unserialize( $serialized, array( 'allowed_classes' => false ) );
	if ( ! is_array( $articles ) || 5 !== count( $articles ) ) {
		WP_CLI::error( 'Editorial payload must contain exactly five articles.' );
	}

	return $articles;
}

$articles = emdo_legume_batch1_articles( $seed_dir );

/* Force an idempotent upsert on production even if a previous partial run exists. */
if ( defined( 'ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267' ) ) {
	delete_option( ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267 );
}
elmercado_seed_legume_nutrition_posts_010267();

if ( ! function_exists( 'elmercado_falang_english_language_010267' ) || ! class_exists( '\\Falang\\Core\\Post' ) ) {
	WP_CLI::error( 'Falang is not available after publication.' );
}
$language = elmercado_falang_english_language_010267();
if ( ! is_object( $language ) ) {
	WP_CLI::error( 'English Falang language could not be resolved.' );
}

$category = get_category_by_slug( 'legumbres' );
if ( ! $category instanceof WP_Term ) {
	WP_CLI::error( 'Legumbres category is missing after publication.' );
}

$rows   = array();
$errors = array();
foreach ( $articles as $article ) {
	$post = get_page_by_path( (string) $article['slug'], OBJECT, 'post' );
	if ( ! $post instanceof WP_Post ) {
		$errors[] = 'Missing post: ' . (string) $article['slug'];
		continue;
	}

	$post_id = (int) $post->ID;
	if ( 'publish' !== $post->post_status ) {
		$errors[] = 'Post is not published: ' . (string) $article['slug'];
	}
	if ( trim( (string) $post->post_title ) !== trim( (string) $article['title'] ) ) {
		$errors[] = 'Spanish title mismatch: ' . (string) $article['slug'];
	}
	if ( trim( (string) $post->post_excerpt ) !== trim( (string) $article['excerpt'] ) ) {
		$errors[] = 'Spanish excerpt mismatch: ' . (string) $article['slug'];
	}
	if ( trim( (string) $post->post_content ) !== trim( (string) $article['content'] ) ) {
		$errors[] = 'Spanish content mismatch: ' . (string) $article['slug'];
	}
	if ( ! has_category( (int) $category->term_id, $post_id ) ) {
		$errors[] = 'Legumbres category missing: ' . (string) $article['slug'];
	}
	if ( (int) get_post_thumbnail_id( $post_id ) <= 0 ) {
		$errors[] = 'Featured image missing: ' . (string) $article['slug'];
	}

	$falang_post = new \Falang\Core\Post( $post_id );
	$translated  = array();
	$mapping     = array(
		'post_title'   => 'en_title',
		'post_content' => 'en_content',
		'post_excerpt' => 'en_excerpt',
		'post_name'    => 'en_slug',
	);
	foreach ( $mapping as $field => $article_key ) {
		try {
			$value = $falang_post->translate_post_field( $post, $field, $language, (string) $post->{$field} );
		} catch ( Throwable $exception ) {
			$value = '';
		}
		$translated[ $field ] = (string) $value;
		if ( trim( (string) $value ) !== trim( (string) $article[ $article_key ] ) ) {
			$errors[] = 'English ' . $field . ' mismatch: ' . (string) $article['slug'];
		}
	}

	$rows[] = array(
		'id'           => $post_id,
		'slug'         => (string) $article['slug'],
		'en_slug'      => (string) $article['en_slug'],
		'title'        => (string) $article['title'],
		'en_title'     => (string) $article['en_title'],
		'permalink'    => (string) get_permalink( $post_id ),
		'en_permalink' => (string) home_url( '/en/' . trim( (string) $article['en_slug'], '/' ) . '/' ),
		'thumbnail_id' => (int) get_post_thumbnail_id( $post_id ),
	);
}

flush_rewrite_rules( false );
wp_cache_flush();

$result = array(
	'verified'    => empty( $errors ) && 5 === count( $rows ),
	'count'       => count( $rows ),
	'category_id' => (int) $category->term_id,
	'posts'       => $rows,
	'errors'      => $errors,
);

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( ! $result['verified'] ) {
	WP_CLI::error( 'Production publication verification failed.' );
}
