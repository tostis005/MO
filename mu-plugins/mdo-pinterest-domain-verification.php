<?php
/**
 * Plugin Name: MDO Pinterest Domain Verification
 * Description: Adds the Pinterest domain verification meta tag to the public site head.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'wp_head',
    static function () {
        echo '<meta name="p:domain_verify" content="bd9b4a2c373b774bb7753fa7040172ac">' . "\n";
    },
    1
);
