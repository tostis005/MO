<?php
/**
 * Plugin Name: MDO Staging Native Translation Render Bridge
 * Description: Staging-only bridge for metadata/attributes that third-party plugins mark as non-translatable. It only reads human-reviewed strings already stored in TranslatePress DB; it never translates or contains translation copy itself.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_native_bridge_is_dev() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    return 'dev.elmercadodeorigen.com' === $host || false !== strpos( (string) home_url(), 'dev.elmercadodeorigen.com' );
}

function mdo_native_bridge_is_english() {
    if ( ! mdo_native_bridge_is_dev() || is_admin() ) { return false; }
    if ( function_exists( 'mdo_native_switcher_current_language' ) ) {
        return 0 === stripos( (string) mdo_native_switcher_current_language(), 'en' );
    }
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
    return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

function mdo_native_bridge_translations() {
    static $map = null;
    if ( null !== $map ) { return $map; }
    $map = array();
    if ( ! mdo_native_bridge_is_english() ) { return $map; }

    global $wpdb;
    $table = $wpdb->prefix . 'trp_dictionary_es_es_en_us';
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $exists !== $table ) { return $map; }

    $rows = $wpdb->get_results( "SELECT original, translated FROM `{$table}` WHERE status = 2 AND translated IS NOT NULL AND translated <> ''", ARRAY_A );
    foreach ( $rows as $row ) {
        if ( ! isset( $row['original'], $row['translated'] ) ) { continue; }
        $original = (string) $row['original'];
        $translated = (string) $row['translated'];
        if ( '' !== $original && '' !== $translated ) { $map[ $original ] = $translated; }
    }
    return $map;
}

function mdo_native_bridge_lookup( $text ) {
    $text = (string) $text;
    $map = mdo_native_bridge_translations();
    if ( isset( $map[ $text ] ) ) { return $map[ $text ]; }

    $lf = str_replace( "\r\n", "\n", $text );
    if ( isset( $map[ $lf ] ) ) { return $map[ $lf ]; }

    $collapsed = preg_replace( '/\s+/u', ' ', trim( $text ) );
    if ( is_string( $collapsed ) && '' !== $collapsed ) {
        foreach ( $map as $original => $translated ) {
            $candidate = preg_replace( '/\s+/u', ' ', trim( (string) $original ) );
            if ( $candidate === $collapsed ) { return $translated; }
        }
    }
    return $text;
}

function mdo_native_bridge_attribute_value( $value ) {
    $value = (string) $value;
    $direct = mdo_native_bridge_lookup( $value );
    if ( $direct !== $value ) { return $direct; }

    if ( preg_match( '/^(.*?[“"])(.+?)([”"])$/u', $value, $m ) ) {
        $inner = mdo_native_bridge_lookup( $m[2] );
        if ( $inner !== $m[2] ) { return $m[1] . $inner . $m[3]; }
    }

    if ( preg_match( '/^(.+?)\s+\((\d+)\s+(product|products)\)$/iu', $value, $m ) ) {
        $term = mdo_native_bridge_lookup( $m[1] );
        if ( $term !== $m[1] ) { return $term . ' (' . $m[2] . ' ' . $m[3] . ')'; }
    }

    return $value;
}

add_filter( 'document_title_parts', function( $parts ) {
    if ( ! mdo_native_bridge_is_english() || ! is_array( $parts ) ) { return $parts; }
    foreach ( $parts as $key => $value ) {
        if ( is_string( $value ) ) { $parts[ $key ] = mdo_native_bridge_lookup( $value ); }
    }
    return $parts;
}, 9999 );

/**
 * TranslatePress starts its translation buffer on init priority 0. Start this buffer
 * first so it sits outside TranslatePress's buffer. At shutdown the translated page
 * is flushed into this callback, allowing us to fix only attributes/metadata that
 * third-party code explicitly marked as non-translatable.
 */
add_action( 'init', function() {
    if ( ! mdo_native_bridge_is_english() ) { return; }
    ob_start( static function( $html ) {
        if ( ! is_string( $html ) || '' === $html ) { return $html; }

        $html = preg_replace_callback( '#<title([^>]*)>(.*?)</title>#isu', static function( $m ) {
            $decoded = html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $translated = mdo_native_bridge_lookup( $decoded );
            if ( $translated === $decoded ) { return $m[0]; }
            return '<title' . $m[1] . '>' . esc_html( $translated ) . '</title>';
        }, $html );

        $html = preg_replace_callback( '/\s(aria-label|title|alt|placeholder)=("|\')(.*?)\2/isu', static function( $m ) {
            $decoded = html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $translated = mdo_native_bridge_attribute_value( $decoded );
            if ( $translated === $decoded ) { return $m[0]; }
            return ' ' . $m[1] . '=' . $m[2] . esc_attr( $translated ) . $m[2];
        }, $html );

        return $html;
    } );
}, -9999 );
