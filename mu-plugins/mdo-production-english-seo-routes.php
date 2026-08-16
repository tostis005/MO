<?php
/**
 * Plugin Name: MDO English SEO Routes
 * Description: Stable English slugs, hreflang and SEO routes without changing Spanish WooCommerce URLs.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * Public URL and internal WordPress route are deliberately separated.
 * The browser keeps the SEO English URL, while TranslatePress receives the
 * already-working native Spanish route prefixed with /en/. This preserves
 * WooCommerce is_shop(), product queries and the existing Spanish storefront.
 */
$GLOBALS['mdoer_public_request_uri'] = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';

function mdoer_prod(): bool {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    if ( '' === $host ) {
        $host = strtolower( (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST ) );
    }
    return in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true );
}
function mdoer_public_uri(): string { return (string) ( $GLOBALS['mdoer_public_request_uri'] ?? ( $_SERVER['REQUEST_URI'] ?? '/' ) ); }
function mdoer_public_path(): string { return (string) wp_parse_url( mdoer_public_uri(), PHP_URL_PATH ); }
function mdoer_en(): bool { return mdoer_prod() && 1 === preg_match( '#^/en(?:/|$)#i', mdoer_public_path() ); }
function mdoer_text( $value ): string {
    $value = html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = (string) preg_replace( '#</?span\b[^>]*>#iu', '', $value );
    return trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $value, true ) ) );
}
function mdoer_en_slug( int $id ): string { return sanitize_title( (string) get_post_meta( $id, '_en_US_post_name', true ) ); }
function mdoer_en_pub( int $id ): bool { return '1' === (string) get_post_meta( $id, '_en_US_published', true ); }

