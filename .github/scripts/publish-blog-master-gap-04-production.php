<?php
/**
 * Publish and verify EMDO master-list gap batch 04 (10 bilingual articles).
 * Spanish WordPress posts + Falang en_US metadata, related WooCommerce products,
 * and established generic featured image 13442 for every post.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

const EMDO_MASTER_GAP_04_MARKER = '2026-09-07.master-gap-04.v1';
const EMDO_BLOG_DEFAULT_FEATURED_IMAGE_ID = 13442;

function emdo_gap04_load_articles(): array {
    $encoded = '';
    for ( $part = 1; $part <= 4; $part++ ) {
        $path = __DIR__ . '/blog-master-gap-04-' . $part . '.b64';
        if ( ! is_readable( $path ) ) { throw new RuntimeException( 'Missing editorial payload part ' . $part ); }
        $encoded .= trim( (string) file_get_contents( $path ) );
    }
    $compressed = base64_decode( $encoded, true );
    if ( false === $compressed ) { throw new RuntimeException( 'Editorial payload is not valid Base64.' ); }
    $json = gzdecode( $compressed );
    if ( false === $json ) { throw new RuntimeException( 'Editorial payload cannot be decompressed.' ); }
    $articles = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
    if ( ! is_array( $articles ) || 10 !== count( $articles ) ) { throw new RuntimeException( 'Expected exactly 10 articles.' ); }

    $required = array(
        'pos','title','slug','excerpt','content','seo_title','seo_description','focus_keyword',
        'en_title','en_slug','en_excerpt','en_content','en_seo_title','en_seo_description','en_focus_keyword',
        'category_slug','category_name','product_cat_slug','product_cat_name','related_heading','en_related_heading'
    );
    $expected = array(1,3,6,7,8,14,25,26,31,32);
    $positions = array(); $es_slugs = array(); $en_slugs = array();
    foreach ( $articles as $article ) {
        foreach ( $required as $key ) {
            if ( ! array_key_exists( $key, $article ) || '' === trim( (string) $article[$key] ) ) {
                throw new RuntimeException( 'Missing editorial field ' . $key . ' in ' . ( $article['slug'] ?? 'unknown' ) );
            }
        }
        $slug = (string) $article['slug']; $en_slug = (string) $article['en_slug'];
        if ( isset( $es_slugs[$slug] ) || isset( $en_slugs[$en_slug] ) ) { throw new RuntimeException( 'Duplicate slug in payload.' ); }
        $es_slugs[$slug] = 1; $en_slugs[$en_slug] = 1; $positions[] = (int) $article['pos'];
    }
    sort( $positions );
    if ( $positions !== $expected ) { throw new RuntimeException( 'Editorial positions do not match master-list gaps.' ); }
    return $articles;
}

function emdo_gap04_category( array $article ): WP_Term {
    $term = get_category_by_slug( (string) $article['category_slug'] );
    if ( ! $term instanceof WP_Term ) { $term = get_term_by( 'name', (string) $article['category_name'], 'category' ); }
    if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'Blog category not found: ' . $article['category_slug'] ); }
    return $term;
}
function emdo_gap04_product_category( array $article ): WP_Term {
    $term = get_term_by( 'slug', (string) $article['product_cat_slug'], 'product_cat' );
    if ( ! $term instanceof WP_Term ) { $term = get_term_by( 'name', (string) $article['product_cat_name'], 'product_cat' ); }
    if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'Product category not found: ' . $article['product_cat_slug'] ); }
    return $term;
}
function emdo_gap04_author_for_category( int $category_id ): int {
    $sample = get_posts( array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','category__in'=>array($category_id)) );
    $author = $sample ? (int) get_post_field( 'post_author', (int) $sample[0] ) : 1;
    return $author > 0 ? $author : 1;
}
function emdo_gap04_render_content( array $article, WP_Term $product_cat, bool $english ): string {
    $content = $english ? (string) $article['en_content'] : (string) $article['content'];
    $heading = $english ? (string) $article['en_related_heading'] : (string) $article['related_heading'];
    $block = '<h2>' . esc_html( $heading ) . '</h2>' . "\n"
        . '[products category="' . esc_attr( $product_cat->slug ) . '" limit="4" columns="4" orderby="date" order="DESC"]';
    $rendered = str_replace( '<!-- EMDO_RELATED_PRODUCTS -->', $block, $content );
    if ( false !== strpos( $rendered, 'EMDO_RELATED_PRODUCTS' ) ) { throw new RuntimeException( 'Related-products placeholder was not rendered.' ); }
    return $rendered;
}
function emdo_gap04_trim( string $text, int $max ): string {
    if ( function_exists( 'mb_strlen' ) && mb_strlen( $text, 'UTF-8' ) > $max ) {
        return rtrim( mb_substr( $text, 0, $max - 1, 'UTF-8' ) ) . '…';
    }
    return $text;
}
function emdo_gap04_word_count( string $html ): int {
    $plain = wp_strip_all_tags( strip_shortcodes( $html ) );
    preg_match_all( '/\p{L}[\p{L}\p{M}\'’\-]*/u', $plain, $matches );
    return count( $matches[0] );
}

