<?php
/** Read-only production diagnostic for La Huerta de Ana Mary public WCFM store. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$user_id = 4514;
$user = get_userdata( $user_id );
if ( ! $user ) {
    fwrite( STDERR, "HUERTA_DIAG_ABORT user_not_found\n" );
    exit( 2 );
}

$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$settings = is_array( $settings ) ? $settings : array();

echo 'user_id=' . $user_id . "\n";
echo 'login=' . $user->user_login . "\n";
echo 'nicename=' . $user->user_nicename . "\n";
echo 'display_name=' . $user->display_name . "\n";
echo 'roles=' . wp_json_encode( array_values( (array) $user->roles ) ) . "\n";
echo 'store_name=' . (string) ( $settings['store_name'] ?? '' ) . "\n";
echo 'store_slug=' . (string) ( $settings['store_slug'] ?? '' ) . "\n";
echo 'store_description_chars=' . mb_strlen( trim( wp_strip_all_tags( (string) get_user_meta( $user_id, '_store_description', true ) ) ) ) . "\n";
echo 'function_wcfmmp_get_store_url=' . ( function_exists( 'wcfmmp_get_store_url' ) ? 'yes' : 'no' ) . "\n";
if ( function_exists( 'wcfmmp_get_store_url' ) ) {
    echo 'wcfm_store_url=' . wcfmmp_get_store_url( $user_id ) . "\n";
}
if ( function_exists( 'wcfm_is_vendor' ) ) {
    echo 'wcfm_is_vendor=' . ( wcfm_is_vendor( $user_id ) ? 'yes' : 'no' ) . "\n";
}
if ( function_exists( 'wcfm_vendor_has_capability' ) ) {
    echo 'vendor_capability_manage_products=' . ( wcfm_vendor_has_capability( $user_id, 'manage_products' ) ? 'yes' : 'no' ) . "\n";
}

global $wpdb;
$count = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",
    $user_id
) );
echo 'published_products=' . $count . "\n";

$products = get_posts( array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'author' => $user_id,
    'posts_per_page' => 3,
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => 'ids',
) );
foreach ( $products as $product_id ) {
    echo 'product=' . (int) $product_id . '|' . get_permalink( $product_id ) . "\n";
}

echo 'option_wcfm_store_url=' . (string) get_option( 'wcfm_store_url', '' ) . "\n";
echo 'option_wcfmmp_store_url=' . (string) get_option( 'wcfmmp_store_url', '' ) . "\n";
echo "HUERTA_DIAG_OK\n";
