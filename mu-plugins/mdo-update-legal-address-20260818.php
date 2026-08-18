<?php
/**
 * Plugin Name: MDO Update Legal Address
 * Description: Updates the public legal/contact address in Spanish and English and keeps rendered output/schema consistent.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const MDO_ULA_VERSION = '2026-08-18-1';
const MDO_ULA_NEW_ES = 'C/ Martín Soler 4, 4º dcha., esc. dcha., 28045 Madrid, España';
const MDO_ULA_NEW_EN = 'C/ Martín Soler 4, 4º dcha., esc. dcha., 28045 Madrid, Spain';
const MDO_ULA_NEW_STREET = 'C/ Martín Soler 4, 4º dcha., esc. dcha.';

function mdo_ula_replace_address( string $text ): string {
    $map = array(
        'C/ Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, España' => MDO_ULA_NEW_ES,
        'C/ Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, Spain' => MDO_ULA_NEW_EN,
        'C/ Ferrocarril 7, 1º B, esc. izda.' => MDO_ULA_NEW_STREET,
        'C/Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, España' => MDO_ULA_NEW_ES,
        'C/Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, Spain' => MDO_ULA_NEW_EN,
    );
    return str_ireplace( array_keys( $map ), array_values( $map ), $text );
}

function mdo_ula_apply_to_pages(): array {
    $updated = array();
    $slugs = array(
        'politica',
        'politica-de-privacidad',
        'politica-de-cookies',
        'aviso-legal',
        'envios',
        'devoluciones-y-reembolsos',
        'contacto',
    );

    foreach ( $slugs as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page instanceof WP_Post ) { continue; }

        $new_content = mdo_ula_replace_address( (string) $page->post_content );
        if ( $new_content !== (string) $page->post_content ) {
            wp_update_post( array( 'ID' => $page->ID, 'post_content' => $new_content ) );
            $updated[] = 'post:' . $page->ID;
        }

        foreach ( array( '_en_US_post_content', '_en_US_post_excerpt', '_en_US_post_title', '_en_US_post_name' ) as $key ) {
            $value = get_post_meta( $page->ID, $key, true );
            if ( ! is_string( $value ) || $value === '' ) { continue; }
            $new = mdo_ula_replace_address( $value );
            if ( $new !== $value ) {
                update_post_meta( $page->ID, $key, $new );
                $updated[] = 'meta:' . $page->ID . ':' . $key;
            }
        }
    }

    update_option( '_mdo_ula_applied', array(
        'version' => MDO_ULA_VERSION,
        'time'    => current_time( 'mysql' ),
        'updated' => $updated,
    ), false );

    return $updated;
}

add_action( 'init', static function() {
    $done = get_option( '_mdo_ula_applied', array() );
    if ( ! is_array( $done ) || ( $done['version'] ?? '' ) !== MDO_ULA_VERSION ) {
        mdo_ula_apply_to_pages();
    }
}, 99 );

add_filter( 'the_content', static function( $content ) {
    return is_string( $content ) ? mdo_ula_replace_address( $content ) : $content;
}, PHP_INT_MAX );

// Final-response safety net so legacy hard-coded text and structured data are also updated.
add_action( 'template_redirect', static function() {
    if ( is_admin() || wp_doing_ajax() || is_feed() ) { return; }
    ob_start( static function( $html ) {
        return mdo_ula_replace_address( (string) $html );
    } );
}, -2000 );
