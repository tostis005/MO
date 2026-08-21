<?php
/**
 * Plugin Name: MDO English Journal Content 20260821
 * Description: Renders reviewed English post copy and blog-category labels from persisted _en_US_* metadata on /en/ routes.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Editorial authority batch 1 deployment trigger: 2026-08-21. */

function mdoej_en_20260821(): bool {
    if ( function_exists( 'mdoer_en' ) ) { return mdoer_en(); }
    if ( function_exists( 'mdo_en_is_request' ) ) { return mdo_en_is_request(); }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return $path === '/en' || str_starts_with( $path, '/en/' );
}

function mdoej_post_value_20260821( int $post_id, string $key, string $fallback ): string {
    if ( ! mdoej_en_20260821() || 'post' !== get_post_type( $post_id ) ) { return $fallback; }
    if ( '1' !== (string) get_post_meta( $post_id, '_en_US_published', true ) ) { return $fallback; }
    $value = (string) get_post_meta( $post_id, $key, true );
    return '' !== trim( wp_strip_all_tags( $value ) ) ? $value : $fallback;
}

add_filter( 'the_title', static function ( string $title, int $post_id ): string {
    return mdoej_post_value_20260821( $post_id, '_en_US_post_title', $title );
}, PHP_INT_MAX, 2 );

/*
 * Replace the native post body before WordPress runs wpautop/shortcode_unautop
 * and do_shortcode. At PHP_INT_MAX the English metadata used to replace an
 * already-rendered Spanish body with raw shortcode text, so related products
 * appeared as [products ...] instead of WooCommerce cards on /en/ articles.
 */
add_filter( 'the_content', static function ( string $content ): string {
    $post_id = (int) get_the_ID();
    return $post_id > 0 ? mdoej_post_value_20260821( $post_id, '_en_US_post_content', $content ) : $content;
}, 8 );

add_filter( 'get_the_excerpt', static function ( $excerpt, $post ) {
    $post_id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
    return $post_id > 0 ? mdoej_post_value_20260821( $post_id, '_en_US_post_excerpt', (string) $excerpt ) : $excerpt;
}, PHP_INT_MAX, 2 );

function mdoej_translate_category_20260821( WP_Term $term ): WP_Term {
    if ( ! mdoej_en_20260821() || 'category' !== $term->taxonomy ) { return $term; }
    if ( '1' !== (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) { return $term; }
    $name = trim( wp_strip_all_tags( (string) get_term_meta( $term->term_id, '_en_US_name', true ) ) );
    if ( '' === $name ) { return $term; }
    $copy = clone $term;
    $copy->name = $name;
    $description = (string) get_term_meta( $term->term_id, '_en_US_description', true );
    if ( '' !== trim( wp_strip_all_tags( $description ) ) ) { $copy->description = $description; }
    return $copy;
}

add_filter( 'get_term', static function ( $term, $taxonomy ) {
    return $term instanceof WP_Term && 'category' === (string) $taxonomy ? mdoej_translate_category_20260821( $term ) : $term;
}, PHP_INT_MAX, 2 );

add_filter( 'get_terms', static function ( $terms, $taxonomies ) {
    if ( ! mdoej_en_20260821() || ! is_array( $terms ) || ! in_array( 'category', (array) $taxonomies, true ) ) { return $terms; }
    foreach ( $terms as $i => $term ) {
        if ( $term instanceof WP_Term && 'category' === $term->taxonomy ) { $terms[ $i ] = mdoej_translate_category_20260821( $term ); }
    }
    return $terms;
}, PHP_INT_MAX, 2 );

add_filter( 'get_category_link', static function ( $url, $term_id ) {
    if ( ! mdoej_en_20260821() ) { return $url; }
    $slug = sanitize_title( (string) get_term_meta( (int) $term_id, '_en_US_slug', true ) );
    if ( '' === $slug || '1' !== (string) get_term_meta( (int) $term_id, '_en_US_published', true ) ) { return $url; }
    return home_url( '/en/category/' . $slug . '/' );
}, PHP_INT_MAX, 2 );
