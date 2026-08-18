<?php
/**
 * MDO English Island Router.
 * Keeps Spanish WordPress/WooCommerce objects untouched while exposing
 * their reviewed English aliases stored in _en_US_* metadata.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_en_is_request(): bool {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) { return false; }
    $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
    return is_string( $path ) && ( $path === '/en' || strpos( $path, '/en/' ) === 0 );
}

function mdo_en_segments(): array {
    if ( ! mdo_en_is_request() ) { return array(); }
    $path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
    $relative = trim( preg_replace( '#^/en/?#', '', $path ), '/' );
    if ( $relative === '' ) { return array(); }
    return array_values( array_filter( array_map( 'sanitize_title', explode( '/', $relative ) ), 'strlen' ) );
}

function mdo_en_root(): string {
    return rtrim( (string) get_option( 'home' ), '/' );
}

function mdo_en_slug_for_post( int $post_id ): string {
    if ( (string) get_post_meta( $post_id, '_en_US_published', true ) !== '1' ) { return ''; }
    return sanitize_title( wp_strip_all_tags( (string) get_post_meta( $post_id, '_en_US_post_name', true ) ) );
}

function mdo_en_find_by_slug( string $post_type, string $slug ): int {
    $slug = sanitize_title( $slug );
    if ( $slug === '' ) { return 0; }
    $ids = get_posts( array(
        'post_type'        => $post_type,
        'post_status'      => 'publish',
        'posts_per_page'   => 2,
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'suppress_filters' => true,
        'meta_query'       => array(
            'relation' => 'AND',
            array( 'key' => '_en_US_post_name', 'value' => $slug, 'compare' => '=' ),
            array( 'key' => '_en_US_published', 'value' => '1', 'compare' => '=' ),
        ),
    ) );
    return count( $ids ) === 1 ? (int) $ids[0] : 0;
}

function mdo_en_page_url( int $post_id ): string {
    $slug = mdo_en_slug_for_post( $post_id );
    return $slug === '' ? '' : mdo_en_root() . '/en/' . rawurlencode( $slug ) . '/';
}

function mdo_en_product_url( int $post_id ): string {
    $slug = mdo_en_slug_for_post( $post_id );
    return $slug === '' ? '' : mdo_en_root() . '/en/product/' . rawurlencode( $slug ) . '/';
}

function mdo_en_native_page_from_slug( string $slug ) {
    $page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );
    return ( $page instanceof WP_Post && $page->post_status === 'publish' ) ? $page : null;
}

function mdo_en_native_product_from_slug( string $slug ) {
    $product = get_page_by_path( sanitize_title( $slug ), OBJECT, 'product' );
    return ( $product instanceof WP_Post && $product->post_status === 'publish' ) ? $product : null;
}

function mdo_en_translate_internal_url( string $url ): string {
    if ( $url === '' || strpos( $url, '#' ) === 0 || stripos( $url, 'mailto:' ) === 0 || stripos( $url, 'tel:' ) === 0 || stripos( $url, 'javascript:' ) === 0 ) { return $url; }
    $root = mdo_en_root();
    $root_host = wp_parse_url( $root, PHP_URL_HOST );
    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( $host && $root_host && strtolower( (string) $host ) !== strtolower( (string) $root_host ) ) { return $url; }

    $query = wp_parse_url( $url, PHP_URL_QUERY );
    $fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );
    $suffix = '';
    if ( is_string( $query ) && $query !== '' ) { $suffix .= '?' . $query; }
    if ( is_string( $fragment ) && $fragment !== '' ) { $suffix .= '#' . $fragment; }

    $path = (string) wp_parse_url( $url, PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
    if ( ! $parts ) { return $root . '/en/' . $suffix; }

    $is_en = strtolower( $parts[0] ) === 'en';
    if ( $is_en ) { array_shift( $parts ); }
    if ( ! $parts ) { return $root . '/en/' . $suffix; }

    if ( count( $parts ) === 1 ) {
        $english_id = mdo_en_find_by_slug( 'page', $parts[0] );
        if ( $english_id ) { return mdo_en_page_url( $english_id ) . $suffix; }
        $native = mdo_en_native_page_from_slug( $parts[0] );
        if ( $native ) {
            $translated = mdo_en_page_url( (int) $native->ID );
            if ( $translated !== '' ) { return $translated . $suffix; }
        }
    }

    if ( count( $parts ) === 2 && in_array( sanitize_title( $parts[0] ), array( 'producto', 'product' ), true ) ) {
        $english_id = mdo_en_find_by_slug( 'product', $parts[1] );
        if ( $english_id ) { return mdo_en_product_url( $english_id ) . $suffix; }
        $native = mdo_en_native_product_from_slug( $parts[1] );
        if ( $native ) {
            $translated = mdo_en_product_url( (int) $native->ID );
            if ( $translated !== '' ) { return $translated . $suffix; }
        }
    }

    return $url;
}

add_action( 'parse_request', static function ( WP $wp ): void {
    $parts = mdo_en_segments();
    if ( ! $parts ) { return; }

    if ( count( $parts ) === 2 && $parts[0] === 'product' ) {
        $id = mdo_en_find_by_slug( 'product', $parts[1] );
        if ( ! $id ) { return; }
        foreach ( array( 'error', 'name', 'pagename', 'page_id', 'attachment', 'product', 'product_cat', 'product_tag' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['p'] = $id;
        return;
    }

    if ( count( $parts ) !== 1 ) { return; }
    $id = mdo_en_find_by_slug( 'page', $parts[0] );
    if ( ! $id ) { return; }
    foreach ( array( 'error', 'name', 'pagename', 'page_id', 'attachment', 'p' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
    if ( $id === (int) get_option( 'woocommerce_shop_page_id' ) ) {
        $wp->query_vars['post_type'] = 'product';
    } else {
        unset( $wp->query_vars['post_type'] );
        $wp->query_vars['page_id'] = $id;
    }
}, PHP_INT_MAX );

add_filter( 'page_link', static function ( string $url, int $post_id ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    $translated = mdo_en_page_url( $post_id );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'post_type_link', static function ( string $url, WP_Post $post ): string {
    if ( ! mdo_en_is_request() || $post->post_type !== 'product' ) { return $url; }
    $translated = mdo_en_product_url( (int) $post->ID );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'wp_nav_menu_objects', static function ( array $items ): array {
    if ( ! mdo_en_is_request() ) { return $items; }
    foreach ( $items as $item ) {
        if ( ! $item instanceof WP_Post ) { continue; }
        $translated = '';
        if ( $item->type === 'post_type' && $item->object === 'page' ) {
            $translated = mdo_en_page_url( (int) $item->object_id );
        } elseif ( $item->type === 'post_type' && $item->object === 'product' ) {
            $translated = mdo_en_product_url( (int) $item->object_id );
        } elseif ( ! empty( $item->url ) ) {
            $translated = mdo_en_translate_internal_url( (string) $item->url );
        }
        if ( $translated !== '' ) { $item->url = $translated; }
    }
    return $items;
}, PHP_INT_MAX );

add_filter( 'woocommerce_get_cart_url', static function ( string $url ): string {
    if ( ! mdo_en_is_request() || ! function_exists( 'wc_get_page_id' ) ) { return $url; }
    $translated = mdo_en_page_url( (int) wc_get_page_id( 'cart' ) );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX );

add_filter( 'woocommerce_get_checkout_url', static function ( string $url ): string {
    if ( ! mdo_en_is_request() || ! function_exists( 'wc_get_page_id' ) ) { return $url; }
    $translated = mdo_en_page_url( (int) wc_get_page_id( 'checkout' ) );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX );

add_filter( 'woocommerce_get_myaccount_page_permalink', static function ( string $url ): string {
    if ( ! mdo_en_is_request() || ! function_exists( 'wc_get_page_id' ) ) { return $url; }
    $translated = mdo_en_page_url( (int) wc_get_page_id( 'myaccount' ) );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX );

add_filter( 'redirect_canonical', static function ( $redirect ) {
    if ( ! mdo_en_is_request() ) { return $redirect; }
    $parts = mdo_en_segments();
    if ( count( $parts ) === 1 && mdo_en_find_by_slug( 'page', $parts[0] ) ) { return false; }
    if ( count( $parts ) === 2 && $parts[0] === 'product' && mdo_en_find_by_slug( 'product', $parts[1] ) ) { return false; }
    return $redirect;
}, PHP_INT_MAX );

add_action( 'template_redirect', static function (): void {
    if ( ! mdo_en_is_request() || is_admin() || wp_doing_ajax() ) { return; }
    $parts = mdo_en_segments();
    if ( count( $parts ) === 1 ) {
        if ( mdo_en_find_by_slug( 'page', $parts[0] ) ) { return; }
        $native = mdo_en_native_page_from_slug( $parts[0] );
        if ( $native ) {
            $target = mdo_en_page_url( (int) $native->ID );
            if ( $target !== '' ) { wp_safe_redirect( $target, 301, 'MDO English page canonical' ); exit; }
        }
    }
    if ( count( $parts ) === 2 && in_array( $parts[0], array( 'producto', 'product' ), true ) ) {
        if ( $parts[0] === 'product' && mdo_en_find_by_slug( 'product', $parts[1] ) ) { return; }
        $native = mdo_en_native_product_from_slug( $parts[1] );
        if ( $native ) {
            $target = mdo_en_product_url( (int) $native->ID );
            if ( $target !== '' ) { wp_safe_redirect( $target, 301, 'MDO English product canonical' ); exit; }
        }
    }
}, 1 );

/* MDO_ENGLISH_ISLAND_TAX_VENDOR_V1 */
function mdo_en_term_slug( WP_Term $term ): string {
    if ( (string) get_term_meta( $term->term_id, '_en_US_published', true ) !== '1' ) { return ''; }
    return sanitize_title( wp_strip_all_tags( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) ) );
}

