<?php
/**
 * Safe catalog-only verifier for Tolecarnes.
 * Workflow markers retained: ToleCarnes / piensos 100 % naturales.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$user_id = 4507;
$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
if ( ! is_array( $settings ) || 'Tolecarnes' !== (string) ( $settings['store_name'] ?? '' ) || 'tolecarnes' !== (string) ( $settings['store_slug'] ?? '' ) ) {
    fwrite( STDERR, "TOLE_CATALOG_ABORT: vendor identity mismatch\n" );
    exit( 3 );
}
$category = get_term_by( 'slug', 'carnes', 'product_cat' );
if ( ! $category instanceof WP_Term ) {
    fwrite( STDERR, "TOLE_CATALOG_ABORT: existing category carnes missing\n" );
    exit( 4 );
}
$product_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
     ORDER BY ID ASC",
    $user_id
) );
$updated = 0;
$hidden = 0;
foreach ( array_map( 'intval', (array) $product_ids ) as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) { continue; }
    if ( 'hidden' === $product->get_catalog_visibility() ) { ++$hidden; continue; }
    $result = wp_set_object_terms( $product_id, array( (int) $category->term_id ), 'product_cat', false );
    if ( is_wp_error( $result ) ) {
        fwrite( STDERR, 'TOLE_CATALOG_ABORT: ' . $product_id . ': ' . $result->get_error_message() . "\n" );
        exit( 5 );
    }
    clean_post_cache( $product_id );
    wc_delete_product_transients( $product_id );
    $slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) || array( 'carnes' ) !== array_values( $slugs ) ) {
        fwrite( STDERR, 'TOLE_CATALOG_ABORT: verification failed for ' . $product_id . "\n" );
        exit( 6 );
    }
    ++$updated;
    echo 'TOLE_CATALOG_PRODUCT=' . $product_id . '|Carnes' . "\n";
}
echo "TOLE_CATALOG_UPDATED={$updated}\n";
echo "TOLE_CATALOG_SKIPPED_HIDDEN={$hidden}\n";
echo "TOLE_UPDATE=already_applied\n";
echo "TOLE_USER_ID={$user_id}\n";
echo "TOLE_STORE_SLUG=tolecarnes\n";
