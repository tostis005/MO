<?php
/**
 * Publish and verify the first bilingual Iberian ham nutrition batch.
 * Uses the proven Falang metadata pattern: one Spanish post + _en_US_post_* meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$seed_dir = getenv( 'EMDO_JAMON_SEED_DIR' );
if ( ! is_string( $seed_dir ) || '' === trim( $seed_dir ) || ! is_dir( $seed_dir ) ) {
    WP_CLI::error( 'EMDO_JAMON_SEED_DIR is missing or invalid.' );
}
$seed_dir = rtrim( $seed_dir, '/\\' );

function emdo_jamon_articles_010268( string $seed_dir ): array {
    $encoded = '';
    for ( $part = 1; $part <= 4; $part++ ) {
        $file = $seed_dir . '/content-seeds/jamon-nutrition-010268-' . $part . '.b64';
        if ( ! is_readable( $file ) ) {
            WP_CLI::error( 'Missing ham nutrition payload part ' . $part . '.' );
        }
        $encoded .= trim( (string) file_get_contents( $file ) );
    }

    $compressed = base64_decode( $encoded, true );
    if ( false === $compressed ) {
        WP_CLI::error( 'Ham nutrition payload is not valid Base64.' );
    }
    $json = gzdecode( $compressed );
    if ( false === $json ) {
        WP_CLI::error( 'Ham nutrition payload cannot be decompressed.' );
    }
    $articles = json_decode( $json, true );
    if ( ! is_array( $articles ) || 5 !== count( $articles ) ) {
        WP_CLI::error( 'Ham nutrition payload must contain exactly five articles.' );
    }

    $required = array( 'slug', 'title', 'excerpt', 'content', 'en_slug', 'en_title', 'en_excerpt', 'en_content' );
    foreach ( $articles as $article ) {
        foreach ( $required as $key ) {
            if ( ! isset( $article[ $key ] ) || '' === trim( (string) $article[ $key ] ) ) {
                WP_CLI::error( 'Invalid article payload: missing ' . $key . '.' );
            }
        }
    }
    return $articles;
}

function emdo_jamon_category_id_010268(): int {
    $category = get_category_by_slug( 'jamones-y-paletas' );
    if ( $category instanceof WP_Term ) {
        return (int) $category->term_id;
    }
    $category = get_term_by( 'name', 'Jamones y paletas', 'category' );
    return $category instanceof WP_Term ? (int) $category->term_id : 0;
}

function emdo_generic_blog_image_id_010268(): int {
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

    $sample = get_page_by_path( 'nutrientes-legumbres-proteinas-fibra-hierro-vitaminas-minerales', OBJECT, 'post' );
    if ( $sample instanceof WP_Post ) {
        return (int) get_post_thumbnail_id( $sample->ID );
    }
    return 0;
}

function emdo_save_english_meta_010268( int $post_id, array $article ): void {
    update_post_meta( $post_id, '_en_US_post_title', (string) $article['en_title'] );
    update_post_meta( $post_id, '_en_US_post_name', (string) $article['en_slug'] );
    update_post_meta( $post_id, '_en_US_post_excerpt', (string) $article['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', (string) $article['en_content'] );
    update_post_meta( $post_id, '_en_US_published', '1' );
}

$articles    = emdo_jamon_articles_010268( $seed_dir );
$category_id = emdo_jamon_category_id_010268();
$image_id    = emdo_generic_blog_image_id_010268();

if ( $category_id <= 0 ) {
    WP_CLI::error( 'Jamones y paletas category could not be resolved.' );
}
if ( $image_id <= 0 ) {
    WP_CLI::error( 'Generic provisional blog image could not be resolved.' );
}

$rows   = array();
$errors = array();

foreach ( $articles as $article ) {
    $slug = (string) $article['slug'];
    if ( get_page_by_path( $slug, OBJECT, 'post' ) instanceof WP_Post ) {
        WP_CLI::error( 'Safety stop: target slug already exists: ' . $slug );
    }

    $result = wp_insert_post(
        wp_slash(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'post_title'     => (string) $article['title'],
                'post_name'      => $slug,
                'post_excerpt'   => (string) $article['excerpt'],
                'post_content'   => (string) $article['content'],
                'post_category'  => array( $category_id ),
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            )
        ),
        true
    );

    if ( is_wp_error( $result ) || (int) $result <= 0 ) {
        WP_CLI::error( 'Could not publish article: ' . $slug );
    }

    $post_id = (int) $result;
    set_post_thumbnail( $post_id, $image_id );
    emdo_save_english_meta_010268( $post_id, $article );

    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
        $errors[] = 'Post not published: ' . $slug;
    }
    if ( trim( (string) $post->post_title ) !== trim( (string) $article['title'] ) ) {
        $errors[] = 'Spanish title mismatch: ' . $slug;
    }
    if ( trim( (string) $post->post_content ) !== trim( (string) $article['content'] ) ) {
        $errors[] = 'Spanish content mismatch: ' . $slug;
    }
    if ( ! has_category( $category_id, $post_id ) ) {
        $errors[] = 'Category missing: ' . $slug;
    }
    if ( (int) get_post_thumbnail_id( $post_id ) <= 0 ) {
        $errors[] = 'Featured image missing: ' . $slug;
    }

    $meta_checks = array(
        '_en_US_post_title'   => 'en_title',
        '_en_US_post_name'    => 'en_slug',
        '_en_US_post_excerpt' => 'en_excerpt',
        '_en_US_post_content' => 'en_content',
    );
    foreach ( $meta_checks as $meta_key => $article_key ) {
        $stored = (string) get_post_meta( $post_id, $meta_key, true );
        if ( trim( $stored ) !== trim( (string) $article[ $article_key ] ) ) {
            $errors[] = 'English meta mismatch (' . $meta_key . '): ' . $slug;
        }
    }
    if ( '1' !== (string) get_post_meta( $post_id, '_en_US_published', true ) ) {
        $errors[] = 'English publication flag missing: ' . $slug;
    }

    $rows[] = array(
        'id'           => $post_id,
        'slug'         => $slug,
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
    'category_id' => $category_id,
    'posts'       => $rows,
    'errors'      => $errors,
);

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( ! $result['verified'] ) {
    WP_CLI::error( 'Ham nutrition publication verification failed.' );
}
