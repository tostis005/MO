<?php
/** Read-only certification of published editorial imagery in production. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$authority_file = (string) getenv( 'MDO_AUTHORITY_IMAGE_OVERRIDES' );
$legacy_file    = (string) getenv( 'MDO_LEGACY_IMAGE_OVERRIDES' );
if ( '' === $authority_file || ! is_file( $authority_file ) ) { throw new RuntimeException( 'Authority overrides missing.' ); }
if ( '' === $legacy_file || ! is_file( $legacy_file ) ) { throw new RuntimeException( 'Legacy overrides missing.' ); }
$authority = require $authority_file;
$legacy    = require $legacy_file;
if ( ! is_array( $authority ) || ! is_array( $legacy ) ) { throw new RuntimeException( 'Invalid override files.' ); }

$forbidden_pexels = array_values( array_unique( array_map( 'strval', array(
    37328201, // explicitly Serrano source used by ham-starting guide.
    19370451, // "Bacon on Tray" used by sliced Iberian ham guide.
    4441933,  // generic cured ham previously used for Iberian portions.
    36601487, 37328770, 24706530, 34314216, 36215958, // market/display ham imagery retired from Iberian guides.
    24906286, 30795237, 30795238, 947174, 14639925, 6059884, 25185476, 30811288, // legacy market/butcher ham pool retired for brand safety.
    36183155, // bottle image retired from olive-oil-label guide.
) ) ) );
$forbidden_lookup = array_fill_keys( $forbidden_pexels, true );

function mdo_cert_attachment_row_20260822( int $attachment_id ): array {
    if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
        return array( 'attachment_id'=>0, 'pexels_id'=>'', 'source_page'=>'', 'url'=>'', 'md5'=>'' );
    }
    $file = get_attached_file( $attachment_id );
    $md5  = is_string( $file ) && is_file( $file ) ? (string) md5_file( $file ) : '';
    return array(
        'attachment_id' => $attachment_id,
        'pexels_id'     => (string) get_post_meta( $attachment_id, '_emdo_pexels_photo_id', true ),
        'source_page'   => (string) get_post_meta( $attachment_id, '_emdo_pexels_page', true ),
        'url'           => (string) wp_get_attachment_url( $attachment_id ),
        'md5'           => $md5,
    );
}

function mdo_cert_inline_rows_20260822( int $post_id ): array {
    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === $content ) { return array(); }
    preg_match_all( '/<img\b[^>]*>/iu', $content, $tags );
    $rows = array();
    foreach ( $tags[0] ?? array() as $tag ) {
        $attachment_id = 0;
        if ( preg_match( '/\bwp-image-([0-9]+)\b/i', $tag, $m ) ) {
            $attachment_id = (int) $m[1];
        }
        if ( $attachment_id <= 0 && preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $m ) ) {
            $src = html_entity_decode( (string) $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $attachment_id = (int) attachment_url_to_postid( $src );
        }
        $row = mdo_cert_attachment_row_20260822( $attachment_id );
        if ( $attachment_id <= 0 && preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $m ) ) {
            $row['url'] = html_entity_decode( (string) $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }
        $rows[] = $row;
    }
    return $rows;
}

function mdo_cert_find_authority_post_20260822( string $key ): int {
    $ids = get_posts( array(
        'post_type'=>'post', 'post_status'=>'publish', 'posts_per_page'=>2, 'fields'=>'ids',
        'meta_key'=>'_emdo_authority_key', 'meta_value'=>$key,
    ) );
    if ( count( $ids ) !== 1 ) { throw new RuntimeException( 'Authority key must resolve to exactly one published post: ' . $key ); }
    return (int) $ids[0];
}

$report = array(
    'status'              => 'checking',
    'certified_at_utc'    => gmdate( 'c' ),
    'site_url'            => site_url(),
    'authority_count'     => 0,
    'published_post_count'=> 0,
    'expected'            => array(),
    'legacy'              => array(),
    'all_images'          => array(),
    'duplicates_by_pexels'=> array(),
    'duplicates_by_md5'   => array(),
    'forbidden_hits'      => array(),
    'semantic_flags'      => array(),
    'public_checks'       => array(),
);

// Every curated authority override must be live on the exact published post.
foreach ( $authority as $key => $img ) {
    $post_id = mdo_cert_find_authority_post_20260822( (string) $key );
    $featured = mdo_cert_attachment_row_20260822( (int) get_post_thumbnail_id( $post_id ) );
    $expected = (string) ( $img['id'] ?? '' );
    if ( '' === $expected || $featured['pexels_id'] !== $expected ) {
        throw new RuntimeException( 'Authority image mismatch for ' . $key . ': expected ' . $expected . ', got ' . $featured['pexels_id'] );
    }
    $report['expected'][] = array(
        'type'=>'authority', 'key'=>(string)$key, 'post_id'=>$post_id,
        'url'=>(string)get_permalink( $post_id ), 'expected_pexels_id'=>$expected,
        'actual'=>$featured,
    );
    $report['public_checks'][] = array( 'url'=>(string)get_permalink( $post_id ), 'expected_pexels_id'=>$expected, 'context'=>'authority:' . $key );
}

// Refined guides are older posts without authority keys but now have locked approved images.
$refined = array(
    'jamon-o-paleta-diferencias-cual-elegir'       => '28913503',
    'jamon-pieza-entera-o-loncheado-como-elegir'  => '34100077',
);
foreach ( $refined as $slug => $expected ) {
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { throw new RuntimeException( 'Refined guide missing: ' . $slug ); }
    $featured = mdo_cert_attachment_row_20260822( (int) get_post_thumbnail_id( $post->ID ) );
    if ( $featured['pexels_id'] !== $expected ) { throw new RuntimeException( 'Refined guide image mismatch: ' . $slug ); }
    $report['legacy'][] = array( 'slug'=>$slug, 'post_id'=>(int)$post->ID, 'featured'=>$featured, 'inline'=>mdo_cert_inline_rows_20260822( (int)$post->ID ) );
    $report['public_checks'][] = array( 'url'=>(string)get_permalink( $post->ID ), 'expected_pexels_id'=>$expected, 'context'=>'refined:' . $slug );
}

// Two historic editorial posts use fully curated featured + inline pools.
foreach ( $legacy as $slug => $config ) {
    $post = get_page_by_path( (string) $slug, OBJECT, 'post' );
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { throw new RuntimeException( 'Historic post missing: ' . $slug ); }
    $featured = mdo_cert_attachment_row_20260822( (int) get_post_thumbnail_id( $post->ID ) );
    $expected_featured = (string) ( $config['featured']['id'] ?? '' );
    if ( $featured['pexels_id'] !== $expected_featured ) { throw new RuntimeException( 'Historic featured mismatch: ' . $slug ); }
    $inline = mdo_cert_inline_rows_20260822( (int) $post->ID );
    $allowed_inline = array_map( static fn( $img ) => (string) ( $img['id'] ?? '' ), (array) ( $config['inline'] ?? array() ) );
    foreach ( $inline as $index => $row ) {
        if ( '' === $row['pexels_id'] || ! in_array( $row['pexels_id'], $allowed_inline, true ) ) {
            throw new RuntimeException( 'Historic inline image outside approved pool: ' . $slug . ':' . $index . ':' . $row['pexels_id'] );
        }
    }
    $report['legacy'][] = array( 'slug'=>(string)$slug, 'post_id'=>(int)$post->ID, 'featured'=>$featured, 'inline'=>$inline );
    $report['public_checks'][] = array( 'url'=>(string)get_permalink( $post->ID ), 'expected_pexels_id'=>$expected_featured, 'context'=>'historic:' . $slug );
}

// Global published-post audit: featureds and inline images, including exact-file hashes.
$post_ids = get_posts( array( 'post_type'=>'post', 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'orderby'=>'ID', 'order'=>'ASC' ) );
$report['published_post_count'] = count( $post_ids );
$authority_ids = get_posts( array( 'post_type'=>'post', 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'meta_key'=>'_emdo_authority_key' ) );
$report['authority_count'] = count( $authority_ids );
if ( $report['authority_count'] < 50 ) { throw new RuntimeException( 'Expected at least 50 published authority posts, found ' . $report['authority_count'] ); }

$by_pexels = array();
$by_md5    = array();
foreach ( $post_ids as $post_id ) {
    $post_id = (int) $post_id;
    $context_base = 'post:' . $post_id . ':' . get_post_field( 'post_name', $post_id );
    $images = array();
    $featured = mdo_cert_attachment_row_20260822( (int) get_post_thumbnail_id( $post_id ) );
    if ( $featured['attachment_id'] > 0 ) { $images[] = array( 'role'=>'featured', 'row'=>$featured ); }
    foreach ( mdo_cert_inline_rows_20260822( $post_id ) as $i => $row ) { $images[] = array( 'role'=>'inline:' . $i, 'row'=>$row ); }

    foreach ( $images as $image ) {
        $row = $image['row'];
        $context = $context_base . ':' . $image['role'];
        $report['all_images'][] = array( 'context'=>$context ) + $row;
        if ( '' !== $row['pexels_id'] ) {
            $by_pexels[ $row['pexels_id'] ][] = $context;
            if ( isset( $forbidden_lookup[ $row['pexels_id'] ] ) ) { $report['forbidden_hits'][ $row['pexels_id'] ][] = $context; }
        }
        if ( '' !== $row['md5'] ) { $by_md5[ $row['md5'] ][] = $context; }
    }

    $key = (string) get_post_meta( $post_id, '_emdo_authority_key', true );
    if ( '' !== $key && $featured['attachment_id'] > 0 ) {
        $haystack = strtolower( $featured['source_page'] . ' ' . (string)get_the_title( $featured['attachment_id'] ) . ' ' . (string)get_post_meta( $featured['attachment_id'], '_wp_attachment_image_alt', true ) );
        if ( preg_match( '/(ham|iberian|bellota|montanera)/i', $key ) && preg_match( '/\b(serrano|bacon|prosciutto)\b/i', $haystack ) ) {
            $report['semantic_flags'][] = array( 'post_id'=>$post_id, 'key'=>$key, 'reason'=>'non-Iberian cured-meat term in image metadata', 'image'=>$featured );
        }
    }
}

foreach ( $by_pexels as $id => $contexts ) { if ( count( $contexts ) > 1 ) { $report['duplicates_by_pexels'][ $id ] = $contexts; } }
foreach ( $by_md5 as $hash => $contexts ) { if ( count( $contexts ) > 1 ) { $report['duplicates_by_md5'][ $hash ] = $contexts; } }

if ( ! empty( $report['duplicates_by_pexels'] ) ) { throw new RuntimeException( 'Repeated Pexels IDs remain: ' . wp_json_encode( $report['duplicates_by_pexels'] ) ); }
if ( ! empty( $report['duplicates_by_md5'] ) ) { throw new RuntimeException( 'Byte-identical repeated images remain: ' . wp_json_encode( $report['duplicates_by_md5'] ) ); }
if ( ! empty( $report['forbidden_hits'] ) ) { throw new RuntimeException( 'Retired/forbidden editorial images remain: ' . wp_json_encode( $report['forbidden_hits'] ) ); }
if ( ! empty( $report['semantic_flags'] ) ) { throw new RuntimeException( 'Semantic image flags remain: ' . wp_json_encode( $report['semantic_flags'] ) ); }

$report['status'] = 'ok';
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;

// Trigger marker: workflow already exists on main before this push.
