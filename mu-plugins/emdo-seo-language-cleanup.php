<?php
/**
 * Plugin Name: EMDO SEO Language Cleanup
 * Description: Small SEO fixes for legacy English journal excerpts and duplicate translated product URLs.
 * Version: 2026.08.21.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Historic posts predate the English content layer. Keep the English Journal
 * useful and language-consistent without modifying the original Spanish posts.
 */
add_filter( 'get_the_excerpt', static function ( string $excerpt, $post ): string {
    if ( is_admin() || ! $post instanceof WP_Post || 'post' !== $post->post_type ) return $excerpt;
    if ( ! function_exists( 'mdo_en_is_request' ) || ! mdo_en_is_request() ) return $excerpt;

    $english = array(
        'jamon-iberico' => 'A practical guide to Iberian ham: curing, tag colours, free-range rearing in the dehesa and the factors that shape its quality and character.',
        'aceite-de-oliva-virgen-extra' => 'How extra virgin olive oil is harvested and made in Córdoba, from choosing the right picking moment to milling, extraction, filtration and storage.',
    );

    $slug = (string) $post->post_name;
    return isset( $english[ $slug ] ) ? $english[ $slug ] : $excerpt;
}, PHP_INT_MAX, 2 );

/**
 * A historical English alias for the sobrasada remained crawlable alongside
 * the reviewed English slug. Consolidate authority on the English URL.
 */
add_action( 'template_redirect', static function (): void {
    if ( is_admin() || wp_doing_ajax() || empty( $_SERVER['REQUEST_URI'] ) ) return;
    $path = untrailingslashit( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) );
    if ( '/en/product/sobrasada-de-bellota-100-iberica' !== $path ) return;

    wp_safe_redirect(
        home_url( '/en/product/100-iberian-acorn-fed-sobrasada/' ),
        301,
        'EMDO English product canonical'
    );
    exit;
}, -100 );
