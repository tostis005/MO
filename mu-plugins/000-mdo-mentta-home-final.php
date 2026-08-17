<?php
/**
 * Plugin Name: MDO - MENTTA Home Final Filter
 * Description: Final server-side safety filter that removes only the MENTTA category card from the public Home output.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    return;
}

/**
 * This MU plugin is deliberately prefixed with 000 so it loads before the
 * Home output-polish MU plugins. Its buffer therefore receives their final
 * transformed HTML and can remove only the exact MENTTA category anchor.
 */
function mdo_mentta_home_final_html_filter( $html ) {
    if ( ! is_string( $html ) || false === stripos( $html, '/mentta' ) || false === strpos( $html, 'emo-category-card' ) ) {
        return $html;
    }

    $filtered = preg_replace(
        '~<a\s+class="emo-category-card"\s+href="[^"]*/mentta/?"[^>]*>.*?</a>~is',
        '',
        $html
    );

    return is_string( $filtered ) ? $filtered : $html;
}

ob_start( 'mdo_mentta_home_final_html_filter' );
