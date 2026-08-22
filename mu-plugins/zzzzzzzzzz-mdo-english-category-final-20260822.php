<?php
/**
 * Plugin Name: MDO English Category Final Query Guard
 * Description: Final, route-scoped repair for English WooCommerce product-category archives. Never affects the global shop or producer stores.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_ec_final_public_uri_20260822(): string {
    return (string) ( $GLOBALS['mdoer_public_request_uri'] ?? $GLOBALS['mdo_ec_public_uri_20260822'] ?? ( $_SERVER['REQUEST_URI'] ?? '' ) );
}

/** @return array{slug:string,paged:int}|array{} */
function mdo_ec_final_route_20260822(): array {
    static $route = null;
    if ( null !== $route ) { return $route; }
    $path = (string) wp_parse_url( mdo_ec_final_public_uri_20260822(), PHP_URL_PATH );
    if ( 1 !== preg_match( '#^/en/product-category/([^/]+)(?:/page/([1-9][0-9]*))?/?$#i', $path, $m ) ) {
        return $route = array();
    }
    return $route = array(
        'slug'  => sanitize_title( rawurldecode( (string) $m[1] ) ),
        'paged' => isset( $m[2] ) ? max( 1, absint( $m[2] ) ) : 1,
    );
}

function mdo_ec_final_term_20260822(): ?WP_Term {
    static $term = false;
    if ( false !== $term ) { return $term instanceof WP_Term ? $term : null; }
    $route = mdo_ec_final_route_20260822();
    if ( ! $route ) { $term = null; return null; }
    $slug = (string) $route['slug'];

    if ( function_exists( 'mdo_en_find_term_by_slug' ) ) {
        $found = mdo_en_find_term_by_slug( 'product_cat', $slug );
        if ( $found instanceof WP_Term ) { $term = $found; return $found; }
    }

    $native = get_term_by( 'slug', $slug, 'product_cat' );
    if ( $native instanceof WP_Term ) { $term = $native; return $native; }

    $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $candidate ) {
            if ( ! $candidate instanceof WP_Term ) { continue; }
            $en_slug = sanitize_title( (string) get_term_meta( $candidate->term_id, '_en_US_slug', true ) );
            $en_name = sanitize_title( (string) get_term_meta( $candidate->term_id, '_en_US_name', true ) );
            if ( $slug === $en_slug || $slug === $en_name ) { $term = $candidate; return $candidate; }
        }
    }
    $term = null;
    return null;
}

/** @return int[] */
function mdo_ec_final_visible_ids_20260822( int $term_id ): array {
    static $cache = array();
    $term_id = absint( $term_id );
    if ( $term_id <= 0 ) { return array(); }
    if ( isset( $cache[ $term_id ] ) ) { return $cache[ $term_id ]; }

    $term_ids = array( $term_id );
    $children = get_term_children( $term_id, 'product_cat' );
    if ( ! is_wp_error( $children ) ) {
        $term_ids = array_values( array_unique( array_merge( $term_ids, array_filter( array_map( 'absint', (array) $children ) ) ) ) );
    }

    global $wpdb;
    $sql = "SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
        WHERE p.post_type='product'
          AND p.post_status='publish'
          AND tt.taxonomy='product_cat'
          AND tt.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . ')';

    if ( function_exists( 'elmercado_catalog_visibility_sql_clause_010218' ) ) {
        $sql .= elmercado_catalog_visibility_sql_clause_010218( 'p' );
    }
    if ( function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
        $excluded = array_values( array_filter( array_map( 'absint', (array) elmercado_catalog_counts_excluded_authors_010217() ) ) );
        if ( $excluded ) { $sql .= ' AND p.post_author NOT IN (' . implode( ',', $excluded ) . ')'; }
    }
    $sql .= ' ORDER BY p.ID DESC';
    $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $wpdb->get_col( $sql ) ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    if ( $ids && class_exists( 'MDO_Catalog_Ranking' ) && is_callable( array( 'MDO_Catalog_Ranking', 'rank_products' ) ) ) {
        $ranked = MDO_Catalog_Ranking::rank_products( $ids, array( 'rotation_seed' => gmdate( 'Y-m-d' ), 'diversify_vendors' => true ) );
        if ( $ranked ) { $ids = array_values( array_map( 'absint', $ranked ) ); }
    }
    return $cache[ $term_id ] = $ids;
}

