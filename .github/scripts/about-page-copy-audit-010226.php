<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$needle = 'Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.';
$like = '%' . $wpdb->esc_like( $needle ) . '%';

$posts = $wpdb->get_results( $wpdb->prepare(
    "SELECT ID, post_title, post_name, post_type, post_status, LENGTH(post_content) AS content_len
     FROM {$wpdb->posts}
     WHERE post_content LIKE %s
     ORDER BY ID",
    $like
) );

echo "=== POST CONTENT MATCHES ===\n";
foreach ( $posts as $p ) {
    printf("ID=%d type=%s status=%s slug=%s title=%s len=%d\n", $p->ID, $p->post_type, $p->post_status, $p->post_name, $p->post_title, $p->content_len);
}

$meta = $wpdb->get_results( $wpdb->prepare(
    "SELECT pm.post_id, pm.meta_key, LENGTH(pm.meta_value) AS meta_len, p.post_title, p.post_name, p.post_type, p.post_status
     FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_value LIKE %s
     ORDER BY pm.post_id, pm.meta_key",
    $like
) );

echo "=== POST META MATCHES ===\n";
foreach ( $meta as $m ) {
    printf("post_id=%d type=%s status=%s slug=%s title=%s key=%s len=%d\n", $m->post_id, $m->post_type, $m->post_status, $m->post_name, $m->post_title, $m->meta_key, $m->meta_len);
}

if ( empty( $posts ) && empty( $meta ) ) {
    echo "NO_MATCHES\n";
}
