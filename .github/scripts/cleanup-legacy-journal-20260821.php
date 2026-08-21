<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$targets = array(
    array( 'slug' => 'jamon-iberico', 'title_hint' => 'Jamón Ibérico' ),
    array( 'slug' => 'aceite-de-oliva-virgen-extra', 'title_hint' => 'Aceite de Oliva Virgen Extra' ),
);

$clean_title = static function ( string $value ): string {
    $value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = wp_strip_all_tags( $value, true );
    $value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = wp_strip_all_tags( $value, true );
    return trim( preg_replace( '/\s+/u', ' ', $value ) ?? $value );
};

$promo_re = '(?:cup[oó]n|coupon|c[oó]digo\s+(?:de\s+)?(?:descuento|promocional)|discount\s+code|promo(?:tional)?\s+code|(?:usa|usar|utiliza|utilizar|introduce)\s+(?:el\s+)?c[oó]digo|(?:use|enter|apply)\s+(?:the\s+)?(?:code|coupon))';

$clean_promo = static function ( string $html ) use ( $promo_re ): string {
    if ( '' === $html ) { return $html; }

    $html = preg_replace(
        '#<(p|li|blockquote|h[1-6])\b[^>]*>(?:(?!</\1>)[\s\S])*?' . $promo_re . '(?:(?!</\1>)[\s\S])*?</\1>\s*#iu',
        '',
        $html
    ) ?? $html;

    $html = preg_replace(
        '#(?:(?<=^)|(?<=[>\.!?]))[^<>\.!?]{0,260}' . $promo_re . '[^<>\.!?]{0,260}[\.!?]#iu',
        '',
        $html
    ) ?? $html;

    $html = preg_replace( '#<p\b[^>]*>\s*(?:&nbsp;|&#160;|<br\s*/?>|\s)*</p>#iu', '', $html ) ?? $html;
    return trim( $html );
};

$find_post = static function ( string $slug, string $hint ): ?WP_Post {
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    if ( $post instanceof WP_Post ) { return $post; }
    $ids = get_posts( array(
        'post_type' => 'post',
        'post_status' => array( 'publish', 'draft', 'private' ),
        's' => $hint,
        'posts_per_page' => 10,
        'fields' => 'ids',
        'suppress_filters' => true,
    ) );
    foreach ( $ids as $id ) {
        $candidate = get_post( (int) $id );
        $plain = $candidate instanceof WP_Post
            ? wp_strip_all_tags( html_entity_decode( $candidate->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) )
            : '';
        if ( $candidate instanceof WP_Post && false !== stripos( $plain, $hint ) ) { return $candidate; }
    }
    return null;
};

$report = array( 'posts' => array(), 'errors' => array() );

foreach ( $targets as $target ) {
    $post = $find_post( $target['slug'], $target['title_hint'] );
    if ( ! $post instanceof WP_Post ) {
        $report['errors'][] = 'Post not found: ' . $target['slug'];
        continue;
    }

    $id = (int) $post->ID;
    $old_title = (string) $post->post_title;
    $new_title = $clean_title( $old_title );
    $old_content = (string) $post->post_content;
    $new_content = $clean_promo( $old_content );

    $update = array( 'ID' => $id );
    if ( '' !== $new_title && $new_title !== $old_title ) { $update['post_title'] = $new_title; }
    if ( $new_content !== $old_content ) { $update['post_content'] = $new_content; }
    if ( count( $update ) > 1 ) {
        $result = wp_update_post( wp_slash( $update ), true );
        if ( is_wp_error( $result ) ) {
            $report['errors'][] = $target['slug'] . ': ' . $result->get_error_message();
            continue;
        }
    }

    $old_en_title = (string) get_post_meta( $id, '_en_US_post_title', true );
    $new_en_title = $clean_title( $old_en_title );
    if ( '' !== $new_en_title && $new_en_title !== $old_en_title ) {
        update_post_meta( $id, '_en_US_post_title', $new_en_title );
    }

    $old_en_content = (string) get_post_meta( $id, '_en_US_post_content', true );
    $new_en_content = $clean_promo( $old_en_content );
    if ( $new_en_content !== $old_en_content ) {
        update_post_meta( $id, '_en_US_post_content', $new_en_content );
    }

    $old_excerpt = (string) $post->post_excerpt;
    $new_excerpt = $clean_promo( $old_excerpt );
    if ( $new_excerpt !== $old_excerpt ) {
        wp_update_post( wp_slash( array( 'ID' => $id, 'post_excerpt' => $new_excerpt ) ) );
    }
    $old_en_excerpt = (string) get_post_meta( $id, '_en_US_post_excerpt', true );
    $new_en_excerpt = $clean_promo( $old_en_excerpt );
    if ( $new_en_excerpt !== $old_en_excerpt ) {
        update_post_meta( $id, '_en_US_post_excerpt', $new_en_excerpt );
    }

    $final_es = (string) get_post_field( 'post_content', $id );
    $final_en = (string) get_post_meta( $id, '_en_US_post_content', true );
    $final_title = (string) get_post_field( 'post_title', $id );
    $final_en_title = (string) get_post_meta( $id, '_en_US_post_title', true );

    $report['posts'][] = array(
        'id' => $id,
        'slug' => (string) get_post_field( 'post_name', $id ),
        'en_slug' => sanitize_title( (string) get_post_meta( $id, '_en_US_post_name', true ) ),
        'title' => $final_title,
        'en_title' => $final_en_title,
        'title_has_markup' => preg_match( '/<[^>]+>|&lt;[^&]+&gt;/i', $final_title ) ? 1 : 0,
        'en_title_has_markup' => preg_match( '/<[^>]+>|&lt;[^&]+&gt;/i', $final_en_title ) ? 1 : 0,
        'es_promo_mentions' => preg_match_all( '/' . $promo_re . '/iu', wp_strip_all_tags( html_entity_decode( $final_es, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ),
        'en_promo_mentions' => preg_match_all( '/' . $promo_re . '/iu', wp_strip_all_tags( html_entity_decode( $final_en, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ),
    );
}

wp_cache_flush();

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";

if ( $report['errors'] ) { exit( 2 ); }
foreach ( $report['posts'] as $row ) {
    if ( $row['title_has_markup'] || $row['en_title_has_markup'] || $row['es_promo_mentions'] || $row['en_promo_mentions'] ) { exit( 3 ); }
}
