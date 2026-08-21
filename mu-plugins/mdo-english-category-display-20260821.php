<?php
/**
 * Plugin Name: MDO English Category Display
 * Description: Renders product-category names and descriptions from persisted English term metadata on /en/ without changing native taxonomy slugs or queries.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdoecd_en_20260821(): bool {
    if ( function_exists( 'mdoer_en' ) ) { return mdoer_en(); }
    if ( function_exists( 'mdo_en_is_request' ) ) { return mdo_en_is_request(); }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return $path === '/en' || str_starts_with( $path, '/en/' );
}

function mdoecd_name_20260821( int $term_id ): string {
    if ( '1' !== (string) get_term_meta( $term_id, '_en_US_published', true ) ) { return ''; }
    return trim( wp_strip_all_tags( html_entity_decode( (string) get_term_meta( $term_id, '_en_US_name', true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

function mdoecd_description_20260821( int $term_id ): string {
    if ( '1' !== (string) get_term_meta( $term_id, '_en_US_published', true ) ) { return ''; }
    return trim( (string) get_term_meta( $term_id, '_en_US_description', true ) );
}

function mdoecd_translate_term_20260821( WP_Term $term ): WP_Term {
    if ( ! mdoecd_en_20260821() || 'product_cat' !== $term->taxonomy ) { return $term; }
    $name = mdoecd_name_20260821( (int) $term->term_id );
    if ( '' === $name ) { return $term; }
    $copy = clone $term;
    $copy->name = $name;
    $description = mdoecd_description_20260821( (int) $term->term_id );
    if ( '' !== trim( wp_strip_all_tags( $description ) ) ) { $copy->description = $description; }
    return $copy;
}

add_filter( 'get_term', static function ( $term, $taxonomy ) {
    return $term instanceof WP_Term && 'product_cat' === (string) $taxonomy ? mdoecd_translate_term_20260821( $term ) : $term;
}, PHP_INT_MAX, 2 );

add_filter( 'get_terms', static function ( $terms, $taxonomies ) {
    if ( ! mdoecd_en_20260821() || ! is_array( $terms ) || ! in_array( 'product_cat', (array) $taxonomies, true ) ) { return $terms; }
    foreach ( $terms as $index => $term ) {
        if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) { $terms[ $index ] = mdoecd_translate_term_20260821( $term ); }
    }
    return $terms;
}, PHP_INT_MAX, 2 );

add_filter( 'single_term_title', static function ( string $title ): string {
    if ( ! mdoecd_en_20260821() ) { return $title; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) { return $title; }
    $name = mdoecd_name_20260821( (int) $term->term_id );
    return '' !== $name ? $name : $title;
}, PHP_INT_MAX );

add_filter( 'woocommerce_page_title', static function ( string $title ): string {
    if ( ! mdoecd_en_20260821() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) { return $title; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) { return $title; }
    $name = mdoecd_name_20260821( (int) $term->term_id );
    return '' !== $name ? $name : $title;
}, PHP_INT_MAX );

add_filter( 'get_the_archive_title', static function ( string $title ): string {
    if ( ! mdoecd_en_20260821() ) { return $title; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) { return $title; }
    $name = mdoecd_name_20260821( (int) $term->term_id );
    return '' !== $name ? $name : $title;
}, PHP_INT_MAX );

add_filter( 'woocommerce_taxonomy_archive_description_raw', static function ( string $description, $term ): string {
    if ( ! mdoecd_en_20260821() || ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) { return $description; }
    $english = mdoecd_description_20260821( (int) $term->term_id );
    return '' !== trim( wp_strip_all_tags( $english ) ) ? $english : $description;
}, PHP_INT_MAX, 2 );
