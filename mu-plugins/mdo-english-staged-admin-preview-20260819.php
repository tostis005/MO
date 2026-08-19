<?php
/**
 * Plugin Name: MDO English Staged Product Admin Preview
 * Description: Lets administrators preview staged English product routes and copy without publishing them to visitors.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_en_preview_path_is_english_20260819( string $value ): bool {
    $path = (string) wp_parse_url( $value, PHP_URL_PATH );
    return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

function mdo_en_preview_public_path_20260819(): string {
    if ( isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
        return (string) wp_parse_url( (string) $GLOBALS['mdoer_public_request_uri'], PHP_URL_PATH );
    }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    return (string) wp_parse_url( $uri, PHP_URL_PATH );
}

function mdo_en_preview_is_english_20260819(): bool {
    if ( mdo_en_preview_path_is_english_20260819( mdo_en_preview_public_path_20260819() ) ) {
        return true;
    }

    // Frontend WooCommerce filters commonly render through admin-ajax.php or REST.
    // In those requests REQUEST_URI is not /en/, so preserve the storefront
    // language from the same-origin referrer.
    $referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
    if ( '' !== $referer && mdo_en_preview_path_is_english_20260819( $referer ) ) {
        return true;
    }

    return function_exists( 'trp_get_current_language' )
        && 'en_us' === strtolower( (string) trp_get_current_language() );
}

function mdo_en_preview_allowed_20260819(): bool {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    // Never alter normal wp-admin screens. admin-ajax.php is allowed because it
    // is also the transport used by frontend shop filters.
    if ( is_admin() && ! ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
        return false;
    }

    return mdo_en_preview_is_english_20260819();
}

function mdo_en_preview_is_staged_product_20260819( int $product_id ): bool {
    $post = get_post( $product_id );
    if ( ! $post instanceof WP_Post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) { return false; }
    if ( ! in_array( (int) $post->post_author, array( 4508, 4509 ), true ) ) { return false; }
    if ( '1' !== (string) get_post_meta( $product_id, '_en_US_ready', true ) ) { return false; }
    if ( '1' === (string) get_post_meta( $product_id, '_en_US_published', true ) ) { return false; }

    $title = trim( wp_strip_all_tags( (string) get_post_meta( $product_id, '_en_US_post_title', true ) ) );
    $slug  = sanitize_title( (string) get_post_meta( $product_id, '_en_US_post_name', true ) );
    return '' !== $title && '' !== $slug;
}

function mdo_en_preview_slug_20260819( int $product_id ): string {
    if ( ! mdo_en_preview_is_staged_product_20260819( $product_id ) ) { return ''; }
    return sanitize_title( (string) get_post_meta( $product_id, '_en_US_post_name', true ) );
}

function mdo_en_preview_url_20260819( int $product_id ): string {
    $slug = mdo_en_preview_slug_20260819( $product_id );
    return '' === $slug ? '' : home_url( '/en/product/' . rawurlencode( $slug ) . '/' );
}

function mdo_en_preview_find_product_20260819( string $slug ): int {
    $slug = sanitize_title( $slug );
    if ( '' === $slug ) { return 0; }

    global $wpdb;
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} s
                ON s.post_id=p.ID AND s.meta_key='_en_US_post_name' AND s.meta_value=%s
             INNER JOIN {$wpdb->postmeta} r
                ON r.post_id=p.ID AND r.meta_key='_en_US_ready' AND r.meta_value='1'
             LEFT JOIN {$wpdb->postmeta} pub
                ON pub.post_id=p.ID AND pub.meta_key='_en_US_published'
             WHERE p.post_type='product'
               AND p.post_status='publish'
               AND p.post_author IN (4508,4509)
               AND COALESCE(pub.meta_value,'0') <> '1'
             ORDER BY p.ID ASC
             LIMIT 2",
            $slug
        )
    );

    if ( 1 !== count( $ids ) ) { return 0; }
    $id = (int) $ids[0];
    return mdo_en_preview_is_staged_product_20260819( $id ) ? $id : 0;
}

add_action(
    'parse_request',
    static function ( WP $wp ): void {
        if ( ! mdo_en_preview_allowed_20260819() ) { return; }

        $path = trim( mdo_en_preview_public_path_20260819(), '/' );
        if ( ! preg_match( '#^en/product/([^/]+)$#i', $path, $matches ) ) { return; }

        $id = mdo_en_preview_find_product_20260819( rawurldecode( (string) $matches[1] ) );
        if ( ! $id ) { return; }

        foreach ( array( 'error', 'name', 'pagename', 'page_id', 'attachment', 'product', 'product_cat', 'product_tag' ) as $key ) {
            unset( $wp->query_vars[ $key ] );
        }
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['p'] = $id;
    },
    PHP_INT_MAX
);

add_filter(
    'post_type_link',
    static function ( string $url, WP_Post $post ): string {
        if ( ! mdo_en_preview_allowed_20260819() || 'product' !== $post->post_type ) { return $url; }
        $preview = mdo_en_preview_url_20260819( (int) $post->ID );
        return '' !== $preview ? $preview : $url;
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_product_get_permalink',
    static function ( $url, $product ) {
        if ( ! mdo_en_preview_allowed_20260819() || ! $product instanceof WC_Product ) { return $url; }
        $preview = mdo_en_preview_url_20260819( (int) $product->get_id() );
        return '' !== $preview ? $preview : $url;
    },
    PHP_INT_MAX,
    2
);

function mdo_en_preview_meta_text_20260819( int $product_id, string $key, string $fallback ): string {
    if ( ! mdo_en_preview_allowed_20260819() || ! mdo_en_preview_is_staged_product_20260819( $product_id ) ) { return $fallback; }
    $value = (string) get_post_meta( $product_id, $key, true );
    return '' !== trim( wp_strip_all_tags( $value ) ) ? $value : $fallback;
}

add_filter(
    'the_title',
    static function ( string $title, int $post_id ): string {
        return mdo_en_preview_meta_text_20260819( $post_id, '_en_US_post_title', $title );
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_product_get_name',
    static function ( $name, $product ) {
        return $product instanceof WC_Product
            ? mdo_en_preview_meta_text_20260819( (int) $product->get_id(), '_en_US_post_title', (string) $name )
            : $name;
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_product_variation_get_name',
    static function ( $name, $product ) {
        if ( ! $product instanceof WC_Product_Variation ) { return $name; }
        $parent_id = (int) $product->get_parent_id();
        return $parent_id
            ? mdo_en_preview_meta_text_20260819( $parent_id, '_en_US_post_title', (string) $name )
            : $name;
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_product_get_description',
    static function ( $description, $product ) {
        return $product instanceof WC_Product
            ? mdo_en_preview_meta_text_20260819( (int) $product->get_id(), '_en_US_post_content', (string) $description )
            : $description;
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_product_get_short_description',
    static function ( $excerpt, $product ) {
        return $product instanceof WC_Product
            ? mdo_en_preview_meta_text_20260819( (int) $product->get_id(), '_en_US_post_excerpt', (string) $excerpt )
            : $excerpt;
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'the_content',
    static function ( string $content ): string {
        if ( ! mdo_en_preview_allowed_20260819() || ! is_singular( 'product' ) ) { return $content; }
        return mdo_en_preview_meta_text_20260819( (int) get_the_ID(), '_en_US_post_content', $content );
    },
    PHP_INT_MAX
);

add_filter(
    'get_the_excerpt',
    static function ( $excerpt, $post ) {
        $post_id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
        return mdo_en_preview_meta_text_20260819( $post_id, '_en_US_post_excerpt', (string) $excerpt );
    },
    PHP_INT_MAX,
    2
);

add_filter(
    'woocommerce_short_description',
    static function ( $excerpt ) {
        if ( ! mdo_en_preview_allowed_20260819() ) { return $excerpt; }
        return mdo_en_preview_meta_text_20260819( (int) get_the_ID(), '_en_US_post_excerpt', (string) $excerpt );
    },
    PHP_INT_MAX
);

add_action(
    'send_headers',
    static function (): void {
        if ( ! mdo_en_preview_allowed_20260819() ) { return; }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
    },
    PHP_INT_MAX
);
