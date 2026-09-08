<?php
/** Roll back only posts created by EMDO master gap batch 05. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
const EMDO_MASTER_GAP_05_ROLLBACK_MARKER = '2026-09-08.master-gap-05';
$encoded = '';
for ( $part = 1; $part <= 4; $part++ ) {
    $path = __DIR__ . '/blog-master-gap-05-' . $part . '.b64';
    if ( ! is_readable( $path ) ) { continue; }
    $encoded .= trim( (string) file_get_contents( $path ) );
}
$compressed = base64_decode( $encoded, true );
$json = false === $compressed ? false : gzdecode( $compressed );
$articles = false === $json ? array() : json_decode( $json, true );
$deleted = array();
if ( is_array( $articles ) ) {
    foreach ( $articles as $article ) {
        $slug = isset( $article['slug'] ) ? (string) $article['slug'] : '';
        if ( '' === $slug ) { continue; }
        $post = get_page_by_path( $slug, OBJECT, 'post' );
        if ( ! $post instanceof WP_Post ) { continue; }
        $marker = (string) get_post_meta( $post->ID, '_emdo_master_gap_05', true );
        if ( EMDO_MASTER_GAP_05_ROLLBACK_MARKER !== $marker ) { continue; }
        if ( wp_delete_post( $post->ID, true ) ) { $deleted[] = (int) $post->ID; }
    }
}
flush_rewrite_rules( false );
wp_cache_flush();
echo wp_json_encode( array( 'deleted' => $deleted, 'count' => count( $deleted ) ) ) . "\n";
