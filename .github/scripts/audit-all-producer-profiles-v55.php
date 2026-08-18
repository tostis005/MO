<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$rows = $wpdb->get_results(
    "SELECT um.user_id,u.user_login,u.user_nicename,u.display_name,um.meta_value
     FROM {$wpdb->usermeta} um
     INNER JOIN {$wpdb->users} u ON u.ID=um.user_id
     WHERE um.meta_key='wcfmmp_profile_settings'
     ORDER BY um.user_id",
    ARRAY_A
);
$out = array();
foreach ( (array) $rows as $row ) {
    $settings = maybe_unserialize( $row['meta_value'] ?? '' );
    if ( ! is_array( $settings ) ) { continue; }
    $id = (int) $row['user_id'];
    $es = (string) get_user_meta( $id, '_store_description', true );
    if ( '' === trim( wp_strip_all_tags( $es ) ) ) {
        $es = (string) ( $settings['shop_description'] ?? '' );
    }
    $en = (string) get_user_meta( $id, '_mdo_en_store_description', true );
    $out[] = array(
        'user_id' => $id,
        'login' => (string) $row['user_login'],
        'nicename' => (string) $row['user_nicename'],
        'display_name' => (string) $row['display_name'],
        'store_name' => (string) ( $settings['store_name'] ?? '' ),
        'store_slug' => (string) ( $settings['store_slug'] ?? '' ),
        'published_products' => (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",
            $id
        ) ),
        'all_nontrash_products' => (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN ('trash','auto-draft') AND post_author=%d",
            $id
        ) ),
        'spanish_chars' => mb_strlen( trim( wp_strip_all_tags( $es ) ) ),
        'english_chars' => mb_strlen( trim( wp_strip_all_tags( $en ) ) ),
        'english_ready' => mb_strlen( trim( wp_strip_all_tags( $en ) ) ) >= 500,
    );
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
