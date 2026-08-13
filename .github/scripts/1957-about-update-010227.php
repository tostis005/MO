<?php
/**
 * Read-only production metadata audit for approved producers.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$target_authors = array( 3, 6, 4507, 4508, 4509 );
$relevant_taxonomies = array(
    'pa_tipo-pieza', 'pa_calidad', 'pa_raza-iberica', 'pa_alimentacion',
    'pa_con-dop', 'pa_dop', 'pa_origen', 'pa_preparacion', 'pa_rango-peso',
    'pa_curacion', 'pa_productor', 'pa_tipo-producto',
);
foreach ( $target_authors as $author_id ) {
    $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    $user = get_userdata( $author_id );
    echo 'METADATA_VENDOR ' . wp_json_encode( array(
        'id' => $author_id,
        'display_name' => $user instanceof WP_User ? (string) $user->display_name : '',
        'store_name' => is_array( $settings ) ? (string) ( $settings['store_name'] ?? '' ) : '',
        'store_slug' => is_array( $settings ) ? (string) ( $settings['store_slug'] ?? '' ) : '',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
         ORDER BY ID ASC",
        $author_id
    ) );
    foreach ( array_map( 'intval', (array) $ids ) as $product_id ) {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
        if ( ! $product instanceof WC_Product ) { continue; }
        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $categories ) ) { $categories = array(); }
        $attrs = array();
        foreach ( $relevant_taxonomies as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
            $names = wp_get_object_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $names ) && $names ) { $attrs[ $taxonomy ] = array_values( array_map( 'strval', $names ) ); }
        }
        echo 'METADATA_PRODUCT ' . wp_json_encode( array(
            'id' => $product_id,
            'author_id' => $author_id,
            'title' => (string) $product->get_name( 'edit' ),
            'visibility' => (string) $product->get_catalog_visibility(),
            'categories' => array_values( array_map( 'strval', (array) $categories ) ),
            'attributes' => $attrs,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    }
}
echo "__1957_UPDATE__=already_applied\n";
