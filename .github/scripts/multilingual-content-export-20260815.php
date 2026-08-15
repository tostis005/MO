<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$page_ids = array( 3699, 6, 1142, 1426, 1385, 7, 8, 9, 1391, 3, 1640, 3736 );
$out = array( 'pages' => array(), 'products' => array(), 'terms' => array() );
foreach ( $page_ids as $id ) {
    $p = get_post( $id );
    if ( ! $p ) { continue; }
    $out['pages'][] = array(
        'id' => (int) $id,
        'title' => $p->post_title,
        'slug' => $p->post_name,
        'excerpt' => $p->post_excerpt,
        'content' => $p->post_content,
    );
}
$products = get_posts( array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 100,
    'orderby' => 'ID',
    'order' => 'ASC',
) );
foreach ( $products as $p ) {
    $out['products'][] = array(
        'id' => (int) $p->ID,
        'title' => $p->post_title,
        'slug' => $p->post_name,
        'excerpt' => $p->post_excerpt,
        'content' => $p->post_content,
    );
}
foreach ( array( 'product_cat', 'product_tag' ) as $tax ) {
    $terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
    if ( is_wp_error( $terms ) ) { continue; }
    foreach ( $terms as $t ) {
        $out['terms'][] = array(
            'taxonomy' => $tax,
            'id' => (int) $t->term_id,
            'name' => $t->name,
            'slug' => $t->slug,
            'description' => $t->description,
        );
    }
}
if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
    foreach ( wc_get_attribute_taxonomies() as $a ) {
        $tax = 'pa_' . $a->attribute_name;
        $terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
        $out['terms'][] = array(
            'taxonomy' => 'attribute_label',
            'id' => (int) $a->attribute_id,
            'name' => $a->attribute_label,
            'slug' => $a->attribute_name,
            'description' => '',
        );
        if ( is_wp_error( $terms ) ) { continue; }
        foreach ( $terms as $t ) {
            $out['terms'][] = array(
                'taxonomy' => $tax,
                'id' => (int) $t->term_id,
                'name' => $t->name,
                'slug' => $t->slug,
                'description' => $t->description,
            );
        }
    }
}
$json = wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
echo '__MDO_TRANSLATION_SOURCE__=' . base64_encode( $json ) . PHP_EOL;
