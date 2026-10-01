<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

$supplier_id = 0;
if ( class_exists( 'MDO_Supplier_Repository' ) ) {
    foreach ( MDO_Supplier_Repository::all() as $supplier ) {
        if ( 'selectos-de-castilla' === (string) ( $supplier['connector'] ?? '' )
            || 'selectos-de-castilla' === (string) ( $supplier['code'] ?? '' )
            || 'Selectos de Castilla' === (string) ( $supplier['name'] ?? '' ) ) {
            $supplier_id = (int) $supplier['id'];
            break;
        }
    }
}
if ( $supplier_id <= 0 ) { $supplier_id = 5; }

$ids = get_posts( array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_supplier_id',
    'meta_value'     => $supplier_id,
    'orderby'        => 'ID',
    'order'          => 'ASC',
) );

$normalize = static function ( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( $text );
    $text = strtolower( $text );
    $text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
    return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
};

$rows = array();
$duck = array();
$cats = array();

foreach ( $ids as $id ) {
    $id = (int) $id;
    $title  = (string) get_the_title( $id );
    $source = (string) get_post_meta( $id, '_emdo_source_url', true );
    $desc   = (string) get_post_field( 'post_content', $id );
    $short  = (string) get_post_field( 'post_excerpt', $id );
    $n_title = $normalize( $title );
    $n_source = $normalize( rawurldecode( $source ) );
    $is_duck = (bool) preg_match( '/\b(pato|magret|foie|rillette|rillettes)\b/', $n_title . ' ' . $n_source );

    $terms = wp_get_post_terms( $id, 'product_cat' );
    $term_rows = array();
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            if ( ! $term instanceof WP_Term ) { continue; }
            $term_rows[] = array(
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => (int) $term->parent,
                'url' => get_term_link( $term ),
            );
            $cats[ $term->slug ] = array(
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => (int) $term->parent,
                'count' => (int) $term->count,
                'url' => get_term_link( $term ),
            );
        }
    }
    $product = wc_get_product( $id );
    $row = array(
        'id' => $id,
        'title' => $title,
        'slug' => (string) get_post_field( 'post_name', $id ),
        'url' => (string) get_permalink( $id ),
        'source' => $source,
        'stock' => $product ? $product->get_stock_status() : null,
        'price' => $product ? $product->get_price() : null,
        'image' => (int) get_post_thumbnail_id( $id ),
        'categories' => $term_rows,
        'duck' => $is_duck,
        'signals' => array(
            'title' => $n_title,
            'source' => $n_source,
        ),
    );
    $rows[] = $row;
    if ( $is_duck ) { $duck[] = $row; }
}

$existing = get_terms( array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
) );
$existing_rows = array();
if ( ! is_wp_error( $existing ) ) {
    foreach ( $existing as $term ) {
        if ( ! $term instanceof WP_Term ) { continue; }
        $n = $normalize( $term->name . ' ' . $term->slug );
        if ( preg_match( '/\b(pato|magret|foie|confit|rillette|rillettes|mousse|pate)\b/', $n ) ) {
            $existing_rows[] = array(
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => (int) $term->parent,
                'count' => (int) $term->count,
                'url' => get_term_link( $term ),
                'description_chars' => strlen( wp_strip_all_tags( (string) $term->description ) ),
            );
        }
    }
}

echo wp_json_encode( array(
    'generated_at' => gmdate( 'c' ),
    'supplier_id' => $supplier_id,
    'published_products' => count( $ids ),
    'duck_products' => count( $duck ),
    'duck_rows' => $duck,
    'existing_duck_product_categories' => $existing_rows,
    'all_categories_on_selectos_products' => array_values( $cats ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