$articles = emdo_gap04_load_articles();
$ids = array(); $context = array(); $rows = array(); $errors = array();

foreach ( $articles as $article ) {
    $existing = get_page_by_path( (string) $article['slug'], OBJECT, 'post' );
    if ( $existing instanceof WP_Post ) { throw new RuntimeException( 'Safety stop: target slug already exists: ' . $article['slug'] ); }
}

foreach ( $articles as $article ) {
    $category = emdo_gap04_category( $article );
    $product_cat = emdo_gap04_product_category( $article );
    $author = emdo_gap04_author_for_category( (int) $category->term_id );
    $result = wp_insert_post( wp_slash( array(
        'post_type'=>'post','post_status'=>'draft','post_title'=>(string)$article['title'],
        'post_name'=>(string)$article['slug'],'post_excerpt'=>(string)$article['excerpt'],
        'post_author'=>$author,'post_category'=>array((int)$category->term_id),
        'comment_status'=>'closed','ping_status'=>'closed',
    ) ), true );
    if ( is_wp_error( $result ) || (int)$result <= 0 ) { throw new RuntimeException( 'Could not reserve: ' . $article['slug'] ); }
    $id = (int)$result;
    update_post_meta( $id, '_emdo_master_gap_04', EMDO_MASTER_GAP_04_MARKER );
    update_post_meta( $id, '_emdo_editorial_position', (string)(int)$article['pos'] );
    $ids[(string)$article['slug']] = $id;
    $context[(string)$article['slug']] = array('category'=>$category,'product_cat'=>$product_cat);
}

$default_image_id = EMDO_BLOG_DEFAULT_FEATURED_IMAGE_ID;
if ( 'attachment' !== get_post_type( $default_image_id ) || ! wp_attachment_is_image( $default_image_id ) ) {
    throw new RuntimeException( 'Established default featured image is unavailable: ' . $default_image_id );
}

