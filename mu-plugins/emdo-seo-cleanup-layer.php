<?php
/**
 * Plugin Name: EMDO SEO Cleanup Layer
 * Description: Legacy redirects, functional-page index controls and a clean public page sitemap.
 * Version: 2026.08.21.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_cleanup_path(): string {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return '/' . ltrim( $path !== '' ? $path : '/', '/' );
}

function emdo_cleanup_is_functional_page_request(): bool {
    return (bool) preg_match( '#^/(?:en/)?(?:store-manager|affiliates)(?:/|$)#i', emdo_cleanup_path() );
}

function emdo_cleanup_excluded_page_ids(): array {
    $ids = array();

    if ( function_exists( 'wc_get_page_id' ) ) {
        foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
            $id = (int) wc_get_page_id( $wc_page );
            if ( $id > 0 ) $ids[] = $id;
        }
    }

    foreach ( array(
        'carrito',
        'finalizar-compra',
        'mi-cuenta',
        'store-manager',
        'affiliates',
        'el-mercado-de-origen',
        'condiciones-especiales',
    ) as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page instanceof WP_Post ) $ids[] = (int) $page->ID;
    }

    return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

function emdo_cleanup_english_page_url( WP_Post $page ): string {
    if ( function_exists( 'mdoer_en_url' ) ) {
        $url = mdoer_en_url( $page );
        return is_string( $url ) ? $url : '';
    }

    $front = (int) get_option( 'page_on_front' );
    $shop  = (int) get_option( 'woocommerce_shop_page_id' );
    if ( (int) $page->ID === $front ) return home_url( '/en/' );
    if ( (int) $page->ID === $shop ) return home_url( '/en/shop/' );
    if ( '1' !== (string) get_post_meta( $page->ID, '_en_US_published', true ) ) return '';

    $slug = sanitize_title( (string) get_post_meta( $page->ID, '_en_US_post_name', true ) );
    return $slug !== '' ? home_url( '/en/' . $slug . '/' ) : '';
}

function emdo_cleanup_xml( string $value ): string {
    return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

function emdo_cleanup_render_sitemap_url( string $loc, string $lastmod, array $alternates ): void {
    echo "  <url>\n";
    echo '    <loc>' . emdo_cleanup_xml( $loc ) . "</loc>\n";
    if ( $lastmod !== '' ) echo '    <lastmod>' . emdo_cleanup_xml( $lastmod ) . "</lastmod>\n";
    foreach ( $alternates as $lang => $href ) {
        echo '    <xhtml:link rel="alternate" hreflang="' . emdo_cleanup_xml( (string) $lang ) . '" href="' . emdo_cleanup_xml( (string) $href ) . '" />' . "\n";
    }
    echo "  </url>\n";
}

function emdo_cleanup_serve_page_sitemap(): void {
    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );

    $pages = get_posts( array(
        'post_type'        => 'page',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'post__not_in'     => emdo_cleanup_excluded_page_ids(),
        'has_password'     => false,
        'suppress_filters' => true,
    ) );

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    foreach ( $pages as $page ) {
        if ( ! $page instanceof WP_Post || $page->post_password !== '' ) continue;
        $es = get_permalink( $page );
        if ( ! is_string( $es ) || $es === '' ) continue;

        $en = emdo_cleanup_english_page_url( $page );
        $timestamp = (int) get_post_modified_time( 'U', true, $page );
        $lastmod = $timestamp > 0 ? gmdate( DATE_W3C, $timestamp ) : '';
        $alternates = $en !== '' ? array( 'es' => $es, 'en' => $en, 'x-default' => $es ) : array();
        emdo_cleanup_render_sitemap_url( esc_url_raw( $es ), $lastmod, $alternates );
        if ( $en !== '' && $en !== $es ) emdo_cleanup_render_sitemap_url( esc_url_raw( $en ), $lastmod, $alternates );
    }

    echo '</urlset>';
    exit;
}

// Serve the canonical page sitemap before the broader dynamic sitemap plugin.
add_action( 'parse_request', static function (): void {
    $path = (string) wp_parse_url( emdo_cleanup_path(), PHP_URL_PATH );
    if ( basename( untrailingslashit( $path ) ) === 'mdo-sitemap-pages.xml' ) {
        emdo_cleanup_serve_page_sitemap();
    }
}, -10000 );

// Consolidate obsolete public pages into their maintained equivalents.
add_action( 'template_redirect', static function (): void {
    $path = untrailingslashit( emdo_cleanup_path() );
    $redirects = array(
        '/el-mercado-de-origen'  => '/quienes-somos/',
        '/condiciones-especiales' => '/envios/',
    );
    if ( isset( $redirects[ $path ] ) ) {
        wp_safe_redirect( home_url( $redirects[ $path ] ), 301, 'EMDO SEO Cleanup' );
        exit;
    }

    if ( emdo_cleanup_is_functional_page_request() && ! headers_sent() ) {
        header( 'X-Robots-Tag: noindex, follow', true );
    }
}, -2000 );

// AIOSEO robots control for functional interfaces that should never rank.
add_filter( 'aioseo_robots_meta', static function ( $attributes ) {
    if ( ! is_array( $attributes ) || ! emdo_cleanup_is_functional_page_request() ) return $attributes;
    $attributes['noindex'] = 'noindex';
    $attributes['nofollow'] = '';
    return $attributes;
}, PHP_INT_MAX );

// Core WordPress fallback in case another SEO layer is unavailable.
add_filter( 'wp_robots', static function ( array $robots ): array {
    if ( emdo_cleanup_is_functional_page_request() ) $robots['noindex'] = true;
    return $robots;
}, PHP_INT_MAX );
