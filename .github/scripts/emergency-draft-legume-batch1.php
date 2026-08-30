<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$titles = array(
    'Qué nutrientes aportan las legumbres: proteínas, fibra, hierro, vitaminas y minerales',
    '¿Qué legumbre tiene más proteína? Comparativa de las legumbres más consumidas en España',
    '¿Qué legumbre tiene más hierro? Comparativa de las legumbres más consumidas en España',
    '¿Qué legumbre tiene más fibra? Comparativa nutricional',
    'Garbanzos, lentejas o alubias: ¿cuál es más nutritiva?',
);

$results = array();
foreach ( $titles as $title ) {
    $ids = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
        'title'          => $title,
        'fields'         => 'ids',
        'posts_per_page' => 10,
        'no_found_rows'  => true,
    ) );

    // WP's title query can vary by version; fall back to an exact DB match.
    if ( empty( $ids ) ) {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_title = %s AND post_status <> 'trash' ORDER BY ID DESC",
            $title
        ) );
    }

    foreach ( array_unique( array_map( 'intval', $ids ) ) as $post_id ) {
        $before = get_post_status( $post_id );
        $updated = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ), true );
        if ( is_wp_error( $updated ) ) {
            WP_CLI::error( 'Could not draft post ' . $post_id . ': ' . $updated->get_error_message() );
        }
        clean_post_cache( $post_id );
        $results[] = array(
            'id'     => $post_id,
            'title'  => get_the_title( $post_id ),
            'before' => $before,
            'after'  => get_post_status( $post_id ),
            'slug'   => get_post_field( 'post_name', $post_id ),
        );
    }
}

wp_cache_flush();
flush_rewrite_rules( false );

echo wp_json_encode( array( 'count' => count( $results ), 'posts' => $results ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
