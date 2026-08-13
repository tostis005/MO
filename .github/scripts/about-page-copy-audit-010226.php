<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$needles = array(
    'Todo comenzó en 2014 cuando empezamos a especializarnos',
    'El Mercado de Origen nace de la necesidad',
    'Nuestra historia',
);

echo "=== PUBLISHED PAGES CANDIDATES ===\n";
$pages = $wpdb->get_results(
    "SELECT ID, post_title, post_name, post_status, LENGTH(post_content) AS content_len
     FROM {$wpdb->posts}
     WHERE post_type='page' AND post_status IN ('publish','private','draft')
     ORDER BY ID"
);
foreach ( $pages as $p ) {
    $hay = strtolower( $p->post_title . ' ' . $p->post_name );
    if ( str_contains( $hay, 'acerca' ) || str_contains( $hay, 'nosotros' ) || str_contains( $hay, 'historia' ) || str_contains( $hay, 'quienes' ) || str_contains( $hay, 'origen' ) ) {
        printf("ID=%d status=%s slug=%s title=%s len=%d\n", $p->ID, $p->post_status, $p->post_name, $p->post_title, $p->content_len);
    }
}

foreach ( $needles as $needle ) {
    $like = '%' . $wpdb->esc_like( $needle ) . '%';
    echo "=== SEARCH: {$needle} ===\n";
    $posts = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, post_title, post_name, post_type, post_status, LENGTH(post_content) AS content_len
         FROM {$wpdb->posts}
         WHERE post_content LIKE %s
         ORDER BY ID",
        $like
    ) );
    foreach ( $posts as $p ) {
        printf("POST ID=%d type=%s status=%s slug=%s title=%s len=%d\n", $p->ID, $p->post_type, $p->post_status, $p->post_name, $p->post_title, $p->content_len);
    }

    $meta = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.post_id, pm.meta_key, LENGTH(pm.meta_value) AS meta_len, p.post_title, p.post_name, p.post_type, p.post_status
         FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_value LIKE %s
         ORDER BY pm.post_id, pm.meta_key",
        $like
    ) );
    foreach ( $meta as $m ) {
        printf("META post_id=%d type=%s status=%s slug=%s title=%s key=%s len=%d\n", $m->post_id, $m->post_type, $m->post_status, $m->post_name, $m->post_title, $m->meta_key, $m->meta_len);
    }
}
