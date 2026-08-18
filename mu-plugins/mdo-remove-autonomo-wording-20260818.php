<?php
/**
 * Plugin Name: MDO Remove Autónomo Wording
 * Description: Removes the explicit autónomo/self-employed wording from public legal identity text while keeping operator identity, NIF and contact details intact.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const MDO_RAW_VERSION = '2026-08-18-1';

function mdo_raw_replace_wording( string $text ): string {
    $replacements = array(
        ', trabajador autónomo, NIF' => ', NIF',
        ', trabajador autónomo, con NIF' => ', con NIF',
        ' trabajador autónomo,' => ' ',
        ', a self-employed individual in Spain, tax ID/NIF' => ', tax ID/NIF',
        ', a self-employed individual in Spain, with tax ID/NIF' => ', with tax ID/NIF',
        ' a self-employed individual in Spain,' => ' ',
    );
    return str_ireplace( array_keys( $replacements ), array_values( $replacements ), $text );
}

function mdo_raw_clean_stored_pages(): array {
    $changed = array();
    $ids = get_posts( array(
        'post_type'        => 'page',
        'post_status'      => 'any',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ) );

    foreach ( array_map( 'intval', (array) $ids ) as $id ) {
        $post = get_post( $id );
        if ( ! $post instanceof WP_Post ) { continue; }

        $new_content = mdo_raw_replace_wording( (string) $post->post_content );
        $new_excerpt = mdo_raw_replace_wording( (string) $post->post_excerpt );
        $post_update = array( 'ID' => $id );
        $needs_update = false;

        if ( $new_content !== (string) $post->post_content ) {
            $post_update['post_content'] = $new_content;
            $needs_update = true;
        }
        if ( $new_excerpt !== (string) $post->post_excerpt ) {
            $post_update['post_excerpt'] = $new_excerpt;
            $needs_update = true;
        }
        if ( $needs_update ) {
            wp_update_post( wp_slash( $post_update ) );
            $changed[] = $id;
        }

        foreach ( array( '_en_US_post_content', '_en_US_post_excerpt' ) as $meta_key ) {
            $value = (string) get_post_meta( $id, $meta_key, true );
            if ( '' === $value ) { continue; }
            $new_value = mdo_raw_replace_wording( $value );
            if ( $new_value !== $value ) {
                update_post_meta( $id, $meta_key, $new_value );
                if ( ! in_array( $id, $changed, true ) ) { $changed[] = $id; }
            }
        }
    }

    update_option( '_mdo_raw_cleanup_20260818', array(
        'version' => MDO_RAW_VERSION,
        'time'    => current_time( 'mysql' ),
        'changed' => $changed,
    ), false );

    return $changed;
}

add_action( 'init', static function() {
    $done = get_option( '_mdo_raw_cleanup_20260818', array() );
    if ( ! is_array( $done ) || ( $done['version'] ?? '' ) !== MDO_RAW_VERSION ) {
        mdo_raw_clean_stored_pages();
    }
}, 30 );

add_filter( 'the_content', static function( $content ) {
    return is_string( $content ) ? mdo_raw_replace_wording( $content ) : $content;
}, PHP_INT_MAX );

add_filter( 'get_the_excerpt', static function( $excerpt ) {
    return is_string( $excerpt ) ? mdo_raw_replace_wording( $excerpt ) : $excerpt;
}, PHP_INT_MAX );

add_action( 'template_redirect', static function() {
    if ( is_admin() || wp_doing_ajax() || is_feed() ) { return; }
    ob_start( static function( $html ) {
        return mdo_raw_replace_wording( (string) $html );
    } );
}, -2000 );
