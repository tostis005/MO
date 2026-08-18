<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$rows = $wpdb->get_results(
    "SELECT um.user_id, u.user_login, u.user_nicename, u.display_name, um.meta_value
     FROM {$wpdb->usermeta} um
     INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
     WHERE um.meta_key = 'wcfmmp_profile_settings'
     ORDER BY um.user_id",
    ARRAY_A
);

foreach ( (array) $rows as $row ) {
    $settings = maybe_unserialize( $row['meta_value'] ?? '' );
    if ( ! is_array( $settings ) ) {
        continue;
    }
    $user_id = (int) $row['user_id'];
    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",
            $user_id
        )
    );
    if ( $count < 1 ) {
        continue;
    }

    $description = (string) get_user_meta( $user_id, '_store_description', true );
    if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
        $description = (string) ( $settings['shop_description'] ?? '' );
    }

    echo "===== STORE =====\n";
    echo 'user_id=' . $user_id . "\n";
    echo 'user_login=' . (string) $row['user_login'] . "\n";
    echo 'user_nicename=' . (string) $row['user_nicename'] . "\n";
    echo 'display_name=' . (string) $row['display_name'] . "\n";
    echo 'profile_store_slug=' . (string) ( $settings['store_slug'] ?? '' ) . "\n";
    echo 'store_name=' . (string) ( $settings['store_name'] ?? '' ) . "\n";
    echo 'published_products=' . $count . "\n";
    echo "----- SPANISH DESCRIPTION -----\n";
    echo $description . "\n";
    echo "===== END STORE =====\n\n";
}
