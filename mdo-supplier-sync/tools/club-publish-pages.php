<?php
/** WP-CLI helper: publish two Club EMDO information pages after deployment. */
if ( ! defined('WP_CLI') || ! WP_CLI || ! current_user_can('manage_woocommerce') ) {
    exit(1);
}
MDO_Club::maybe_create_content();
foreach ( array('mdo_club_page' => 'mdo_club_landing', 'mdo_club_gift_page' => 'mdo_club_gift_cards') as $key => $shortcode ) {
    $id = (int) get_option($key);
    $page = $id ? get_post($id) : null;
    if (!$page || !has_shortcode($page->post_content, $shortcode)) {
        WP_CLI::error('Missing Club EMDO page: ' . $key);
    }
    wp_update_post(array('ID' => $id, 'post_status' => 'publish'));
    WP_CLI::log($key . ': ' . get_permalink($id));
}
flush_rewrite_rules(false);
WP_CLI::success('Club EMDO informational pages published without navigation links.');
