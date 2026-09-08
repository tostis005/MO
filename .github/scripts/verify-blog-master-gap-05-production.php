<?php
/** Read-only verifier for EMDO master gap batch 05. */
if ( ! defined( 'ABSPATH' ) ) { exit( 2 ); }
const EMDO_GAP05_VERIFY_MARKER = '2026-09-08.master-gap-05';
const EMDO_GAP05_VERIFY_IMAGE = 13442;

function emdo_gap05_verify_words( string $html ): int {
    $plain = wp_strip_all_tags( strip_shortcodes( $html ) );
    preg_match_all( '/\p{L}[\p{L}\p{M}\'’\-]*/u', $plain, $m );
    return count( $m[0] );
}

$encoded = '';
for ( $part = 1; $part <= 4; $part++ ) {
    $path = __DIR__ . '/blog-master-gap-05-' . $part . '.b64';
    if ( ! is_readable( $path ) ) { throw new RuntimeException( 'Missing payload part ' . $part ); }
    $encoded .= trim( (string) file_get_contents( $path ) );
}
$compressed = base64_decode( $encoded, true );
if ( false === $compressed || hash( 'sha256', $compressed ) !== 'dbc5bf02234f06037bee506aab77c0e272de91a519dbd8b1c006c41ed2969b7f' ) {
    throw new RuntimeException( 'Compressed payload validation failed.' );
}
$json = gzdecode( $compressed );
if ( false === $json || hash( 'sha256', $json ) !== 'a401a869f6063541f6256b96b2c88733eda7320c9c447f8340d465324e48cc42' ) {
    throw new RuntimeException( 'Decoded payload validation failed.' );
}
$articles = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
$rows = array(); $errors = array();

foreach ( $articles as $article ) {
    $slug = (string) $article['slug'];
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) { $errors[] = 'Missing post: ' . $slug; continue; }
    $id = (int) $post->ID;
    $category = get_category_by_slug( (string) $article['category_slug'] );
    $en_title = (string) get_post_meta( $id, '_en_US_post_title', true );
    $en_slug = (string) get_post_meta( $id, '_en_US_post_name', true );
    $en_content = (string) get_post_meta( $id, '_en_US_post_content', true );
    $es_words = emdo_gap05_verify_words( (string) $post->post_content );
    $en_words = emdo_gap05_verify_words( $en_content );
    $product_needle = '[products category="' . (string) $article['product_cat_slug'] . '"';

    if ( 'publish' !== $post->post_status ) $errors[] = 'Not published: ' . $slug;
    if ( (string) $post->post_title !== (string) $article['title'] ) $errors[] = 'Spanish title mismatch: ' . $slug;
    if ( ! $category instanceof WP_Term || ! has_category( (int) $category->term_id, $id ) ) $errors[] = 'Category mismatch: ' . $slug;
    if ( EMDO_GAP05_VERIFY_IMAGE !== (int) get_post_thumbnail_id( $id ) ) $errors[] = 'Thumbnail mismatch: ' . $slug;
    if ( '1' !== (string) get_post_meta( $id, '_emdo_uses_default_featured', true ) ) $errors[] = 'Default image flag missing: ' . $slug;
    if ( EMDO_GAP05_VERIFY_MARKER !== (string) get_post_meta( $id, '_emdo_master_gap_05', true ) ) $errors[] = 'Batch marker mismatch: ' . $slug;
    if ( (string) (int) $article['pos'] !== (string) get_post_meta( $id, '_emdo_editorial_position', true ) ) $errors[] = 'Position mismatch: ' . $slug;
    if ( $en_title !== (string) $article['en_title'] ) $errors[] = 'English title mismatch: ' . $slug;
    if ( $en_slug !== (string) $article['en_slug'] ) $errors[] = 'English slug mismatch: ' . $slug;
    if ( '1' !== (string) get_post_meta( $id, '_en_US_published', true ) ) $errors[] = 'English publish flag missing: ' . $slug;
    if ( '1' !== (string) get_post_meta( $id, '_en_US_ready', true ) ) $errors[] = 'English ready flag missing: ' . $slug;
    if ( $es_words < 850 || $en_words < 750 ) $errors[] = 'Word count too low: ' . $slug . ' ES=' . $es_words . ' EN=' . $en_words;
    if ( false === strpos( (string) $post->post_content, $product_needle ) ) $errors[] = 'Spanish related products mismatch: ' . $slug;
    if ( false === strpos( $en_content, $product_needle ) ) $errors[] = 'English related products mismatch: ' . $slug;
    if ( '' === trim( (string) get_post_meta( $id, 'rank_math_title', true ) ) ) $errors[] = 'SEO title missing: ' . $slug;
    if ( '' === trim( (string) get_post_meta( $id, 'rank_math_description', true ) ) ) $errors[] = 'SEO description missing: ' . $slug;

    $rows[] = array(
        'position' => (int) $article['pos'], 'id' => $id, 'slug' => $slug, 'en_slug' => $en_slug,
        'title' => (string) $post->post_title, 'en_title' => $en_title,
        'category_slug' => (string) $article['category_slug'], 'product_cat' => (string) $article['product_cat_slug'],
        'es_words' => $es_words, 'en_words' => $en_words, 'thumbnail_id' => (int) get_post_thumbnail_id( $id ),
        'permalink' => (string) get_permalink( $id ),
        'en_permalink' => (string) home_url( '/en/' . trim( $en_slug, '/' ) . '/' ),
    );
}

usort( $rows, static fn( $a, $b ) => $a['position'] <=> $b['position'] );
$result = array(
    'verified' => empty( $errors ) && 10 === count( $rows ),
    'count' => count( $rows),
    'default_featured_image_id' => EMDO_GAP05_VERIFY_IMAGE,
    'posts' => $rows,
    'errors' => $errors,
);
$out = wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
file_put_contents( '/tmp/emdo-gap05-verify.json', $out . "\n" );
echo $out . "\n";
if ( ! $result['verified'] ) { throw new RuntimeException( 'Batch-05 verification failed: ' . implode( ' | ', $errors ) ); }
