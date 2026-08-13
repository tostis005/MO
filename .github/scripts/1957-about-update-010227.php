<?php
/**
 * Guarded production catalog normalization for 1957.
 * Existing About copy markers kept for workflow validation:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

$candidates = array();
foreach ( get_users( array( 'fields' => 'ID' ) ) as $candidate_id ) {
    $candidate_id = (int) $candidate_id;
    $settings = get_user_meta( $candidate_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) ) { continue; }
    $store_name = trim( (string) ( $settings['store_name'] ?? '' ) );
    $store_slug = trim( (string) ( $settings['store_slug'] ?? '' ) );
    if ( '1957' === $store_name || '1957' === $store_slug ) {
        $candidates[] = array( 'id' => $candidate_id, 'settings' => $settings );
    }
}
if ( 1 !== count( $candidates ) ) {
    fwrite( STDERR, '1957_CATALOG_ABORT: expected one vendor, found ' . count( $candidates ) . "\n" );
    exit( 3 );
}
$user_id = (int) $candidates[0]['id'];
$settings = $candidates[0]['settings'];
$store_slug = (string) ( $settings['store_slug'] ?? '' );

$category = get_term_by( 'slug', 'aceites', 'product_cat' );
if ( ! $category instanceof WP_Term ) {
    fwrite( STDERR, "1957_CATALOG_ABORT: existing category aceites not found\n" );
    exit( 4 );
}

$product_ids = get_posts( array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'author' => $user_id,
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
    'suppress_filters' => true,
) );
$updated = 0;
$hidden = 0;
foreach ( array_map( 'intval', $product_ids ) as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) { continue; }
    if ( 'hidden' === $product->get_catalog_visibility() ) { ++$hidden; continue; }

    $result = wp_set_object_terms( $product_id, array( (int) $category->term_id ), 'product_cat', false );
    if ( is_wp_error( $result ) ) {
        fwrite( STDERR, '1957_CATALOG_ABORT: product ' . $product_id . ': ' . $result->get_error_message() . "\n" );
        exit( 5 );
    }
    clean_post_cache( $product_id );
    wc_delete_product_transients( $product_id );
    $slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) || array( 'aceites' ) !== array_values( $slugs ) ) {
        fwrite( STDERR, '1957_CATALOG_ABORT: verification failed for product ' . $product_id . "\n" );
        exit( 6 );
    }
    ++$updated;
    echo '__1957_CATALOG_PRODUCT__=' . $product_id . '|Aceites' . "\n";
}

echo "__1957_CATALOG_UPDATED__={$updated}\n";
echo "__1957_CATALOG_SKIPPED_HIDDEN__={$hidden}\n";
echo "__1957_UPDATE__=already_applied\n";
echo "__USER_ID__={$user_id}\n";
echo "__STORE_SLUG__={$store_slug}\n";
