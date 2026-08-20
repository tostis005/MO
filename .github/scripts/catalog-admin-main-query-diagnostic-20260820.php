<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => array( 'ID' ) ) );
if ( ! $admins ) { fwrite( STDERR, "NO_ADMIN\n" ); exit( 2 ); }
$admin_id = (int) $admins[0]->ID;
wp_set_current_user( $admin_id );

$disabled = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
    ? array_values( array_filter( array_map( 'absint', (array) elmercado_wcfm_disabled_vendor_ids_010210() ) ) )
    : array();
if ( ! $disabled ) { fwrite( STDERR, "NO_DISABLED\n" ); exit( 3 ); }

$vendor_id = 0;
$product_id = 0;
foreach ( $disabled as $candidate ) {
    $ids = get_posts( array(
        'post_type' => 'product', 'post_status' => 'publish', 'author' => $candidate,
        'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true,
    ) );
    if ( $ids ) { $vendor_id = (int) $candidate; $product_id = (int) $ids[0]; break; }
}
if ( ! $vendor_id ) { fwrite( STDERR, "NO_VENDOR_PRODUCT\n" ); exit( 4 ); }

$diag_token = strtolower( wp_generate_password( 16, false, false ) );
$diag_token = preg_replace( '/[^a-z0-9]/', '', $diag_token );
$observer_path = WPMU_PLUGIN_DIR . '/mdo-temp-main-query-observer-20260820.php';
$observer = <<<'PHP'
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action(
    'wp',
    static function (): void {
        if ( ! isset( $_GET['emo_main_diag'] ) || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $token = preg_replace( '/[^a-z0-9]/', '', strtolower( sanitize_text_field( wp_unslash( $_GET['emo_main_diag'] ) ) ) );
        if ( '' === $token ) { return; }
        global $wp_query;
        if ( ! $wp_query instanceof WP_Query ) { return; }
        $vars = $wp_query->query_vars;
        foreach ( $vars as $key => $value ) {
            if ( is_object( $value ) ) { $vars[ $key ] = get_class( $value ); }
            elseif ( is_resource( $value ) ) { $vars[ $key ] = 'resource'; }
        }
        $data = array(
            'is_shop' => function_exists( 'is_shop' ) && is_shop(),
            'is_product_category' => function_exists( 'is_product_category' ) && is_product_category(),
            'request_uri' => (string) ( $_SERVER['REQUEST_URI'] ?? '' ),
            'get' => $_GET,
            'query_vars' => $vars,
            'sql' => (string) $wp_query->request,
            'posts' => array_values( array_map( static fn( $p ) => $p instanceof WP_Post ? (int) $p->ID : (int) $p, (array) $wp_query->posts ) ),
            'post_count' => (int) $wp_query->post_count,
            'found_posts' => (int) $wp_query->found_posts,
            'max_num_pages' => (int) $wp_query->max_num_pages,
        );
        file_put_contents( sys_get_temp_dir() . '/emo-main-' . $token . '.json', wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
    },
    PHP_INT_MAX
);
PHP;

if ( false === file_put_contents( $observer_path, $observer ) ) {
    fwrite( STDERR, "OBSERVER_WRITE_FAILED\n" ); exit( 5 );
}

$expiration = time() + 600;
$manager = WP_Session_Tokens::get_instance( $admin_id );
$session_token = $manager->create( $expiration );
$cookie = wp_generate_auth_cookie( $admin_id, $expiration, 'logged_in', $session_token );
$cookie_header = LOGGED_IN_COOKIE . '=' . $cookie;

$url = add_query_arg(
    array( 'vendor_id' => $vendor_id, 'emo_main_diag' => $diag_token, 'cache_bust' => time() ),
    wc_get_page_permalink( 'shop' )
);
$response = wp_remote_get( $url, array(
    'timeout' => 30,
    'redirection' => 3,
    'headers' => array(
        'Cookie' => $cookie_header,
        'Cache-Control' => 'no-cache',
        'Pragma' => 'no-cache',
        'User-Agent' => 'EMDO main query diagnostic',
    ),
) );

$manager->destroy( $session_token );
@unlink( $observer_path );

if ( is_wp_error( $response ) ) {
    fwrite( STDERR, 'HTTP_ERROR=' . $response->get_error_message() . "\n" ); exit( 6 );
}
$body = (string) wp_remote_retrieve_body( $response );
echo 'ADMIN_ID=' . $admin_id . "\n";
echo 'VENDOR_ID=' . $vendor_id . "\n";
echo 'PRODUCT_ID=' . $product_id . "\n";
echo 'STATUS=' . wp_remote_retrieve_response_code( $response ) . "\n";
echo 'HTML_NO_PRODUCTS=' . ( false !== strpos( $body, 'No se han encontrado productos que coincidan con tu selección' ) ? '1' : '0' ) . "\n";

$diag_path = sys_get_temp_dir() . '/emo-main-' . $diag_token . '.json';
if ( ! is_readable( $diag_path ) ) {
    fwrite( STDERR, "MAIN_QUERY_REPORT_MISSING\n" ); exit( 7 );
}
$payload = file_get_contents( $diag_path );
@unlink( $diag_path );
echo "MAIN_QUERY_JSON_BEGIN\n" . $payload . "\nMAIN_QUERY_JSON_END\n";
