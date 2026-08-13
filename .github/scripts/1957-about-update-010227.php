<?php
/**
 * Read-only production ownership diagnostic.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$rows = $wpdb->get_results(
    "SELECT ID, post_author, post_title, post_status
     FROM {$wpdb->posts}
     WHERE post_type = 'product' AND post_status = 'publish'
     ORDER BY ID ASC",
    ARRAY_A
);

$authors = array();
foreach ( (array) $rows as $row ) {
    $author_id = (int) $row['post_author'];
    if ( ! isset( $authors[ $author_id ] ) ) {
        $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
        $user = get_userdata( $author_id );
        $authors[ $author_id ] = array(
            'id' => $author_id,
            'display_name' => $user instanceof WP_User ? (string) $user->display_name : '',
            'store_name' => is_array( $settings ) ? (string) ( $settings['store_name'] ?? '' ) : '',
            'store_slug' => is_array( $settings ) ? (string) ( $settings['store_slug'] ?? '' ) : '',
        );
    }
    $product_id = (int) $row['ID'];
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
    $visibility = $product instanceof WC_Product ? (string) $product->get_catalog_visibility() : '';
    $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $categories ) ) { $categories = array(); }
    echo 'OWNERSHIP_PRODUCT ' . wp_json_encode( array(
        'id' => $product_id,
        'author_id' => $author_id,
        'title' => (string) $row['post_title'],
        'visibility' => $visibility,
        'categories' => array_values( array_map( 'strval', (array) $categories ) ),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
ksort( $authors );
foreach ( $authors as $author ) {
    echo 'OWNERSHIP_AUTHOR ' . wp_json_encode( $author, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}

echo 'OWNERSHIP_SUMMARY ' . wp_json_encode( array(
    'published_products' => count( $rows ),
    'author_ids' => array_keys( $authors ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

echo "__1957_UPDATE__=already_applied\n";
