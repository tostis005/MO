<?php
/**
 * Plugin Name: EMDO Category SEO Description
 * Description: Renders the persisted ES/EN description on public product-category archives.
 * Version: 2026.08.21.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_category_seo_is_english(): bool {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

function emdo_category_seo_clean_html( $value ): string {
    $value = (string) $value;
    $charset = get_bloginfo( 'charset' ) ?: 'UTF-8';
    for ( $i = 0; $i < 2; $i++ ) {
        $decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, $charset );
        if ( $decoded === $value ) break;
        $value = $decoded;
    }
    $value = trim( wp_kses_post( $value ) );
    if ( $value === '' ) return '';

    if ( ! preg_match( '/<\s*(?:p|ul|ol|div|h[2-6]|blockquote)\b/i', $value ) ) {
        $value = wpautop( esc_html( wp_strip_all_tags( $value ) ) );
    }
    return $value;
}

function emdo_category_seo_current_description(): string {
    if ( ! function_exists( 'is_product_category' ) || ! is_product_category() || is_paged() ) return '';
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term || $term->taxonomy !== 'product_cat' ) return '';

    if ( emdo_category_seo_is_english() ) {
        if ( (string) get_term_meta( $term->term_id, '_en_US_published', true ) !== '1' ) return '';
        return emdo_category_seo_clean_html( get_term_meta( $term->term_id, '_en_US_description', true ) );
    }

    return emdo_category_seo_clean_html( $term->description );
}

function emdo_category_seo_is_long_description( string $html ): bool {
    $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
    $length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain, 'UTF-8' ) : strlen( $plain );
    return $length > 900;
}

function emdo_category_seo_render_description( string $position ): void {
    $html = emdo_category_seo_current_description();
    if ( $html === '' ) return;
    $long = emdo_category_seo_is_long_description( $html );
    if ( ( $position === 'above' && $long ) || ( $position === 'below' && ! $long ) ) return;

    echo '<section class="term-description emdo-seo-category-description emdo-seo-category-description--' . esc_attr( $position ) . '" data-emdo-category-description="1" data-emdo-category-description-position="' . esc_attr( $position ) . '" aria-label="' . esc_attr( emdo_category_seo_is_english() ? 'Category description' : 'Descripción de la categoría' ) . '">';
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized by emdo_category_seo_clean_html().
    echo '</section>';
}

add_action( 'woocommerce_before_shop_loop', static function (): void {
    emdo_category_seo_render_description( 'above' );
}, 2 );

add_action( 'woocommerce_after_shop_loop', static function (): void {
    emdo_category_seo_render_description( 'below' );
}, 20 );

add_action( 'wp_head', static function (): void {
    if ( ! function_exists( 'is_product_category' ) || ! is_product_category() || is_paged() ) return;
    echo '<style id="emdo-category-seo-description-css">.emdo-seo-category-description{max-width:920px;line-height:1.65}.emdo-seo-category-description--above{margin:0 0 24px}.emdo-seo-category-description--below{margin:32px 0 8px}.emdo-seo-category-description p:last-child{margin-bottom:0}</style>';
}, 50 );
