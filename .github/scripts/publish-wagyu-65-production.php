<?php
/**
 * Publish the 65 bilingual Wagyu editorial payloads in production.
 *
 * Spanish is the canonical WordPress post and English is stored through the
 * Falang _en_US_* metadata pattern already used by the site. Idempotent by
 * slug + batch marker. Related products temporarily use WooCommerce Carnes;
 * an empty Wagyu product category is created for the future catalogue.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

const EMDO_WAGYU_65_MARKER = '2026-09-10.wagyu-65.v1';

$seed_root = getenv( 'EMDO_WAGYU_SEED_DIR' );
if ( ! is_string( $seed_root ) || '' === trim( $seed_root ) || ! is_dir( $seed_root ) ) {
    throw new RuntimeException( 'Invalid Wagyu seed directory.' );
}
$seed_root = rtrim( $seed_root, '/\\' );

function emdo_wagyu_load_articles( string $root ): array {
    $patterns = array(
        $root . '/.github/data/editorial-wagyu-batch01-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch02-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch03-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch04-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch05-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch06-20260910/*.php',
        $root . '/.github/data/editorial-wagyu-batch07-20260910/*.php',
    );
    $files = array();
    foreach ( $patterns as $pattern ) {
        $matched = glob( $pattern );
        if ( is_array( $matched ) ) {
            $files = array_merge( $files, $matched );
        }
    }
    natsort( $files );
    $files = array_values( $files );
    if ( 65 !== count( $files ) ) {
        throw new RuntimeException( 'Expected exactly 65 Wagyu payloads, found ' . count( $files ) . '.' );
    }

    $required = array(
        'key','slug','en_slug','topic','title','en_title','excerpt','en_excerpt',
        'focus_keyword','en_focus_keyword','seo_title','en_seo_title',
        'meta_description','en_meta_description','content','en_content',
    );
    $articles = array();
    $seen_es = array();
    $seen_en = array();
    foreach ( $files as $file ) {
        $article = include $file;
        if ( ! is_array( $article ) ) {
            throw new RuntimeException( 'Invalid payload: ' . basename( $file ) );
        }
        foreach ( $required as $key ) {
            if ( ! isset( $article[ $key ] ) || ( is_string( $article[ $key ] ) && '' === trim( $article[ $key ] ) ) ) {
                throw new RuntimeException( 'Missing ' . $key . ' in ' . basename( $file ) );
            }
        }
        if ( 'wagyu' !== (string) $article['topic'] ) {
            throw new RuntimeException( 'Unexpected topic in ' . basename( $file ) );
        }
        $slug = sanitize_title( (string) $article['slug'] );
        $en_slug = sanitize_title( (string) $article['en_slug'] );
        if ( isset( $seen_es[ $slug ] ) || isset( $seen_en[ $en_slug ] ) ) {
            throw new RuntimeException( 'Duplicate Wagyu slug: ' . $slug . ' / ' . $en_slug );
        }
        $seen_es[ $slug ] = true;
        $seen_en[ $en_slug ] = true;
        $article['_source_file'] = basename( $file );
        $articles[] = $article;
    }
    return $articles;
}

function emdo_wagyu_ensure_term( string $taxonomy, string $name, string $slug ): WP_Term {
    $term = get_term_by( 'slug', $slug, $taxonomy );
    if ( ! $term instanceof WP_Term ) {
        $term = get_term_by( 'name', $name, $taxonomy );
    }
    if ( ! $term instanceof WP_Term ) {
        $created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
        if ( is_wp_error( $created ) ) {
            throw new RuntimeException( 'Could not create ' . $taxonomy . ' ' . $name . ': ' . $created->get_error_message() );
        }
        $term = get_term( (int) $created['term_id'], $taxonomy );
    }
    if ( ! $term instanceof WP_Term ) {
        throw new RuntimeException( 'Could not resolve ' . $taxonomy . ' ' . $name . '.' );
    }
    update_term_meta( $term->term_id, '_en_US_name', 'Wagyu' );
    update_term_meta( $term->term_id, '_en_US_slug', 'wagyu' );
    update_term_meta( $term->term_id, '_en_US_published', '1' );
    return $term;
}

function emdo_wagyu_related_product_term(): WP_Term {
    $term = get_term_by( 'slug', 'carnes', 'product_cat' );
    if ( ! $term instanceof WP_Term ) {
        $term = get_term_by( 'name', 'Carnes', 'product_cat' );
    }
    if ( ! $term instanceof WP_Term ) {
        throw new RuntimeException( 'WooCommerce product category Carnes was not found.' );
    }
    return $term;
}

function emdo_wagyu_featured_image(): int {
    $ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wp_attachment_image_alt',
            'meta_value'     => 'Imagen provisional del blog de El Mercado de Origen',
        )
    );
    if ( ! empty( $ids ) ) {
        return (int) $ids[0];
    }

    $carnes = get_category_by_slug( 'carnes' );
    if ( $carnes instanceof WP_Term ) {
        $posts = get_posts(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'fields'         => 'ids',
                'category__in'   => array( (int) $carnes->term_id ),
            )
        );
        foreach ( $posts as $post_id ) {
            $thumb = (int) get_post_thumbnail_id( (int) $post_id );
            if ( $thumb > 0 ) {
                return $thumb;
            }
        }
    }
    return 0;
}

function emdo_wagyu_related_block( string $content, string $product_slug, bool $en ): string {
    $heading = $en ? 'Related meats from our shop' : 'Carnes relacionadas de nuestra tienda';
    $block = "\n<h2>" . esc_html( $heading ) . "</h2>\n";
    $block .= '[products category="' . esc_attr( $product_slug ) . '" limit="4" columns="4" orderby="date" order="DESC"]';
    if ( false !== strpos( $content, '<!-- EMDO_RELATED_PRODUCTS -->' ) ) {
        return str_replace( '<!-- EMDO_RELATED_PRODUCTS -->', $block, $content );
    }
    return rtrim( $content ) . $block . "\n";
}

function emdo_wagyu_save_meta( int $post_id, array $article, string $en_content ): void {
    update_post_meta( $post_id, '_emdo_editorial_wagyu_65', EMDO_WAGYU_65_MARKER );
    update_post_meta( $post_id, '_emdo_editorial_key', (string) $article['key'] );
    update_post_meta( $post_id, '_emdo_seo_title', (string) $article['seo_title'] );
    update_post_meta( $post_id, '_emdo_seo_description', (string) $article['meta_description'] );
    update_post_meta( $post_id, '_emdo_focus_keyword', (string) $article['focus_keyword'] );

    update_post_meta( $post_id, '_en_US_post_title', (string) $article['en_title'] );
    update_post_meta( $post_id, '_en_US_post_name', (string) $article['en_slug'] );
    update_post_meta( $post_id, '_en_US_post_excerpt', (string) $article['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', $en_content );
    update_post_meta( $post_id, '_en_US_seo_title', (string) $article['en_seo_title'] );
    update_post_meta( $post_id, '_en_US_seo_description', (string) $article['en_meta_description'] );
    update_post_meta( $post_id, '_en_US_focus_keyword', (string) $article['en_focus_keyword'] );
    update_post_meta( $post_id, '_en_US_ready', '1' );
    update_post_meta( $post_id, '_en_US_published', '1' );
}

$articles = emdo_wagyu_load_articles( $seed_root );
if ( ! taxonomy_exists( 'product_cat' ) ) {
    throw new RuntimeException( 'WooCommerce product_cat taxonomy is unavailable.' );
}

$blog_term = emdo_wagyu_ensure_term( 'category', 'Wagyu', 'wagyu' );
$shop_term = emdo_wagyu_ensure_term( 'product_cat', 'Wagyu', 'wagyu' );
$related_term = emdo_wagyu_related_product_term();
$image_id = emdo_wagyu_featured_image();

// Full preflight before the first write: do not overwrite unrelated existing posts.
foreach ( $articles as $article ) {
    $existing = get_page_by_path( (string) $article['slug'], OBJECT, 'post' );
    if ( $existing instanceof WP_Post ) {
        $marker = (string) get_post_meta( $existing->ID, '_emdo_editorial_wagyu_65', true );
        if ( EMDO_WAGYU_65_MARKER !== $marker ) {
            throw new RuntimeException( 'Safety stop: unmarked slug already exists: ' . $article['slug'] );
        }
    }
}

$created_ids = array();
$rows = array();
try {
    foreach ( $articles as $index => $article ) {
        $slug = (string) $article['slug'];
        $existing = get_page_by_path( $slug, OBJECT, 'post' );
        $es_content = emdo_wagyu_related_block( (string) $article['content'], (string) $related_term->slug, false );
        $en_content = emdo_wagyu_related_block( (string) $article['en_content'], (string) $related_term->slug, true );

        $postarr = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'post_title'     => (string) $article['title'],
            'post_name'      => $slug,
            'post_excerpt'   => (string) $article['excerpt'],
            'post_content'   => $es_content,
            'post_category'  => array( (int) $blog_term->term_id ),
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        );
        if ( $existing instanceof WP_Post ) {
            $postarr['ID'] = (int) $existing->ID;
            $result = wp_update_post( wp_slash( $postarr ), true );
        } else {
            $result = wp_insert_post( wp_slash( $postarr ), true );
        }
        if ( is_wp_error( $result ) || (int) $result <= 0 ) {
            $message = is_wp_error( $result ) ? $result->get_error_message() : 'unknown';
            throw new RuntimeException( 'Could not publish ' . $slug . ': ' . $message );
        }
        $post_id = (int) $result;
        if ( ! $existing instanceof WP_Post ) {
            $created_ids[] = $post_id;
        }
        emdo_wagyu_save_meta( $post_id, $article, $en_content );
        if ( $image_id > 0 ) {
            set_post_thumbnail( $post_id, $image_id );
        }

        if ( 'publish' !== get_post_status( $post_id ) ) {
            throw new RuntimeException( 'Post not published: ' . $slug );
        }
        if ( ! has_category( (int) $blog_term->term_id, $post_id ) ) {
            throw new RuntimeException( 'Wagyu blog category missing: ' . $slug );
        }
        if ( '1' !== (string) get_post_meta( $post_id, '_en_US_published', true ) ) {
            throw new RuntimeException( 'English publication flag missing: ' . $slug );
        }
        if ( trim( (string) get_post_meta( $post_id, '_en_US_post_name', true ) ) !== trim( (string) $article['en_slug'] ) ) {
            throw new RuntimeException( 'English slug mismatch: ' . $slug );
        }
        if ( false === strpos( (string) get_post_field( 'post_content', $post_id ), '[products category="' . $related_term->slug . '"' ) ) {
            throw new RuntimeException( 'Related Carnes block missing: ' . $slug );
        }

        $rows[] = array(
            'n'              => $index + 1,
            'id'             => $post_id,
            'slug'           => $slug,
            'en_slug'        => (string) $article['en_slug'],
            'permalink'      => (string) get_permalink( $post_id ),
            'en_permalink'   => (string) home_url( '/en/' . trim( (string) $article['en_slug'], '/' ) . '/' ),
            'blog_category'  => $blog_term->slug,
            'related_product_category' => $related_term->slug,
        );
    }
} catch ( Throwable $e ) {
    foreach ( array_reverse( $created_ids ) as $created_id ) {
        wp_delete_post( (int) $created_id, true );
    }
    flush_rewrite_rules( false );
    wp_cache_flush();
    throw $e;
}

flush_rewrite_rules( false );
wp_cache_flush();

$out = array(
    'verified'                    => 65 === count( $rows ),
    'count'                       => count( $rows ),
    'blog_category_id'            => (int) $blog_term->term_id,
    'blog_category_slug'          => (string) $blog_term->slug,
    'shop_wagyu_category_id'      => (int) $shop_term->term_id,
    'shop_wagyu_category_slug'    => (string) $shop_term->slug,
    'related_product_category'    => (string) $related_term->slug,
    'featured_image_id'           => $image_id,
    'posts'                       => $rows,
);
echo 'EMDO_WAGYU_65_BEGIN' . PHP_EOL;
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
echo 'EMDO_WAGYU_65_END' . PHP_EOL;
if ( ! $out['verified'] ) {
    throw new RuntimeException( 'Wagyu 65 verification failed.' );
}
