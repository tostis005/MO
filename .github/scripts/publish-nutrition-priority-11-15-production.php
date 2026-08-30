<?php
/**
 * Publish and verify bilingual nutrition batch 11-15:
 * 4 olive-oil articles + 1 vegetable nutrition article.
 * Uses one Spanish post + Falang _en_US_post_* metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$seed_dir = getenv( 'EMDO_NUTRITION_1115_SEED_DIR' );
if ( ! is_string( $seed_dir ) || '' === trim( $seed_dir ) || ! is_dir( $seed_dir ) ) {
    WP_CLI::error( 'EMDO_NUTRITION_1115_SEED_DIR is missing or invalid.' );
}
$seed_dir = rtrim( $seed_dir, '/\\' );

function emdo_nutrition_1115_articles( string $seed_dir ): array {
    $encoded = '';
    for ( $part = 1; $part <= 6; $part++ ) {
        $file = $seed_dir . '/content-seeds/aove-veg-nutrition-010269-' . $part . '.b64';
        if ( ! is_readable( $file ) ) {
            WP_CLI::error( 'Missing nutrition payload part ' . $part . '.' );
        }
        $encoded .= trim( (string) file_get_contents( $file ) );
    }

    $compressed = base64_decode( $encoded, true );
    if ( false === $compressed ) {
        WP_CLI::error( 'Nutrition payload is not valid Base64.' );
    }
    $json = gzdecode( $compressed );
    if ( false === $json ) {
        WP_CLI::error( 'Nutrition payload cannot be decompressed.' );
    }
    $articles = json_decode( $json, true );
    if ( ! is_array( $articles ) || 5 !== count( $articles ) ) {
        WP_CLI::error( 'Nutrition payload must contain exactly five articles.' );
    }

    $required = array(
        'slug', 'title', 'excerpt', 'content',
        'en_slug', 'en_title', 'en_excerpt', 'en_content',
        'category_slug', 'category_name', 'product_cat_name',
        'related_heading', 'en_related_heading'
    );
    foreach ( $articles as $article ) {
        foreach ( $required as $key ) {
            if ( ! isset( $article[ $key ] ) || '' === trim( (string) $article[ $key ] ) ) {
                WP_CLI::error( 'Invalid article payload: missing ' . $key . '.' );
            }
        }
    }
    return $articles;
}

function emdo_nutrition_1115_category_id( array $article ): int {
    $category = get_category_by_slug( (string) $article['category_slug'] );
    if ( $category instanceof WP_Term ) {
        return (int) $category->term_id;
    }
    $category = get_term_by( 'name', (string) $article['category_name'], 'category' );
    return $category instanceof WP_Term ? (int) $category->term_id : 0;
}

function emdo_nutrition_1115_product_cat( array $article ): ?WP_Term {
    $term = get_term_by( 'name', (string) $article['product_cat_name'], 'product_cat' );
    if ( $term instanceof WP_Term ) {
        return $term;
    }
    $term = get_term_by( 'slug', sanitize_title( (string) $article['product_cat_name'] ), 'product_cat' );
    return $term instanceof WP_Term ? $term : null;
}

function emdo_nutrition_1115_related_block( array $article, WP_Term $product_cat, bool $english = false ): string {
    $heading = $english ? (string) $article['en_related_heading'] : (string) $article['related_heading'];
    return '<h2>' . esc_html( $heading ) . '</h2>' . "\n"
        . '[products category="' . esc_attr( $product_cat->slug ) . '" limit="4" columns="4" orderby="date" order="DESC"]';
}

function emdo_nutrition_1115_render_content( array $article, WP_Term $product_cat, bool $english = false ): string {
    $content = $english ? (string) $article['en_content'] : (string) $article['content'];
    return str_replace(
        '<!-- EMDO_RELATED_PRODUCTS -->',
        emdo_nutrition_1115_related_block( $article, $product_cat, $english ),
        $content
    );
}

function emdo_nutrition_1115_generic_image_id(): int {
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

function emdo_nutrition_1115_save_english( int $post_id, array $article, string $english_content ): void {
    update_post_meta( $post_id, '_en_US_post_title', (string) $article['en_title'] );
    update_post_meta( $post_id, '_en_US_post_name', (string) $article['en_slug'] );
    update_post_meta( $post_id, '_en_US_post_excerpt', (string) $article['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', $english_content );
    update_post_meta( $post_id, '_en_US_published', '1' );
}

$articles = emdo_nutrition_1115_articles( $seed_dir );
$image_id = emdo_nutrition_1115_generic_image_id();

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

    $category_id = emdo_nutrition_1115_category_id( $article );
    if ( $category_id <= 0 ) {
        WP_CLI::error( 'Blog category could not be resolved for: ' . $slug );
    }
    $product_cat = emdo_nutrition_1115_product_cat( $article );
    if ( ! $product_cat instanceof WP_Term ) {
        WP_CLI::error( 'WooCommerce product category could not be resolved for: ' . $slug );
    }

    $spanish_content = emdo_nutrition_1115_render_content( $article, $product_cat, false );
    $english_content = emdo_nutrition_1115_render_content( $article, $product_cat, true );

    if ( false !== strpos( $spanish_content, 'EMDO_RELATED_PRODUCTS' ) || false !== strpos( $english_content, 'EMDO_RELATED_PRODUCTS' ) ) {
        WP_CLI::error( 'Related products placeholder was not rendered for: ' . $slug );
    }

    $result = wp_insert_post(
        wp_slash(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'post_title'     => (string) $article['title'],
                'post_name'      => $slug,
                'post_excerpt'   => (string) $article['excerpt'],
                'post_content'   => $spanish_content,
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
    emdo_nutrition_1115_save_english( $post_id, $article, $english_content );

    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
        $errors[] = 'Post not published: ' . $slug;
    }
    if ( trim( (string) $post->post_title ) !== trim( (string) $article['title'] ) ) {
        $errors[] = 'Spanish title mismatch: ' . $slug;
    }
    if ( trim( (string) $post->post_content ) !== trim( $spanish_content ) ) {
        $errors[] = 'Spanish content mismatch: ' . $slug;
    }
    if ( ! has_category( $category_id, $post_id ) ) {
        $errors[] = 'Category missing: ' . $slug;
    }
    if ( (int) get_post_thumbnail_id( $post_id ) <= 0 ) {
        $errors[] = 'Featured image missing: ' . $slug;
    }
    if ( false === strpos( (string) $post->post_content, '[products category="' . $product_cat->slug . '"' ) ) {
        $errors[] = 'Related products block missing: ' . $slug;
    }

    $checks = array(
        '_en_US_post_title'   => (string) $article['en_title'],
        '_en_US_post_name'    => (string) $article['en_slug'],
        '_en_US_post_excerpt' => (string) $article['en_excerpt'],
        '_en_US_post_content' => $english_content,
    );
    foreach ( $checks as $meta_key => $expected ) {
        if ( trim( (string) get_post_meta( $post_id, $meta_key, true ) ) !== trim( $expected ) ) {
            $errors[] = 'English meta mismatch (' . $meta_key . '): ' . $slug;
        }
    }
    if ( '1' !== (string) get_post_meta( $post_id, '_en_US_published', true ) ) {
        $errors[] = 'English publication flag missing: ' . $slug;
    }

    $rows[] = array(
        'id'            => $post_id,
        'slug'          => $slug,
        'en_slug'       => (string) $article['en_slug'],
        'title'         => (string) $article['title'],
        'en_title'      => (string) $article['en_title'],
        'category_slug' => (string) $article['category_slug'],
        'permalink'     => (string) get_permalink( $post_id ),
        'en_permalink'  => (string) home_url( '/en/' . trim( (string) $article['en_slug'], '/' ) . '/' ),
        'thumbnail_id'  => (int) get_post_thumbnail_id( $post_id ),
        'product_cat'   => (string) $product_cat->slug,
    );
}

flush_rewrite_rules( false );
wp_cache_flush();

$result = array(
    'verified' => empty( $errors ) && 5 === count( $rows ),
    'count'    => count( $rows ),
    'posts'    => $rows,
    'errors'   => $errors,
);

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( ! $result['verified'] ) {
    WP_CLI::error( 'Nutrition batch 11-15 publication verification failed.' );
}
