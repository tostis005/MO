<?php
// Production verification trigger v1.0.2.
if ( ! defined( 'ABSPATH' ) ) { exit( 90 ); }
if ( ! defined( 'DOING_AJAX' ) ) { define( 'DOING_AJAX', true ); }

$out = array(
    'ok'       => false,
    'mentta'   => array(),
    'products' => array(),
    'errors'   => array(),
);

$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
$_SERVER['HTTP_REFERER'] = 'https://www.elmercadodeorigen.com/en/shop/';

if ( ! function_exists( 'mdo_mentta_should_hide_publicly' ) ) {
    $out['mentta'] = array( 'plugin_loaded' => false, 'ajax_hidden' => false, 'term_absent' => false );
    $out['errors'][] = 'mentta_plugin_not_loaded';
} else {
    $ajax_hidden = (bool) mdo_mentta_should_hide_publicly();
    $slugs = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'fields'     => 'slugs',
    ) );
    $term_absent = ! is_wp_error( $slugs ) && ! in_array( 'mentta', array_map( 'strtolower', (array) $slugs ), true );
    $out['mentta'] = array(
        'plugin_loaded' => true,
        'ajax_hidden'   => $ajax_hidden,
        'term_absent'   => $term_absent,
    );
    if ( ! $ajax_hidden ) { $out['errors'][] = 'mentta_ajax_not_hidden'; }
    if ( ! $term_absent ) { $out['errors'][] = 'mentta_term_still_visible'; }
}

if ( ! function_exists( 'mdo_en_preview_allowed_20260819' ) ) {
    $out['errors'][] = 'preview_plugin_not_loaded';
    echo wp_json_encode( $out );
    exit( 1 );
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
if ( ! $admins ) {
    $out['errors'][] = 'no_admin_user';
    echo wp_json_encode( $out );
    exit( 2 );
}
wp_set_current_user( (int) $admins[0] );

if ( ! mdo_en_preview_allowed_20260819() ) {
    $out['errors'][] = 'admin_english_preview_not_allowed';
}

foreach ( array( 4508 => 'Puente Robles', 4509 => 'El Catedrático' ) as $author => $vendor ) {
    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'author'         => $author,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array( 'key' => '_en_US_ready', 'value' => '1' ),
            array( 'key' => '_en_US_post_title', 'compare' => 'EXISTS' ),
            array( 'key' => '_en_US_post_name', 'compare' => 'EXISTS' ),
        ),
    ) );

    if ( ! $ids ) {
        $out['errors'][] = 'missing_staged_product_' . $author;
        continue;
    }

    $id       = (int) $ids[0];
    $original = (string) get_post_field( 'post_title', $id );
    $title    = (string) get_post_meta( $id, '_en_US_post_title', true );
    $slug     = sanitize_title( (string) get_post_meta( $id, '_en_US_post_name', true ) );
    $content  = (string) get_post_meta( $id, '_en_US_post_content', true );
    $excerpt  = (string) get_post_meta( $id, '_en_US_post_excerpt', true );

    $filtered_title = (string) apply_filters( 'the_title', $original, $id );
    $permalink      = (string) get_permalink( $id );
    $resolved       = (int) mdo_en_preview_find_product_20260819( $slug );
    $product        = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;

    $name_ok        = $product && (string) $product->get_name() === $title;
    $description_ok = $product && (string) $product->get_description() === $content;
    $excerpt_ok     = $product && (string) $product->get_short_description() === $excerpt;
    $title_ok       = '' !== trim( $title ) && $filtered_title === $title;
    $url_ok         = '' !== $slug && false !== strpos( $permalink, '/en/product/' . rawurlencode( $slug ) . '/' );
    $route_ok       = $resolved === $id;
    $guard_ok       = '1' === (string) get_post_meta( $id, '_en_US_ready', true )
        && '1' !== (string) get_post_meta( $id, '_en_US_published', true );

    $out['products'][ $vendor ] = array(
        'id'             => $id,
        'english_title'  => $title,
        'english_slug'   => $slug,
        'permalink'      => $permalink,
        'title_ok'       => $title_ok,
        'name_ok'        => $name_ok,
        'description_ok' => $description_ok,
        'excerpt_ok'     => $excerpt_ok,
        'route_ok'       => $route_ok,
        'guard_ok'       => $guard_ok,
    );

    foreach ( array(
        'title' => $title_ok,
        'name' => $name_ok,
        'description' => $description_ok,
        'excerpt' => $excerpt_ok,
        'url' => $url_ok,
        'route' => $route_ok,
        'guard' => $guard_ok,
    ) as $check => $ok ) {
        if ( ! $ok ) {
            $out['errors'][] = $vendor . '_' . $check;
        }
    }
}

$out['ok'] = empty( $out['errors'] )
    && ! empty( $out['mentta']['ajax_hidden'] )
    && ! empty( $out['mentta']['term_absent'] )
    && 2 === count( $out['products'] );
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
exit( $out['ok'] ? 0 : 3 );
