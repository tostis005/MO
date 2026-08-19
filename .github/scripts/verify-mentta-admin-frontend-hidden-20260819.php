<?php
// Verifies that logged-in administrators no longer receive the internal MENTTA category on the storefront.
if ( $argc < 2 ) { fwrite( STDERR, "missing_wp_path\n" ); exit( 90 ); }

$_SERVER['REQUEST_URI'] = '/en/shop/';
$_SERVER['HTTP_HOST'] = 'www.elmercadodeorigen.com';
$_SERVER['HTTPS'] = 'on';

require rtrim( $argv[1], '/\\' ) . '/wp-load.php';

$out = array(
    'ok'                    => false,
    'admin_exception_file'  => file_exists( WPMU_PLUGIN_DIR . '/mdo-mentta-admin-frontend-010257.php' ),
    'base_filter_present'   => false,
    'admin_set'             => false,
    'mentta_absent'         => false,
    'errors'                => array(),
);

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
if ( ! $admins ) {
    $out['errors'][] = 'no_admin_user';
} else {
    wp_set_current_user( (int) $admins[0] );
    $out['admin_set'] = is_user_logged_in() && current_user_can( 'manage_options' );
    if ( ! $out['admin_set'] ) { $out['errors'][] = 'admin_not_set'; }
}

$out['base_filter_present'] = false !== has_filter( 'get_terms_args', 'mdo_mentta_hide_from_public_term_queries' )
    && false !== has_filter( 'get_terms', 'mdo_mentta_hide_from_public_term_results' );
if ( ! $out['base_filter_present'] ) { $out['errors'][] = 'base_filter_missing'; }

if ( $out['admin_exception_file'] ) { $out['errors'][] = 'admin_exception_file_still_present'; }

$slugs = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'slugs',
) );

if ( is_wp_error( $slugs ) ) {
    $out['errors'][] = 'term_query_error';
} else {
    $out['mentta_absent'] = ! in_array( 'mentta', array_map( 'strtolower', (array) $slugs ), true );
    if ( ! $out['mentta_absent'] ) { $out['errors'][] = 'mentta_visible_to_admin_frontend'; }
}

$out['ok'] = empty( $out['errors'] )
    && ! $out['admin_exception_file']
    && $out['base_filter_present']
    && $out['admin_set']
    && $out['mentta_absent'];

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
exit( $out['ok'] ? 0 : 1 );