function mdoer_post_row_by_en_slug( string $slug, array $types ): ?array {
    global $wpdb;
    if ( ! $types ) { return null; }
    $in  = implode( ',', array_fill( 0, count( $types ), '%s' ) );
    $sql = $wpdb->prepare(
        "SELECT p.ID,p.post_type,p.post_name,p.post_parent FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_en_US_post_name'
         JOIN {$wpdb->postmeta} u ON u.post_id=p.ID AND u.meta_key='_en_US_published' AND u.meta_value='1'
         WHERE p.post_status='publish' AND p.post_type IN ($in) AND s.meta_value=%s ORDER BY p.ID ASC LIMIT 1",
        ...array_merge( $types, array( $slug ) )
    );
    $row = $wpdb->get_row( $sql, ARRAY_A );
    return is_array( $row ) ? $row : null;
}
function mdoer_post_row_by_native_slug( string $slug, array $types ): ?array {
    global $wpdb;
    if ( ! $types ) { return null; }
    $in  = implode( ',', array_fill( 0, count( $types ), '%s' ) );
    $sql = $wpdb->prepare(
        "SELECT ID,post_type,post_name,post_parent FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ($in) AND post_name=%s ORDER BY ID ASC LIMIT 1",
        ...array_merge( $types, array( $slug ) )
    );
    $row = $wpdb->get_row( $sql, ARRAY_A );
    return is_array( $row ) ? $row : null;
}
function mdoer_term_row( string $taxonomy, string $slug, bool $english = true ): ?array {
    global $wpdb;
    if ( $english ) {
        $sql = $wpdb->prepare(
            "SELECT t.term_id,t.slug FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id
             JOIN {$wpdb->termmeta} tm ON tm.term_id=t.term_id AND tm.meta_key='_en_US_slug'
             JOIN {$wpdb->termmeta} pub ON pub.term_id=t.term_id AND pub.meta_key='_en_US_published' AND pub.meta_value='1'
             WHERE tt.taxonomy=%s AND tm.meta_value=%s LIMIT 1",
            $taxonomy, $slug
        );
    } else {
        $sql = $wpdb->prepare(
            "SELECT t.term_id,t.slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id WHERE tt.taxonomy=%s AND t.slug=%s LIMIT 1",
            $taxonomy, $slug
        );
    }
    $row = $wpdb->get_row( $sql, ARRAY_A );
    return is_array( $row ) ? $row : null;
}
function mdoer_native_page_path( int $id ): string {
    global $wpdb;
    $parts = array(); $guard = 0;
    while ( $id > 0 && $guard++ < 20 ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT post_name,post_parent FROM {$wpdb->posts} WHERE ID=%d LIMIT 1", $id ), ARRAY_A );
        if ( ! is_array( $row ) || '' === (string) $row['post_name'] ) { break; }
        array_unshift( $parts, (string) $row['post_name'] );
        $id = (int) $row['post_parent'];
    }
    return '/' . implode( '/', $parts ) . '/';
}
function mdoer_native_post_url( WP_Post $post ): string {
    $front = (int) get_option( 'page_on_front' );
    $shop  = (int) get_option( 'woocommerce_shop_page_id' );
    if ( $post->ID === $front ) { return home_url( '/' ); }
    if ( $post->ID === $shop ) { return home_url( '/' . $post->post_name . '/' ); }
    if ( 'product' === $post->post_type ) {
        $perms = (array) get_option( 'woocommerce_permalinks', array() );
        $base  = trim( (string) ( $perms['product_base'] ?? '/producto' ), '/' );
        return home_url( '/' . ( $base ?: 'producto' ) . '/' . $post->post_name . '/' );
    }
    if ( 'page' === $post->post_type ) { return home_url( mdoer_native_page_path( $post->ID ) ); }
    return home_url( '/' . $post->post_name . '/' );
}
function mdoer_en_url( WP_Post $post ): string {
    $front = (int) get_option( 'page_on_front' );
    $shop  = (int) get_option( 'woocommerce_shop_page_id' );
    if ( $post->ID === $front ) { return home_url( '/en/' ); }
    if ( $post->ID === $shop ) { return home_url( '/en/shop/' ); }
    if ( ! mdoer_en_pub( $post->ID ) ) { return ''; }
    $slug = mdoer_en_slug( $post->ID );
    if ( '' === $slug ) { return ''; }
    return 'product' === $post->post_type ? home_url( '/en/product/' . $slug . '/' ) : home_url( '/en/' . $slug . '/' );
}
function mdoer_term_en_url( WP_Term $term ): string {
    $slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
    if ( '' === $slug || '1' !== (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) { return ''; }
    $bases = array( 'product_cat' => 'product-category', 'product_tag' => 'product-tag', 'category' => 'category', 'post_tag' => 'tag' );
    return isset( $bases[ $term->taxonomy ] ) ? home_url( '/en/' . $bases[ $term->taxonomy ] . '/' . $slug . '/' ) : '';
}
function mdoer_native_term_url( WP_Term $term ): string {
    $bases = array( 'product_cat' => 'categoria-producto', 'product_tag' => 'etiqueta-producto', 'category' => 'category', 'post_tag' => 'tag' );
    $base = $bases[ $term->taxonomy ] ?? '';
    return $base ? home_url( '/' . $base . '/' . $term->slug . '/' ) : '';
}

/* Translate the public SEO path into the native route BEFORE normal plugins load. */
function mdoer_bootstrap_internal_route(): void {
    if ( ! mdoer_prod() || ! mdoer_en() ) { return; }
    $path = trim( mdoer_public_path(), '/' );
    $internal = '';

    if ( preg_match( '#^en/shop(?:/page/(\d+))?$#i', $path, $m ) ) {
        $internal = '/en/tienda/' . ( ! empty( $m[1] ) ? 'page/' . (int) $m[1] . '/' : '' );
    } elseif ( preg_match( '#^en/product/([^/]+)$#i', $path, $m ) ) {
        $row = mdoer_post_row_by_en_slug( rawurldecode( $m[1] ), array( 'product' ) );
        if ( $row ) { $internal = '/en/producto/' . $row['post_name'] . '/'; }
    } elseif ( preg_match( '#^en/product-category/([^/]+)(?:/page/(\d+))?$#i', $path, $m ) ) {
        $row = mdoer_term_row( 'product_cat', rawurldecode( $m[1] ), true );
        if ( $row ) { $internal = '/en/categoria-producto/' . $row['slug'] . '/' . ( ! empty( $m[2] ) ? 'page/' . (int) $m[2] . '/' : '' ); }
    } elseif ( preg_match( '#^en/product-tag/([^/]+)(?:/page/(\d+))?$#i', $path, $m ) ) {
        $row = mdoer_term_row( 'product_tag', rawurldecode( $m[1] ), true );
        if ( $row ) { $internal = '/en/etiqueta-producto/' . $row['slug'] . '/' . ( ! empty( $m[2] ) ? 'page/' . (int) $m[2] . '/' : '' ); }
    } elseif ( preg_match( '#^en/([^/]+)$#i', $path, $m ) ) {
        $row = mdoer_post_row_by_en_slug( rawurldecode( $m[1] ), array( 'page', 'post' ) );
        if ( $row ) { $internal = '/en/' . $row['post_name'] . '/'; }
    }

    if ( '' === $internal ) { return; }
    $query = (string) wp_parse_url( mdoer_public_uri(), PHP_URL_QUERY );
    $_SERVER['REQUEST_URI'] = $internal . ( '' !== $query ? '?' . $query : '' );
    $GLOBALS['mdoer_internal_request_uri'] = $_SERVER['REQUEST_URI'];
}
mdoer_bootstrap_internal_route();

add_filter( 'redirect_canonical', static fn( $redirect ) => mdoer_en() ? false : $redirect, PHP_INT_MAX );
foreach ( array( 'page_link', 'post_link', 'post_type_link' ) as $hook ) {
    add_filter( $hook, static function( $url, $object = null ) {
        if ( ! mdoer_en() ) { return $url; }
        $post = $object instanceof WP_Post ? $object : get_post( (int) $object );
        if ( ! $post instanceof WP_Post ) { return $url; }
        return mdoer_en_url( $post ) ?: $url;
    }, PHP_INT_MAX, 2 );
}
add_filter( 'term_link', static function( $url, $term ) {
    if ( ! mdoer_en() || ! $term instanceof WP_Term ) { return $url; }
    return mdoer_term_en_url( $term ) ?: $url;
}, PHP_INT_MAX, 2 );

function mdoer_preferred(): array {
    if ( is_front_page() ) { return array( 'es' => home_url( '/' ), 'en' => home_url( '/en/' ) ); }
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        $post = get_post( (int) get_option( 'woocommerce_shop_page_id' ) );
        return $post instanceof WP_Post ? array( 'es' => mdoer_native_post_url( $post ), 'en' => home_url( '/en/shop/' ) ) : array( 'es' => '', 'en' => '' );
    }
    $object = get_queried_object();
    if ( is_singular() && $object instanceof WP_Post ) { return array( 'es' => mdoer_native_post_url( $object ), 'en' => mdoer_en_url( $object ) ); }
    if ( ( is_category() || is_tag() || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) && $object instanceof WP_Term ) {
        return array( 'es' => mdoer_native_term_url( $object ), 'en' => mdoer_term_en_url( $object ) );
    }
    return array( 'es' => '', 'en' => '' );
}