foreach ( $articles as $article ) {
    $slug = (string)$article['slug']; $id = $ids[$slug];
    $category = $context[$slug]['category']; $product_cat = $context[$slug]['product_cat'];
    $spanish = emdo_gap04_render_content( $article, $product_cat, false );
    $english = emdo_gap04_render_content( $article, $product_cat, true );
    $es_words = emdo_gap04_word_count( $spanish ); $en_words = emdo_gap04_word_count( $english );
    if ( $es_words < 850 || $en_words < 750 ) { throw new RuntimeException( 'Article too short: ' . $slug . ' ES=' . $es_words . ' EN=' . $en_words ); }

    $updated = wp_update_post( wp_slash( array(
        'ID'=>$id,'post_title'=>(string)$article['title'],'post_name'=>$slug,
        'post_excerpt'=>(string)$article['excerpt'],'post_content'=>$spanish,
        'post_category'=>array((int)$category->term_id),'comment_status'=>'closed','ping_status'=>'closed'
    ) ), true );
    if ( is_wp_error( $updated ) ) { throw new RuntimeException( 'Spanish render failed: ' . $slug ); }

    update_post_meta( $id, '_en_US_post_title', (string)$article['en_title'] );
    update_post_meta( $id, '_en_US_post_name', (string)$article['en_slug'] );
    update_post_meta( $id, '_en_US_post_excerpt', (string)$article['en_excerpt'] );
    update_post_meta( $id, '_en_US_post_content', $english );
    update_post_meta( $id, '_en_US_ready', '1' );
    update_post_meta( $id, '_en_US_published', '1' );

    update_post_meta( $id, '_emdo_seo_title', emdo_gap04_trim((string)$article['seo_title'],62) );
    update_post_meta( $id, '_emdo_seo_description', emdo_gap04_trim((string)$article['seo_description'],158) );
    update_post_meta( $id, '_en_US_seo_title', emdo_gap04_trim((string)$article['en_seo_title'],62) );
    update_post_meta( $id, '_en_US_seo_description', emdo_gap04_trim((string)$article['en_seo_description'],158) );
    update_post_meta( $id, 'rank_math_title', emdo_gap04_trim((string)$article['seo_title'],62) );
    update_post_meta( $id, 'rank_math_description', emdo_gap04_trim((string)$article['seo_description'],158) );
    update_post_meta( $id, 'rank_math_focus_keyword', (string)$article['focus_keyword'] );

    if ( ! set_post_thumbnail( $id, $default_image_id ) ) { throw new RuntimeException( 'Default featured image could not be assigned: ' . $slug ); }
    update_post_meta( $id, '_emdo_uses_default_featured', '1' );
    delete_post_meta( $id, '_emdo_featured_image_source' );
    delete_post_meta( $id, '_emdo_featured_image_query' );

    if ( trim((string)get_post_meta($id,'_en_US_post_title',true)) !== trim((string)$article['en_title']) ) $errors[]='English title mismatch: '.$slug;
    if ( trim((string)get_post_meta($id,'_en_US_post_name',true)) !== trim((string)$article['en_slug']) ) $errors[]='English slug mismatch: '.$slug;
    if ( '1' !== (string)get_post_meta($id,'_en_US_published',true) ) $errors[]='English publication flag missing: '.$slug;
    if ( ! has_category((int)$category->term_id,$id) ) $errors[]='Category missing: '.$slug;
    if ( '1' !== (string)get_post_meta($id,'_emdo_uses_default_featured',true) ) $errors[]='Default featured marker missing: '.$slug;
    if ( $default_image_id !== (int)get_post_thumbnail_id($id) ) $errors[]='Default featured image mismatch: '.$slug;

    $rows[$slug]=array(
        'position'=>(int)$article['pos'],'id'=>$id,'slug'=>$slug,'en_slug'=>(string)$article['en_slug'],
        'title'=>(string)$article['title'],'en_title'=>(string)$article['en_title'],
        'category_slug'=>(string)$article['category_slug'],'product_cat'=>(string)$product_cat->slug,
        'es_words'=>$es_words,'en_words'=>$en_words,'thumbnail_id'=>(int)get_post_thumbnail_id($id)
    );
}
if ( $errors ) { throw new RuntimeException( 'Draft validation failed: ' . implode(' | ',$errors) ); }

foreach ( $articles as $article ) {
    $slug=(string)$article['slug']; $id=$ids[$slug];
    $r=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
    if ( is_wp_error($r) || 'publish' !== get_post_status($id) ) { throw new RuntimeException( 'Publish failed: '.$slug ); }
    clean_post_cache($id);
    $rows[$slug]['status']='publish';
    $rows[$slug]['permalink']=(string)get_permalink($id);
    $rows[$slug]['en_permalink']=(string)home_url('/en/'.trim((string)$article['en_slug'],'/').'/');
}
flush_rewrite_rules(false); wp_cache_flush();
$out=array(
    'marker'=>EMDO_MASTER_GAP_04_MARKER,'verified'=>true,'count'=>count($rows),
    'default_featured_image_id'=>$default_image_id,'posts'=>array_values($rows),'errors'=>array()
);
echo "EMDO_MASTER_GAP_04_BEGIN\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_MASTER_GAP_04_END\n";
