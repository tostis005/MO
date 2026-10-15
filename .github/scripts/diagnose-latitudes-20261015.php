<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$vendors = array();
foreach ( get_users( array( 'role' => 'wcfm_vendor', 'number' => 2000 ) ) as $user ) {
    $settings = get_user_meta( $user->ID, 'wcfmmp_profile_settings', true );
    $vendors[] = array(
        'id' => (int) $user->ID,
        'name' => (string) $user->display_name,
        'disabled' => (string) get_user_meta( $user->ID, '_disable_vendor', true ),
        'offline' => (string) get_user_meta( $user->ID, '_wcfm_store_offline', true ),
        'profile_keys' => is_array( $settings ) ? array_keys( $settings ) : array(),
        'banner_type' => isset( $settings['banner'] ) ? gettype( $settings['banner'] ) : null,
        'gravatar_type' => isset( $settings['gravatar'] ) ? gettype( $settings['gravatar'] ) : null,
    );
}
$patterns = array();
foreach ( get_users( array( 'fields' => array( 'ID', 'user_email', 'user_login' ), 'number' => 5000 ) ) as $u ) {
    $local = strtolower( (string) explode( '@', $u->user_email )[0] );
    if ( strpos( $local, 'tostis005' ) !== false ) {
        $patterns[] = preg_replace( '/[0-9]+/', '#', $local );
    }
}
$patterns = array_count_values( $patterns );
$report = array(
    'vendor_count' => count( $vendors ),
    'sample_profiles' => array_slice( $vendors, -12 ),
    'tostis005_email_local_patterns' => $patterns,
    'existing_latitudes_user' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_nicename=%s OR user_login=%s LIMIT 1", 'latitudes', 'latitudes' ) ),
    'supplier_exists' => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MDO_Database::table( 'suppliers' ) . ' WHERE code=%s OR source_url LIKE %s', 'latitudes', '%latitudes.es%' ) ),
);
foreach ( array( 'https://latitudes.es/exoticos/', 'https://latitudes.es/premium/' ) as $url ) {
    $response = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => array( 'Accept' => 'text/html' ) ) );
    $html = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
    $report['source_tests'][] = array(
        'url' => $url,
        'status' => is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response ),
        'html_bytes' => strlen( $html ),
        'product_link_candidates' => preg_match_all( '~href=["\x27][^"\x27]*latitudes\\.es/(?:exoticos|premium)/[^"\x27]*["\x27]~i', $html ),
        'pagination_detected' => (bool) preg_match( '~/page/[2-9]/~', $html ),
    );
}
echo 'LATITUDES_READONLY_DIAGNOSTIC=' . wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