function mdo_en_find_term_by_slug( string $taxonomy, string $slug ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return null; }
    $slug = sanitize_title( $slug );
    if ( $slug === '' ) { return null; }
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'number'     => 2,
        'meta_query' => array(
            'relation' => 'AND',
            array( 'key' => '_en_US_slug', 'value' => $slug, 'compare' => '=' ),
            array( 'key' => '_en_US_published', 'value' => '1', 'compare' => '=' ),
        ),
    ) );
    if ( is_wp_error( $terms ) || count( $terms ) !== 1 ) { return null; }
    return $terms[0];
}

function mdo_en_taxonomy_base( string $taxonomy ): string {
    $map = array(
        'product_cat' => 'product-category',
        'product_tag' => 'product-tag',
        'category'    => 'category',
    );
    return $map[ $taxonomy ] ?? '';
}

function mdo_en_taxonomy_for_base( string $base ): string {
    $map = array(
        'product-category' => 'product_cat',
        'product-tag'      => 'product_tag',
        'category'         => 'category',
    );
    return $map[ $base ] ?? '';
}

function mdo_en_term_url( WP_Term $term ): string {
    $base = mdo_en_taxonomy_base( $term->taxonomy );
    $slug = mdo_en_term_slug( $term );
    if ( $base === '' || $slug === '' ) { return ''; }
    return mdo_en_root() . '/en/' . $base . '/' . rawurlencode( $slug ) . '/';
}