add_filter( 'aioseo_canonical_url', static function( $url ) { $x = mdoer_preferred(); return mdoer_en() && $x['en'] ? $x['en'] : $url; }, PHP_INT_MAX );
add_filter( 'get_canonical_url', static function( $url ) { $x = mdoer_preferred(); return mdoer_en() && $x['en'] ? $x['en'] : $url; }, PHP_INT_MAX );
add_filter( 'aioseo_title', static function( $title ) {
    if ( ! mdoer_en() ) { return $title; }
    if ( function_exists( 'is_shop' ) && is_shop() ) { return 'Shop | El Mercado de Origen'; }
    $object = get_queried_object();
    if ( $object instanceof WP_Post ) {
        $value = mdoer_text( get_post_meta( $object->ID, '_en_US_post_title', true ) );
        if ( $value && ! in_array( strtolower( $value ), array( 'home', 'inicio' ), true ) ) { return false !== stripos( $value, 'El Mercado de Origen' ) ? $value : $value . ' | El Mercado de Origen'; }
    } elseif ( $object instanceof WP_Term ) {
        $value = mdoer_text( get_term_meta( $object->term_id, '_en_US_name', true ) );
        if ( $value ) { return $value . ' | El Mercado de Origen'; }
    }
    return $title;
}, PHP_INT_MAX );
add_filter( 'aioseo_description', static function( $description ) {
    if ( ! mdoer_en() ) { return $description; }
    if ( function_exists( 'is_shop' ) && is_shop() ) { return 'Shop products selected for their origin, quality and the producers behind them at El Mercado de Origen.'; }
    $object = get_queried_object();
    if ( $object instanceof WP_Post ) {
        $value = mdoer_text( get_post_meta( $object->ID, '_en_US_post_excerpt', true ) );
        if ( ! $value ) { $value = mdoer_text( strip_shortcodes( (string) get_post_meta( $object->ID, '_en_US_post_content', true ) ) ); }
        if ( $value ) { return wp_html_excerpt( $value, 155, '…' ); }
    } elseif ( $object instanceof WP_Term ) {
        $value = mdoer_text( get_term_meta( $object->term_id, '_en_US_description', true ) );
        if ( $value ) { return wp_html_excerpt( $value, 155, '…' ); }
    }
    return $description;
}, PHP_INT_MAX );

