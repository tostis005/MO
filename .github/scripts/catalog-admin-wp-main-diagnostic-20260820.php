<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => array( 'ID' ) ) );
if ( ! $admins ) { fwrite( STDERR, "NO_ADMIN\n" ); exit( 2 ); }
$admin_id = (int) $admins[0]->ID;
wp_set_current_user( $admin_id );

$disabled = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
    ? array_values( array_filter( array_map( 'absint', (array) elmercado_wcfm_disabled_vendor_ids_010210() ) ) )
    : array();
$vendor_id = 0;
foreach ( $disabled as $candidate ) {
    $ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'author' => $candidate, 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
    if ( $ids ) { $vendor_id = (int) $candidate; break; }
}
if ( ! $vendor_id ) { fwrite( STDERR, "NO_DISABLED_VENDOR_PRODUCTS\n" ); exit( 3 ); }

$shop_url = wc_get_page_permalink( 'shop' );
$path = (string) wp_parse_url( $shop_url, PHP_URL_PATH );
if ( '' === $path ) { $path = '/tienda/'; }
$query_string = 'vendor_id=' . $vendor_id;

$_GET = array( 'vendor_id' => (string) $vendor_id );
$_POST = array();
$_REQUEST = $_GET;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $path . '?' . $query_string;
$_SERVER['QUERY_STRING'] = $query_string;
$_SERVER['HTTP_HOST'] = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

/* WP-CLI loads WordPress but does not run the front-controller main query. */
global $wp, $wp_query, $wp_the_query;
$wp = new WP();
$wp_query = new WP_Query();
$wp_the_query = $wp_query;
$wp->main();

$ids = array_values( array_map( static fn( $p ) => $p instanceof WP_Post ? (int) $p->ID : (int) $p, (array) $wp_query->posts ) );
$vars = $wp_query->query_vars;
foreach ( $vars as $key => $value ) {
    if ( is_object( $value ) ) { $vars[ $key ] = get_class( $value ); }
    elseif ( is_resource( $value ) ) { $vars[ $key ] = 'resource'; }
}

echo 'ADMIN_ID=' . $admin_id . "\n";
echo 'VENDOR_ID=' . $vendor_id . "\n";
echo 'CAN_MANAGE=' . ( current_user_can( 'manage_options' ) ? '1' : '0' ) . "\n";
echo 'IS_SHOP=' . ( function_exists( 'is_shop' ) && is_shop() ? '1' : '0' ) . "\n";
echo 'IS_PRODUCT_ARCHIVE=' . ( $wp_query->is_post_type_archive( 'product' ) ? '1' : '0' ) . "\n";
echo 'POST_COUNT=' . (int) $wp_query->post_count . "\n";
echo 'FOUND_POSTS=' . (int) $wp_query->found_posts . "\n";
echo 'POST_IDS=' . implode( ',', $ids ) . "\n";
echo 'QUERY_VARS=' . wp_json_encode( $vars, JSON_UNESCAPED_SLASHES ) . "\n";
echo 'SQL=' . preg_replace( '/\s+/', ' ', (string) $wp_query->request ) . "\n";
