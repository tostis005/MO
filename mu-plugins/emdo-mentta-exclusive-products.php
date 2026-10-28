<?php
/**
 * Plugin Name: EMDO - Productos exclusivos para Mentta
 * Description: Muestra seis packs exclusivamente en la plantilla normal WooCommerce mediante enlace directo, sin listarlos en el escaparate.
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_mentta_exclusive_ids() {
    $ids = get_option( 'emdo_mentta_exclusive_ids', array() );
    return array_values( array_filter( array_unique( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * Six already-approved Mentta-only packs may be opened via their exact direct
 * product URL for partner diagnostics. They remain excluded from shop listings,
 * searches, product feeds, related items and checkout.
 * The four unapproved Musee packs are NOT on this allowlist.
 */
function emdo_mentta_partner_view_products() {
    return array(
        'pack-degustacion-pasaporte-salvaje' => 21814,
        'pack-degustacion-safari-africano' => 21818,
        'pack-degustacion-vuelta-al-mundo' => 21820,
        'pack-cafe-esencia-de-costa-rica' => 21904,
        'pack-cafe-ruta-de-los-cuatro-origenes' => 21906,
        'pack-cafe-coleccion-completa' => 21908,
    );
}

/** Only the MAIN single-product query can pass the public shop exclusion. */
function emdo_mentta_partner_direct_product_query( $query ) {
    if ( ! ( $query instanceof WP_Query ) || ! $query->is_main_query() ) { return false; }

    // A real direct product URL can still be unresolved as a singular query
    // during pre_get_posts. Use its exact canonical route rather than relying
    // only on WordPress's early is_singular() state.
    $path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $parts = explode( '/', $path );
    if ( count( $parts ) !== 2 || $parts[0] !== 'producto' ) { return false; }
    $approved = emdo_mentta_partner_view_products();
    $slug = $parts[1];
    if ( ! isset( $approved[ $slug ] ) ) { return false; }
    $id = (int) $approved[ $slug ];

    // Reject incompatible explicit product targets rather than allowing a
    // query to surface another protected product under a shared URL.
    $requested_id = absint( $query->get( 'p' ) );
    if ( $requested_id && $requested_id !== $id ) { return false; }
    return true;
}

function emdo_mentta_exclusive_public_request() {
    // Allow WordPress admin to preview only the seven paused Musée Gourmet packs.
    // Everyone else (including public visitors) retains the normal 404 exclusions.
    if ( function_exists( 'emdo_musee_review_preview_allowed' ) && emdo_musee_review_preview_allowed() ) { return false; }
    if ( defined( 'WP_CLI' ) && WP_CLI ) { return false; }
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return false; }
    if ( is_admin() ) {
        if ( ! wp_doing_ajax() ) { return false; }
        $referer = (string) ( $_SERVER['HTTP_REFERER'] ?? '' );
        if ( false !== strpos( strtolower( (string) wp_parse_url( $referer, PHP_URL_PATH ) ), '/wp-admin/' ) ) { return false; }
    }
    return true;
}

/**
 * No standalone HTML rendering: approved directly linked packs use the SAME
 * WooCommerce product template as any ordinary product on the site.
 * Catalog/Store API exclusions are handled independently below.
 */
add_filter( 'wc_product_sku_enabled', static function ( $enabled ) {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) { return $enabled; }
    $id = (int) get_queried_object_id();
    return in_array( $id, array_values( emdo_mentta_partner_view_products() ), true ) ? false : $enabled;
}, 10000 );

/** Protect main shop, producer pages, archives, widgets and custom WP_Query grids. */
add_action( 'pre_get_posts', function ( $query ) {
    if ( ! emdo_mentta_exclusive_public_request() || emdo_mentta_partner_direct_product_query( $query ) ) { return; }
    $post_type = $query->get( 'post_type' );
    if ( 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
        && ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( 'product_cat' )
        && ! $query->is_tax( 'product_tag' ) ) { return; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return; }
    $query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), $ids ) ) ) );
}, 100 );

/** Final guard for grids using hand-written product queries. */
add_filter( 'the_posts', function ( $posts, $query ) {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_array( $posts ) ) { return $posts; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return $posts; }
    $partner_detail = emdo_mentta_partner_direct_product_query( $query );
    $approved_ids = $partner_detail ? array_values( emdo_mentta_partner_view_products() ) : array();
    return array_values( array_filter( $posts, static function ( $post ) use ( $ids, $approved_ids ) {
        if ( ! ( $post instanceof WP_Post ) || 'product' !== $post->post_type ) { return true; }
        $id = (int) $post->ID;
        return ! in_array( $id, $ids, true ) || in_array( $id, $approved_ids, true );
    } ) );
}, 100, 2 );
add_filter( 'woocommerce_product_is_visible', function ( $visible, $product_id ) {
    if ( ! emdo_mentta_exclusive_public_request() || ! in_array( (int) $product_id, emdo_mentta_exclusive_ids(), true ) ) { return $visible; }
    if ( in_array( (int) $product_id, array_values( emdo_mentta_partner_view_products() ), true )
        && is_singular( 'product' ) && (int) get_queried_object_id() === (int) $product_id ) { return $visible; }
    return false;
}, 100, 2 );
add_filter( 'woocommerce_related_products', function ( $ids ) {
    return emdo_mentta_exclusive_public_request() ? array_values( array_diff( (array) $ids, emdo_mentta_exclusive_ids() ) ) : $ids;
}, 100 );

/** A direct browser URL must be 404, but authorized WooCommerce REST remains readable. */
add_action( 'template_redirect', function () {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_singular( 'product' ) ) { return; }
    $id = (int) get_queried_object_id();
    if ( ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) { return; }
    if ( in_array( $id, array_values( emdo_mentta_partner_view_products() ), true ) ) { return; }
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
}, 0 );

/** Keep these commercial-only duplicates outside WordPress and Yoast sitemaps. */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'product' !== $post_type ) { return $args; }
    $args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), emdo_mentta_exclusive_ids() ) ) );
    return $args;
}, 100, 2 );
add_filter( 'wpseo_sitemap_entry', function ( $url, $type, $post ) {
    if ( 'post' === $type && is_object( $post ) && in_array( (int) ( $post->ID ?? 0 ), emdo_mentta_exclusive_ids(), true ) ) { return false; }
    return $url;
}, 100, 3 );
