<?php
/**
 * Read-only diagnostic for the production global shop.
 * Intended for WP-CLI eval-file only.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$rows = $wpdb->get_results(
    "SELECT post_author, post_status, COUNT(*) AS qty
     FROM {$wpdb->posts}
     WHERE post_type = 'product'
     GROUP BY post_author, post_status
     ORDER BY post_author, post_status",
    ARRAY_A
);

$author_counts = array();
foreach ( (array) $rows as $row ) {
    $author_id = (int) $row['post_author'];
    if ( ! isset( $author_counts[ $author_id ] ) ) {
        $user = get_userdata( $author_id );
        $author_counts[ $author_id ] = array(
            'name' => $user instanceof WP_User ? $user->display_name : '',
            'roles' => $user instanceof WP_User ? array_values( (array) $user->roles ) : array(),
            'statuses' => array(),
        );
    }
    $author_counts[ $author_id ]['statuses'][ (string) $row['post_status'] ] = (int) $row['qty'];
}

$published_ids = get_posts(
    array(
        'post_type'        => 'product',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'suppress_filters' => true,
        'no_found_rows'    => true,
    )
);
$published_ids = array_values( array_unique( array_map( 'absint', (array) $published_ids ) ) );

$visibility_by_author = array();
$visibility_term_counts = array();
$stock_by_author = array();
foreach ( $published_ids as $product_id ) {
    $post = get_post( $product_id );
    if ( ! $post ) {
        continue;
    }
    $author = (int) $post->post_author;
    if ( ! isset( $visibility_by_author[ $author ] ) ) {
        $visibility_by_author[ $author ] = array( 'published' => 0, 'catalog_eligible' => 0, 'exclude_from_catalog' => 0 );
        $stock_by_author[ $author ] = array();
    }
    $visibility_by_author[ $author ]['published']++;
    $terms = wp_get_object_terms( $product_id, 'product_visibility', array( 'fields' => 'slugs' ) );
    $terms = is_wp_error( $terms ) ? array() : array_values( (array) $terms );
    foreach ( $terms as $term ) {
        $visibility_term_counts[ $term ] = ( $visibility_term_counts[ $term ] ?? 0 ) + 1;
    }
    if ( in_array( 'exclude-from-catalog', $terms, true ) ) {
        $visibility_by_author[ $author ]['exclude_from_catalog']++;
    } else {
        $visibility_by_author[ $author ]['catalog_eligible']++;
    }
    $stock = (string) get_post_meta( $product_id, '_stock_status', true );
    $stock = '' !== $stock ? $stock : '(empty)';
    $stock_by_author[ $author ][ $stock ] = ( $stock_by_author[ $author ][ $stock ] ?? 0 ) + 1;
}

$destination = null;
$excluded_vendors = null;
$ranked_ids = null;
$ranked_by_author = null;
$rank_error = null;
if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
    try {
        $destination = MDO_Catalog_Destination_Frontend::current_destination();
        $excluded_vendors = MDO_Catalog_Destination_Frontend::excluded_vendor_ids();
        $method = new ReflectionMethod( 'MDO_Catalog_Destination_Frontend', 'ranked_product_ids' );
        $method->setAccessible( true );
        $ranked_ids = array_values( array_map( 'absint', (array) $method->invoke( null ) ) );
        $ranked_by_author = array();
        foreach ( $ranked_ids as $id ) {
            $post = get_post( $id );
            if ( $post ) {
                $a = (int) $post->post_author;
                $ranked_by_author[ $a ] = ( $ranked_by_author[ $a ] ?? 0 ) + 1;
            }
        }
    } catch ( Throwable $e ) {
        $rank_error = get_class( $e ) . ': ' . $e->getMessage();
    }
}

$users = array();
foreach ( array_keys( $author_counts ) as $author_id ) {
    $profile = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    $users[ $author_id ] = array(
        'name' => $author_counts[ $author_id ]['name'],
        'roles' => $author_counts[ $author_id ]['roles'],
        'wcfmmp_profile_settings_type' => gettype( $profile ),
        'store_hide' => is_array( $profile ) ? ( $profile['store_hide'] ?? null ) : null,
        'store_name' => is_array( $profile ) ? ( $profile['store_name'] ?? null ) : null,
    );
}

$result = array(
    'generated_at_gmt' => gmdate( 'c' ),
    'published_product_count_raw' => count( $published_ids ),
    'product_counts_by_author' => $author_counts,
    'visibility_by_author' => $visibility_by_author,
    'visibility_term_counts' => $visibility_term_counts,
    'stock_by_author' => $stock_by_author,
    'woocommerce_hide_out_of_stock_items' => get_option( 'woocommerce_hide_out_of_stock_items' ),
    'destination' => $destination,
    'excluded_vendor_ids' => $excluded_vendors,
    'ranked_product_count' => is_array( $ranked_ids ) ? count( $ranked_ids ) : null,
    'ranked_by_author' => $ranked_by_author,
    'ranked_first_40_ids' => is_array( $ranked_ids ) ? array_slice( $ranked_ids, 0, 40 ) : null,
    'rank_error' => $rank_error,
    'vendor_users' => $users,
);

echo '__EMDO_SHOP_STATE__' . wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
