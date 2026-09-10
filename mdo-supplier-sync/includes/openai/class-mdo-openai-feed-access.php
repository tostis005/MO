<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Keep the generated OpenAI merchant snapshot private until OpenAI explicitly
 * confirms direct-feed access for this account. Logged-in WooCommerce/site
 * administrators may still download it for QA from the EMDO admin screen.
 */
function mdo_openai_guard_direct_feed_access_20260910(): void {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );

    if ( 0 !== strpos( rtrim( $path, '/' ), '/emdo-feed/openai/' ) ) {
        return;
    }

    $settings = function_exists( 'mdo_openai_settings_20260909' ) ? mdo_openai_settings_20260909() : array();
    $confirmed = '1' === (string) ( $settings['direct_feed_access_confirmed'] ?? '0' );
    $admin_qa = is_user_logged_in() && ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) );

    if ( $confirmed || $admin_qa ) {
        return;
    }

    status_header( 404 );
    header( 'Content-Type: text/plain; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    header( 'Cache-Control: no-store, max-age=0', true );
    echo 'Feed access not enabled';
    exit;
}
add_action( 'parse_request', 'mdo_openai_guard_direct_feed_access_20260910', -19001 );