add_action( 'wp_head', static function(): void {
    $x = mdoer_preferred();
    if ( ! $x['es'] || ! $x['en'] ) { return; }
    echo '<link rel="alternate" hreflang="es-ES" href="' . esc_url( $x['es'] ) . '" data-mdo-hreflang="1">' . "\n";
    echo '<link rel="alternate" hreflang="en" href="' . esc_url( $x['en'] ) . '" data-mdo-hreflang="1">' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $x['es'] ) . '" data-mdo-hreflang="1">' . "\n";
}, 2 );
add_action( 'wp_head', static function(): void {
    echo '<style id="mdo-hide-lang-ui">.trp-language-switcher-container,.trp-language-switcher,#trp-floater-ls,.trp-floater-ls,.elmercado-falang-switcher,.falang-language-switcher,[class*="falang-language-switcher"],li.menu-item-language,li.lang-item{display:none!important;visibility:hidden!important;pointer-events:none!important}</style>';
}, PHP_INT_MAX );

function mdoer_rewrite_url( string $url ): string {
    if ( '' === $url || str_starts_with( $url, '#' ) || preg_match( '#^(?:mailto:|tel:|javascript:)#i', $url ) ) { return $url; }
    $parts = wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    if ( false === $parts ) { return $url; }
    if ( isset( $parts['host'] ) && ! in_array( strtolower( $parts['host'] ), array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) { return $url; }
    $path = trailingslashit( $parts['path'] ?? '/' ); $new = '';
    if ( preg_match( '#^/en/tienda(?:/page/(\d+))?/$#i', $path, $m ) ) {
        $new = '/en/shop/' . ( ! empty( $m[1] ) ? 'page/' . (int) $m[1] . '/' : '' );
    } elseif ( preg_match( '#^/en/(?:producto|product)/([^/]+)/$#i', $path, $m ) ) {
        $row = mdoer_post_row_by_native_slug( rawurldecode( $m[1] ), array( 'product' ) );
        if ( $row ) { $slug = mdoer_en_slug( (int) $row['ID'] ); if ( $slug ) { $new = '/en/product/' . $slug . '/'; } }
    } elseif ( preg_match( '#^/en/(?:categoria-producto|product-category)/([^/]+)/$#i', $path, $m ) ) {
        $row = mdoer_term_row( 'product_cat', rawurldecode( $m[1] ), false );
        if ( $row ) { $slug = sanitize_title( (string) get_term_meta( (int) $row['term_id'], '_en_US_slug', true ) ); if ( $slug ) { $new = '/en/product-category/' . $slug . '/'; } }
    } elseif ( preg_match( '#^/en/([^/]+)/$#i', $path, $m ) ) {
        $row = mdoer_post_row_by_native_slug( rawurldecode( $m[1] ), array( 'page', 'post' ) );
        if ( $row ) { $post = get_post( (int) $row['ID'] ); if ( $post instanceof WP_Post ) { $new = (string) wp_parse_url( mdoer_en_url( $post ), PHP_URL_PATH ); } }
    }
    if ( '' === $new ) { return $url; }
    $out = isset( $parts['host'] ) || isset( $parts['scheme'] ) ? home_url( $new ) : $new;
    if ( ! empty( $parts['query'] ) ) { $out .= '?' . $parts['query']; }
    if ( ! empty( $parts['fragment'] ) ) { $out .= '#' . $parts['fragment']; }
    return $out;
}
function mdoer_html( string $html ): string {
    if ( ! mdoer_prod() ) { return $html; }
    $html = (string) preg_replace_callback( "#<link\\b[^>]*\\bhreflang=(\"|')[^\"']+\\1[^>]*>#iu", static fn( $m ) => str_contains( $m[0], 'data-mdo-hreflang=' ) ? $m[0] : '', $html );
    if ( ! mdoer_en() ) { return $html; }
    $html = (string) preg_replace_callback( "#\\b(href|action|content)=(\"|')([^\"']+)\\2#iu", static fn( $m ) => $m[1] . '=' . $m[2] . esc_attr( mdoer_rewrite_url( $m[3] ) ) . $m[2], $html );
    $x = mdoer_preferred();
    if ( $x['en'] ) {
        $html = (string) preg_replace( "#<link\\b[^>]*\\brel=(\"|')canonical\\1[^>]*>#iu", '', $html );
        $html = str_ireplace( '</head>', '<link rel="canonical" href="' . esc_url( $x['en'] ) . '" data-mdo-canonical="1">' . "\n</head>", $html );
    }
    return $html;
}
add_action( 'template_redirect', static function(): void {
    if ( ! is_admin() && ! wp_doing_ajax() && ! is_feed() && ! is_robots() ) { ob_start( 'mdoer_html' ); }
}, -900 );

/* Redirect only legacy PUBLIC English URLs, never the internal translated route. */
add_action( 'template_redirect', static function(): void {
    if ( ! mdoer_en() || wp_doing_ajax() ) { return; }
    $public = trailingslashit( mdoer_public_path() );
    $x = mdoer_preferred();
    if ( ! $x['en'] ) { return; }
    $wanted = trailingslashit( (string) wp_parse_url( $x['en'], PHP_URL_PATH ) );
    if ( $public !== $wanted && ( '/en/tienda/' === $public || str_starts_with( $public, '/en/producto/' ) ) ) {
        wp_safe_redirect( $x['en'], 301, 'MDO-English-Routing' ); exit;
    }
}, -1000 );

function mdoer_sitemap(): array {
    global $wpdb;
    $urls = array( home_url( '/en/' ), home_url( '/en/shop/' ) );
    $ids = $wpdb->get_col(
        "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_en_US_post_name' AND s.meta_value<>''
         JOIN {$wpdb->postmeta} u ON u.post_id=p.ID AND u.meta_key='_en_US_published' AND u.meta_value='1'
         WHERE p.post_status='publish' AND p.post_type IN ('page','post','product')"
    );
    $skip = array_map( 'intval', array( get_option( 'woocommerce_cart_page_id' ), get_option( 'woocommerce_checkout_page_id' ), get_option( 'woocommerce_myaccount_page_id' ) ) );
    foreach ( $ids as $id ) {
        $post = get_post( (int) $id );
        if ( ! $post instanceof WP_Post || in_array( (int) $id, $skip, true ) || (int) $id === (int) get_option( 'woocommerce_shop_page_id' ) ) { continue; }
        $url = mdoer_en_url( $post ); if ( $url ) { $urls[] = $url; }
    }
    $terms = get_terms( array( 'taxonomy' => array( 'product_cat', 'category' ), 'hide_empty' => false ) );
    if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { if ( $term instanceof WP_Term ) { $url = mdoer_term_en_url( $term ); if ( $url ) { $urls[] = $url; } } } }
    return array_values( array_unique( $urls ) );
}
add_action( 'template_redirect', static function(): void {
    if ( ! mdoer_prod() || '/english-sitemap.xml' !== mdoer_public_path() ) { return; }
    nocache_headers(); header( 'Content-Type: application/xml; charset=UTF-8' );
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ( mdoer_sitemap() as $url ) { echo '<url><loc>' . esc_url( $url ) . '</loc></url>'; }
    echo '</urlset>'; exit;
}, -2000 );
add_filter( 'aioseo_sitemap_indexes', static function( $indexes ) {
    if ( mdoer_prod() ) { $indexes[] = array( 'loc' => home_url( '/english-sitemap.xml' ), 'lastmod' => gmdate( DATE_W3C ), 'count' => count( mdoer_sitemap() ) ); }
    return $indexes;
}, PHP_INT_MAX );
add_filter( 'robots_txt', static function( $output, $public ) {
    if ( ! $public || ! mdoer_prod() ) { return $output; }
    $line = 'Sitemap: ' . home_url( '/english-sitemap.xml' );
    return str_contains( $output, $line ) ? $output : rtrim( $output ) . "\n" . $line . "\n";
}, PHP_INT_MAX, 2 );