/* Last parse_request pass: canonicalise the taxonomy query without changing the public URL. */
add_action( 'parse_request', static function ( WP $wp ): void {
    $term = mdo_ec_final_term_20260822();
    if ( ! $term instanceof WP_Term ) { return; }
    foreach ( array( 'error','name','pagename','page_id','p','attachment','product','product_tag','category_name','cat','tag' ) as $key ) {
        unset( $wp->query_vars[ $key ] );
    }
    unset( $wp->query_vars['post_type'] );
    $wp->query_vars['product_cat'] = (string) $term->slug;
    $route = mdo_ec_final_route_20260822();
    if ( ! empty( $route['paged'] ) && (int) $route['paged'] > 1 ) { $wp->query_vars['paged'] = (int) $route['paged']; }
}, PHP_INT_MAX );

/* Register after theme/plugin query hooks so this is the final post inclusion decision. */
add_action( 'wp_loaded', static function (): void {
    add_action( 'pre_get_posts', static function ( WP_Query $query ): void {
        if ( is_admin() || ! $query->is_main_query() ) { return; }
        $term = mdo_ec_final_term_20260822();
        if ( ! $term instanceof WP_Term ) { return; }

        $ids = mdo_ec_final_visible_ids_20260822( (int) $term->term_id );
        $query->set( 'product_cat', (string) $term->slug );
        $query->set( 'post_type', 'product' );
        $query->set( 'post_status', 'publish' );
        $query->set( 'post__in', $ids ?: array( 0 ) );

        $requested_order = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' === $requested_order || 'mdo_recommended' === $requested_order ) {
            $query->set( 'orderby', 'post__in' );
            $query->set( 'order', 'ASC' );
        }

        $route = mdo_ec_final_route_20260822();
        if ( ! empty( $route['paged'] ) ) { $query->set( 'paged', max( 1, (int) $route['paged'] ) ); }
    }, PHP_INT_MAX );

    /* The child-theme catalogue parity layer reports the global shop total for
     * taxonomy archives. Correct the unfiltered English category total here. */
    add_filter( 'found_posts', static function ( $found, WP_Query $query ) {
        if ( ! $query->is_main_query() ) { return $found; }
        $term = mdo_ec_final_term_20260822();
        if ( ! $term instanceof WP_Term ) { return $found; }
        foreach ( array_keys( $_GET ) as $raw ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = sanitize_key( (string) $raw );
            if ( in_array( $key, array( 'orderby' ), true ) ) { continue; }
            if ( in_array( $key, array( 'min_price','max_price','vendor_id','s','product_tag' ), true ) || str_starts_with( $key, 'filter_' ) || str_starts_with( $key, 'query_type_' ) ) { return $found; }
        }
        return count( mdo_ec_final_visible_ids_20260822( (int) $term->term_id ) );
    }, PHP_INT_MAX, 2 );

    add_filter( 'the_posts', static function ( array $posts, WP_Query $query ): array {
        if ( ! $query->is_main_query() ) { return $posts; }
        $term = mdo_ec_final_term_20260822();
        if ( ! $term instanceof WP_Term ) { return $posts; }
        $total = count( mdo_ec_final_visible_ids_20260822( (int) $term->term_id ) );
        $per_page = max( 1, (int) $query->get( 'posts_per_page' ) );
        $query->found_posts = $total;
        $query->max_num_pages = (int) ceil( $total / $per_page );
        return $posts;
    }, PHP_INT_MAX, 2 );
}, PHP_INT_MAX );

/* The public English alias is already canonical. */
add_filter( 'redirect_canonical', static function ( $redirect ) {
    return mdo_ec_final_term_20260822() instanceof WP_Term ? false : $redirect;
}, PHP_INT_MAX );