function mdo_en_vendor_url( int $user_id ): string {
    $user = get_userdata( $user_id );
    if ( ! $user instanceof WP_User || $user->user_nicename === '' ) { return ''; }
    return mdo_en_root() . '/en/store/' . rawurlencode( $user->user_nicename ) . '/';
}

add_action( 'parse_request', static function ( WP $wp ): void {
    if ( ! mdo_en_is_request() ) { return; }
    $parts = mdo_en_segments();
    if ( count( $parts ) !== 2 ) { return; }

    if ( $parts[0] === 'store' ) {
        foreach ( array( 'error', 'name', 'pagename', 'page_id', 'p', 'attachment', 'product', 'product_cat', 'product_tag', 'category_name' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['tienda'] = $parts[1];
        return;
    }

    $taxonomy = mdo_en_taxonomy_for_base( $parts[0] );
    if ( $taxonomy === '' ) { return; }
    $term = mdo_en_find_term_by_slug( $taxonomy, $parts[1] );
    if ( ! $term instanceof WP_Term ) { return; }

    foreach ( array( 'error', 'name', 'pagename', 'page_id', 'p', 'attachment', 'product', 'product_cat', 'product_tag', 'category_name', 'cat', 'tag' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
    if ( $taxonomy === 'product_cat' ) {
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['product_cat'] = $term->slug;
    } elseif ( $taxonomy === 'product_tag' ) {
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['product_tag'] = $term->slug;
    } else {
        unset( $wp->query_vars['post_type'] );
        $wp->query_vars['category_name'] = $term->slug;
    }
}, PHP_INT_MAX );

add_filter( 'term_link', static function ( string $url, WP_Term $term, string $taxonomy ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    $translated = mdo_en_term_url( $term );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 3 );

add_filter( 'wcfmmp_get_store_url', static function ( string $url, $user_id ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    $translated = mdo_en_vendor_url( (int) $user_id );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'wcfmmp_store_list_card_url', static function ( string $url, $store_id ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    $translated = mdo_en_vendor_url( (int) $store_id );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'home_url', static function ( string $url ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    return preg_replace( '#/en/en/#', '/en/', $url, 1 ) ?: $url;
}, PHP_INT_MAX );

add_filter( 'site_url', static function ( string $url ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    return preg_replace( '#/en/en/#', '/en/', $url, 1 ) ?: $url;
}, PHP_INT_MAX );

add_filter( 'redirect_canonical', static function ( $redirect ) {
    if ( ! mdo_en_is_request() ) { return $redirect; }
    $parts = mdo_en_segments();
    if ( count( $parts ) !== 2 ) { return $redirect; }
    if ( $parts[0] === 'store' ) { return false; }
    $taxonomy = mdo_en_taxonomy_for_base( $parts[0] );
    if ( $taxonomy !== '' && mdo_en_find_term_by_slug( $taxonomy, $parts[1] ) instanceof WP_Term ) { return false; }
    return $redirect;
}, PHP_INT_MAX );

add_action( 'template_redirect', static function (): void {
    if ( ! mdo_en_is_request() || is_admin() || wp_doing_ajax() ) { return; }
    $parts = mdo_en_segments();
    if ( count( $parts ) !== 2 ) { return; }

    if ( $parts[0] === 'tienda' ) {
        // Only canonicalise a URL that was publicly requested as /en/tienda/.
        // Clean /en/store/ requests may be mapped to /en/tienda/ internally
        // so WCFM can execute its native vendor query; those must never bounce.
        $public_uri = isset( $GLOBALS['mdoev_original_public_uri_010260'] )
            ? (string) $GLOBALS['mdoev_original_public_uri_010260']
            : ( isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '' );
        $public_path = trim( (string) wp_parse_url( $public_uri, PHP_URL_PATH ), '/' );
        $public_parts = array_values( array_filter( explode( '/', $public_path ), 'strlen' ) );
        if ( isset( $public_parts[0] ) && strtolower( (string) $public_parts[0] ) === 'en' ) {
            array_shift( $public_parts );
        }
        if ( count( $public_parts ) !== 2 || strtolower( (string) $public_parts[0] ) !== 'tienda' ) {
            return;
        }
        $target = mdo_en_root() . '/en/store/' . rawurlencode( $public_parts[1] ) . '/';
        wp_safe_redirect( $target, 301, 'MDO English vendor base' );
        exit;
    }

    $taxonomy = mdo_en_taxonomy_for_base( $parts[0] );
    if ( $taxonomy === '' || mdo_en_find_term_by_slug( $taxonomy, $parts[1] ) instanceof WP_Term ) { return; }
    $native = get_term_by( 'slug', $parts[1], $taxonomy );
    if ( $native instanceof WP_Term ) {
        $target = mdo_en_term_url( $native );
        if ( $target !== '' ) {
            wp_safe_redirect( $target, 301, 'MDO English taxonomy slug' );
            exit;
        }
    }
}, 2 );

/* MDO_ENGLISH_ISLAND_POSTS_FINAL_V1 */
function mdo_en_post_url( int $post_id ): string {
    $slug = mdo_en_slug_for_post( $post_id );
    return $slug === '' ? '' : mdo_en_root() . '/en/' . rawurlencode( $slug ) . '/';
}

function mdo_en_native_post_from_slug( string $slug ) {
    $post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'post' );
    return ( $post instanceof WP_Post && $post->post_status === 'publish' ) ? $post : null;
}

/*
 * Base router sets post_type=product so its custom catalogue loader stays
 * active. Product taxonomy aliases must instead mirror native WC taxonomy
 * semantics; the taxonomy query var alone is enough and prevents is_shop().
 */
add_action( 'parse_request', static function ( WP $wp ): void {
    if ( ! mdo_en_is_request() ) { return; }
    $parts = mdo_en_segments();
    if ( count( $parts ) === 2 ) {
        $taxonomy = mdo_en_taxonomy_for_base( $parts[0] );
        if ( in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
            $term = mdo_en_find_term_by_slug( $taxonomy, $parts[1] );
            if ( $term instanceof WP_Term ) {
                unset( $wp->query_vars['post_type'] );
            }
        }
        return;
    }

    if ( count( $parts ) !== 1 ) { return; }
    $post_id = mdo_en_find_by_slug( 'post', $parts[0] );
    if ( ! $post_id ) { return; }
    foreach ( array( 'error', 'name', 'pagename', 'page_id', 'attachment', 'product', 'product_cat', 'product_tag', 'category_name' ) as $key ) {
        unset( $wp->query_vars[ $key ] );
    }
    $wp->query_vars['post_type'] = 'post';
    $wp->query_vars['p'] = $post_id;
}, PHP_INT_MAX );

add_filter( 'post_link', static function ( string $url, WP_Post $post ): string {
    if ( ! mdo_en_is_request() || $post->post_type !== 'post' ) { return $url; }
    $translated = mdo_en_post_url( (int) $post->ID );
    return $translated !== '' ? $translated : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'redirect_canonical', static function ( $redirect ) {
    if ( ! mdo_en_is_request() ) { return $redirect; }
    $parts = mdo_en_segments();
    if ( count( $parts ) === 1 && mdo_en_find_by_slug( 'post', $parts[0] ) ) { return false; }
    return $redirect;
}, PHP_INT_MAX );

add_action( 'template_redirect', static function (): void {
    if ( ! mdo_en_is_request() || is_admin() || wp_doing_ajax() ) { return; }
    $parts = mdo_en_segments();
    if ( count( $parts ) !== 1 || mdo_en_find_by_slug( 'post', $parts[0] ) ) { return; }
    $native = mdo_en_native_post_from_slug( $parts[0] );
    if ( ! $native ) { return; }
    $target = mdo_en_post_url( (int) $native->ID );
    if ( $target !== '' ) {
        wp_safe_redirect( $target, 301, 'MDO English post canonical' );
        exit;
    }
}, 3 );

function mdo_en_canonical_anchor_url( string $url ): string {
    if ( ! mdo_en_is_request() || $url === '' || strpos( $url, '#' ) === 0 ) { return $url; }
    $root = mdo_en_root();
    $root_host = strtolower( (string) wp_parse_url( $root, PHP_URL_HOST ) );
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    if ( $host !== '' && $host !== $root_host ) { return $url; }
    $path = (string) wp_parse_url( $url, PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
    if ( count( $parts ) !== 2 || strtolower( $parts[0] ) !== 'en' ) { return $url; }
    $slug = sanitize_title( $parts[1] );
    if ( $slug === '' ) { return $url; }

    /* Already canonical English aliases stay untouched. */
    if ( mdo_en_find_by_slug( 'page', $slug ) || mdo_en_find_by_slug( 'post', $slug ) ) { return $url; }

    $target = '';
    $native_page = mdo_en_native_page_from_slug( $slug );
    if ( $native_page ) {
        $target = mdo_en_page_url( (int) $native_page->ID );
    } else {
        $native_post = mdo_en_native_post_from_slug( $slug );
        if ( $native_post ) { $target = mdo_en_post_url( (int) $native_post->ID ); }
    }
    if ( $target === '' ) { return $url; }

    $query = wp_parse_url( $url, PHP_URL_QUERY );
    $fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );
    if ( is_string( $query ) && $query !== '' ) { $target .= '?' . $query; }
    if ( is_string( $fragment ) && $fragment !== '' ) { $target .= '#' . $fragment; }
    return $target;
}

/*
 * Some footer/legal links are hard-coded markup rather than WP menu items.
 * Rewrite only visible <a href> attributes on English front-end responses;
 * never touch canonical/hreflang/link tags or Spanish output.
 */
add_action( 'template_redirect', static function (): void {
    if ( ! mdo_en_is_request() || is_admin() || wp_doing_ajax() || is_feed() ) { return; }
    ob_start( static function ( string $html ): string {
        return preg_replace_callback(
            '#<a\b([^>]*?)\bhref=("|\')([^"\']+)(\2)([^>]*)>#i',
            static function ( array $m ): string {
                $rewritten = mdo_en_canonical_anchor_url( html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
                if ( $rewritten === $m[3] ) { return $m[0]; }
                return '<a' . $m[1] . 'href=' . $m[2] . esc_url( $rewritten ) . $m[4] . $m[5] . '>';
            },
            $html
        ) ?: $html;
    } );
}, 100 );

/* MDO_ENGLISH_ISLAND_VISIBLE_URL_FINAL_V1 */
add_filter( 'post_type_archive_link', static function ( string $url, string $post_type ): string {
    if ( ! mdo_en_is_request() || $post_type !== 'product' ) { return $url; }
    $shop_id = (int) get_option( 'woocommerce_shop_page_id' );
    $translated = $shop_id > 0 ? mdo_en_page_url( $shop_id ) : '';
    return $translated !== '' ? $translated : mdo_en_root() . '/en/shop/';
}, PHP_INT_MAX, 2 );

add_filter( 'wt_cli_plugin_settings', static function ( $settings ) {
    if ( ! mdo_en_is_request() || ! is_array( $settings ) ) { return $settings; }
    $cookie_page = get_page_by_path( 'politica-de-cookies', OBJECT, 'page' );
    $target = $cookie_page instanceof WP_Post ? mdo_en_page_url( (int) $cookie_page->ID ) : '';
    if ( $target === '' ) { $target = mdo_en_root() . '/en/cookie-policy/'; }
    $settings['button_2_url'] = $target;
    return $settings;
}, PHP_INT_MAX );

/* MDO_ENGLISH_ISLAND_EXACT_URL_HOOKS_V1 */
add_filter( 'woocommerce_get_shop_page_permalink', static function ( string $url ): string {
    if ( ! mdo_en_is_request() ) { return $url; }
    $shop_id = (int) get_option( 'woocommerce_shop_page_id' );
    $translated = $shop_id > 0 ? mdo_en_page_url( $shop_id ) : '';
    return $translated !== '' ? $translated : mdo_en_root() . '/en/shop/';
}, PHP_INT_MAX );

add_filter( 'wt_readmore_link_settings', static function ( $settings ) {
    if ( ! mdo_en_is_request() || ! is_array( $settings ) ) { return $settings; }
    $action = isset( $settings['button_x_action'] ) ? (string) $settings['button_x_action'] : '';
    $current = isset( $settings['button_x_url'] ) ? (string) $settings['button_x_url'] : '';
    if ( $action !== 'CONSTANT_OPEN_URL' || stripos( $current, 'politica-de-cookies' ) === false ) { return $settings; }
    $cookie_page = get_page_by_path( 'politica-de-cookies', OBJECT, 'page' );
    $target = $cookie_page instanceof WP_Post ? mdo_en_page_url( (int) $cookie_page->ID ) : '';
    $settings['button_x_url'] = $target !== '' ? $target : mdo_en_root() . '/en/cookie-policy/';
    return $settings;
}, PHP_INT_MAX );

/* MDO_ENGLISH_ISLAND_VISIBLE_ANCHOR_V2 */
add_action( 'template_redirect', static function (): void {
    if ( is_admin() || ! mdo_en_is_request() ) { return; }
    ob_start( static function ( string $html ): string {
        $shop = mdo_en_root() . '/en/shop/';
        return (string) preg_replace_callback( '/<a\b[^>]*>/i', static function ( array $m ) use ( $shop ): string {
            $tag = $m[0];
            $tag = preg_replace( '#href=("|\')https?://(?:www\.)?elmercadodeorigen\.com/en/tienda/\1#i', 'href="' . esc_url( $shop ) . '"', $tag );
            $tag = preg_replace( '#href=("|\')/en/tienda/\1#i', 'href="' . esc_url( $shop ) . '"', $tag );
            return (string) $tag;
        }, $html );
    } );
}, PHP_INT_MAX );

/* MDO_ENGLISH_ISLAND_OUTERMOST_LINK_BUFFER_V2 */
add_action('plugins_loaded', static function (): void {
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if (!preg_match('#^/en(?:/|$)#i', (string) parse_url($uri, PHP_URL_PATH))) return;
    ob_start(static function (string $html): string {
        $shop = home_url('/en/shop/');
        $html = preg_replace('#href=("|\')https?://(?:www\.)?elmercadodeorigen\.com/en/tienda/([^"\']*)\1#i', 'href="' . esc_url($shop) . '$2"', $html);
        $html = preg_replace('#href=("|\')/en/tienda/([^"\']*)\1#i', 'href="' . esc_url($shop) . '$2"', $html);
        return (string) $html;
    });
}, -PHP_INT_MAX);
