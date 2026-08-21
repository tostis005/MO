<?php
/** Production data audit for editorial authority batch 1. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Re-audit after the explicit production repair run. */
$keys = array(
    'iberico-seals',
    'ham-storage',
    'cured-meats-guide',
    'oil-varieties',
    'what-evoo-means',
);

function emdo_ab1_audit_words( string $html ): int {
    $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
    if ( '' === $plain ) { return 0; }
    preg_match_all( "/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u", $plain, $m );
    return count( $m[0] );
}

$out = array( 'generated_at' => gmdate( 'c' ), 'posts' => array() );
global $wpdb;
foreach ( $keys as $key ) {
    $id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_emdo_authority_key' AND meta_value=%s ORDER BY post_id ASC LIMIT 1",
        $key
    ) );
    if ( ! $id ) {
        $out['posts'][] = array( 'key' => $key, 'exists' => false );
        continue;
    }
    $post = get_post( $id );
    $image_id = (int) get_post_thumbnail_id( $id );
    $meta = $image_id ? wp_get_attachment_metadata( $image_id ) : array();
    $en_content = (string) get_post_meta( $id, '_en_US_post_content', true );
    $out['posts'][] = array(
        'key' => $key,
        'exists' => $post instanceof WP_Post,
        'id' => $id,
        'status' => get_post_status( $id ),
        'title' => get_the_title( $id ),
        'slug' => (string) get_post_field( 'post_name', $id ),
        'en_title' => (string) get_post_meta( $id, '_en_US_post_title', true ),
        'en_slug' => (string) get_post_meta( $id, '_en_US_post_name', true ),
        'en_published' => (string) get_post_meta( $id, '_en_US_published', true ),
        'words_es' => emdo_ab1_audit_words( (string) get_post_field( 'post_content', $id ) ),
        'words_en' => emdo_ab1_audit_words( $en_content ),
        'has_product_shortcode_es' => str_contains( (string) get_post_field( 'post_content', $id ), '[products ' ),
        'has_product_shortcode_en' => str_contains( $en_content, '[products ' ),
        'image_id' => $image_id,
        'image_w' => (int) ( $meta['width'] ?? 0 ),
        'image_h' => (int) ( $meta['height'] ?? 0 ),
        'image_source' => $image_id ? (string) get_post_meta( $image_id, '_emdo_pexels_page', true ) : '',
        'image_license' => $image_id ? (string) get_post_meta( $image_id, '_emdo_image_license', true ) : '',
    );
}
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
