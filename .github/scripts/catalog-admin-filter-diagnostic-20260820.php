<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$admins = get_users( array( 'role' => 'administrator', 'number' => 5, 'fields' => array( 'ID', 'user_login' ) ) );
if ( ! $admins ) { fwrite( STDERR, "NO_ADMIN\n" ); exit( 2 ); }
$admin = $admins[0];
wp_set_current_user( (int) $admin->ID );

echo 'ADMIN_ID=' . (int) $admin->ID . "\n";
echo 'CAN_MANAGE=' . ( current_user_can( 'manage_options' ) ? '1' : '0' ) . "\n";
echo 'IS_ADMIN_REQUEST=' . ( is_admin() ? '1' : '0' ) . "\n";

$disabled = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ? elmercado_wcfm_disabled_vendor_ids_010210() : array();
echo 'DISABLED=' . implode( ',', array_map( 'absint', (array) $disabled ) ) . "\n";
if ( ! $disabled ) { fwrite( STDERR, "NO_DISABLED_VENDORS\n" ); exit( 3 ); }

$vendor_id = 0;
$product_id = 0;
foreach ( $disabled as $candidate ) {
    $ids = get_posts( array(
        'post_type' => 'product', 'post_status' => 'publish', 'author' => (int) $candidate,
        'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true,
    ) );
    if ( $ids ) { $vendor_id = (int) $candidate; $product_id = (int) $ids[0]; break; }
}
if ( ! $vendor_id || ! $product_id ) { fwrite( STDERR, "NO_DISABLED_VENDOR_PRODUCTS\n" ); exit( 4 ); }

echo "VENDOR_ID={$vendor_id}\nPRODUCT_ID={$product_id}\n";
$terms = wp_get_post_terms( $product_id, 'product_cat' );
$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
if ( $category instanceof WP_Term ) {
    echo 'CATEGORY_ID=' . (int) $category->term_id . "\n";
    echo 'CATEGORY_SLUG=' . $category->slug . "\n";
}

$ship_excluded = class_exists( 'MDO_Catalog_Destination_Frontend' ) ? MDO_Catalog_Destination_Frontend::excluded_vendor_ids() : array();
echo 'SHIPPING_EXCLUDED=' . implode( ',', array_map( 'absint', (array) $ship_excluded ) ) . "\n";

function emo_diag_callback_name( $cb ): string {
    if ( is_string( $cb ) ) { return $cb; }
    if ( is_array( $cb ) && count( $cb ) === 2 ) {
        $left = is_object( $cb[0] ) ? get_class( $cb[0] ) : (string) $cb[0];
        return $left . '::' . (string) $cb[1];
    }
    if ( $cb instanceof Closure ) { return 'Closure'; }
    return gettype( $cb );
}
function emo_diag_callback_file( $cb ): string {
    try {
        if ( $cb instanceof Closure ) { return (string) ( new ReflectionFunction( $cb ) )->getFileName(); }
        if ( is_array( $cb ) && count( $cb ) === 2 ) { return (string) ( new ReflectionMethod( $cb[0], $cb[1] ) )->getFileName(); }
        if ( is_string( $cb ) && function_exists( $cb ) ) { return (string) ( new ReflectionFunction( $cb ) )->getFileName(); }
    } catch ( Throwable $e ) {}
    return '';
}

global $wp_filter;
foreach ( array( 'pre_get_posts', 'posts_clauses', 'posts_where', 'posts_join', 'the_posts', 'woocommerce_product_query', 'woocommerce_product_is_visible' ) as $hook ) {
    echo "HOOK={$hook}\n";
    if ( empty( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook ) { continue; }
    foreach ( $wp_filter[ $hook ]->callbacks as $priority => $items ) {
        foreach ( $items as $item ) {
            $cb = $item['function'] ?? null;
            $file = emo_diag_callback_file( $cb );
            if ( '' === $file ) { continue; }
            $interesting = false;
            foreach ( array( 'wcfm', 'market', 'vendor', 'catalog', 'woocommerce', 'elmercado', 'mdo-' ) as $needle ) {
                if ( false !== stripos( $file, $needle ) ) { $interesting = true; break; }
            }
            if ( $interesting ) {
                echo 'CB=' . $priority . '|' . emo_diag_callback_name( $cb ) . '|' . $file . "\n";
            }
        }
    }
}

$query_args = array(
    'post_type' => 'product', 'post_status' => 'publish', 'author' => $vendor_id,
    'posts_per_page' => 20, 'fields' => 'ids', 'suppress_filters' => false,
);
if ( $category instanceof WP_Term ) {
    $query_args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( (int) $category->term_id ), 'include_children' => true ) );
}
$_GET['vendor_id'] = $vendor_id;
$q = new WP_Query( $query_args );
echo 'DIRECT_IDS=' . implode( ',', array_map( 'absint', (array) $q->posts ) ) . "\n";
echo 'DIRECT_SQL=' . preg_replace( '/\s+/', ' ', (string) $q->request ) . "\n";
unset( $_GET['vendor_id'] );

$expiration = time() + 600;
$manager = WP_Session_Tokens::get_instance( (int) $admin->ID );
$token = $manager->create( $expiration );
$cookie = wp_generate_auth_cookie( (int) $admin->ID, $expiration, 'logged_in', $token );
$cookie_header = LOGGED_IN_COOKIE . '=' . $cookie;
$base = home_url( '/tienda/' );
$urls = array( 'vendor' => add_query_arg( array( 'vendor_id' => $vendor_id, 'emo_diag' => time() ), $base ) );
if ( $category instanceof WP_Term ) {
    $cat_url = get_term_link( $category );
    if ( ! is_wp_error( $cat_url ) ) { $urls['category'] = add_query_arg( 'emo_diag', time(), $cat_url ); }
}
foreach ( $urls as $label => $url ) {
    $response = wp_remote_get( $url, array(
        'timeout' => 30, 'redirection' => 3,
        'headers' => array( 'Cookie' => $cookie_header, 'Cache-Control' => 'no-cache', 'Pragma' => 'no-cache', 'User-Agent' => 'EMDO admin filter diagnostic' ),
    ) );
    if ( is_wp_error( $response ) ) { echo strtoupper( $label ) . '_HTTP_ERROR=' . $response->get_error_message() . "\n"; continue; }
    $body = (string) wp_remote_retrieve_body( $response );
    preg_match_all( '/\bpost-(\d+)\b/', $body, $matches );
    $page_ids = array_values( array_unique( array_map( 'absint', $matches[1] ?? array() ) ) );
    $no_products = false !== strpos( $body, 'No se han encontrado productos que coincidan con tu selección' ) || false !== strpos( $body, 'No products were found matching your selection' );
    echo strtoupper( $label ) . '_STATUS=' . (int) wp_remote_retrieve_response_code( $response ) . "\n";
    echo strtoupper( $label ) . '_NO_PRODUCTS=' . ( $no_products ? '1' : '0' ) . "\n";
    echo strtoupper( $label ) . '_PAGE_IDS=' . implode( ',', $page_ids ) . "\n";
}
$manager->destroy( $token );
