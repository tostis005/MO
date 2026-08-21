<?php
/**
 * Plugin Name: EMDO SEO Cleanup Layer
 * Description: Legacy redirects, functional-page index controls and clean public page/product sitemaps.
 * Version: 2026.08.21.2
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

function emdo_cleanup_status_flag_is_on( $value ): bool {
    if ( is_bool( $value ) ) return $value;
    if ( is_int( $value ) || is_float( $value ) ) return 0 !== (int) $value;
    if ( is_string( $value ) ) {
        $normalized = strtolower( trim( $value ) );
        return ! in_array( $normalized, array( '', '0', 'no', 'false', 'off', 'none' ), true );
    }
    return ! empty( $value );
}

function emdo_cleanup_vendor_is_disabled( int $user_id ): bool {
    if ( $user_id <= 0 ) return false;
    if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) ) {
        return (bool) elmercado_wcfm_vendor_is_disabled_010210( $user_id );
    }

    $user = get_userdata( $user_id );
    if ( ! $user instanceof WP_User ) return false;
    if ( in_array( 'disable_vendor', array_map( 'sanitize_key', (array) $user->roles ), true ) ) return true;
    if ( emdo_cleanup_status_flag_is_on( get_user_meta( $user_id, '_disable_vendor', true ) ) ) return true;
    return emdo_cleanup_status_flag_is_on( get_user_meta( $user_id, '_wcfm_store_offline', true ) );
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

function emdo_cleanup_english_product_url( WP_Post $product ): string {
    if ( function_exists( 'mdoer_en_url' ) ) {
        $url = mdoer_en_url( $product );
        return is_string( $url ) ? $url : '';
    }
    if ( '1' !== (string) get_post_meta( $product->ID, '_en_US_published', true ) ) return '';
    $slug = sanitize_title( (string) get_post_meta( $product->ID, '_en_US_post_name', true ) );
    return $slug !== '' ? home_url( '/en/product/' . $slug . '/' ) : '';
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

function emdo_cleanup_sitemap_open(): void {
    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
}

function emdo_cleanup_sitemap_close(): void {
    echo '</urlset>';
    exit;
}

function emdo_cleanup_render_language_pair( WP_Post $post, string $es, string $en ): void {
    $es = esc_url_raw( $es );
    $en = esc_url_raw( $en );
    if ( $es === '' ) return;

    $timestamp = (int) get_post_modified_time( 'U', true, $post );
    $lastmod = $timestamp > 0 ? gmdate( DATE_W3C, $timestamp ) : '';
    $alternates = $en !== '' ? array( 'es' => $es, 'en' => $en, 'x-default' => $es ) : array();
    emdo_cleanup_render_sitemap_url( $es, $lastmod, $alternates );
    if ( $en !== '' && $en !== $es ) emdo_cleanup_render_sitemap_url( $en, $lastmod, $alternates );
}

function emdo_cleanup_serve_page_sitemap(): void {
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

    emdo_cleanup_sitemap_open();
    foreach ( $pages as $page ) {
        if ( ! $page instanceof WP_Post || $page->post_password !== '' ) continue;
        $es = get_permalink( $page );
        if ( ! is_string( $es ) || $es === '' ) continue;
        emdo_cleanup_render_language_pair( $page, $es, emdo_cleanup_english_page_url( $page ) );
    }
    emdo_cleanup_sitemap_close();
}

/**
 * Select published products directly from wp_posts so WooCommerce's global
 * "hide out of stock" query setting cannot silently remove valid SEO URLs.
 * We then enforce our own public rules: active vendor + non-hidden product.
 */
function emdo_cleanup_eligible_product_ids(): array {
    global $wpdb;
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s AND post_password=%s ORDER BY ID ASC",
            'product',
            'publish',
            ''
        )
    );

    $eligible = array();
    foreach ( array_map( 'intval', (array) $ids ) as $id ) {
        $post = get_post( $id );
        if ( ! $post instanceof WP_Post || emdo_cleanup_vendor_is_disabled( (int) $post->post_author ) ) continue;

        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $id );
            if ( ! $product || 'hidden' === (string) $product->get_catalog_visibility() ) continue;
        }

        $eligible[] = $id;
    }
    return array_values( array_unique( $eligible ) );
}

function emdo_cleanup_serve_product_sitemap(): void {
    emdo_cleanup_sitemap_open();
    foreach ( emdo_cleanup_eligible_product_ids() as $id ) {
        $post = get_post( $id );
        if ( ! $post instanceof WP_Post ) continue;
        $es = get_permalink( $post );
        if ( ! is_string( $es ) || $es === '' ) continue;
        emdo_cleanup_render_language_pair( $post, $es, emdo_cleanup_english_product_url( $post ) );
    }
    emdo_cleanup_sitemap_close();
}

// Serve canonical page/product sitemaps before the broader dynamic sitemap plugin.
add_action( 'parse_request', static function (): void {
    $path = (string) wp_parse_url( emdo_cleanup_path(), PHP_URL_PATH );
    $file = basename( untrailingslashit( $path ) );
    if ( $file === 'mdo-sitemap-pages.xml' ) emdo_cleanup_serve_page_sitemap();
    if ( $file === 'mdo-sitemap-products.xml' ) emdo_cleanup_serve_product_sitemap();
}, -10000 );

// Consolidate obsolete public pages into their maintained equivalents.
add_action( 'template_redirect', static function (): void {
    $path = untrailingslashit( emdo_cleanup_path() );
    $redirects = array(
        '/el-mercado-de-origen'   => '/quienes-somos/',
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
