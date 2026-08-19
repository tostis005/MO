<?php
// Production verification trigger v1.0.2.
if ( $argc < 2 ) { fwrite( STDERR, "missing_wp_path\n" ); exit( 90 ); }

define( 'DOING_AJAX', true );
define( 'WP_ADMIN', true );
$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
$_SERVER['HTTP_REFERER'] = 'https://www.elmercadodeorigen.com/en/shop/';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

require rtrim( $argv[1], '/\\' ) . '/wp-load.php';

$out = array(
    'ok'              => false,
    'plugin_loaded'   => function_exists( 'mdo_mentta_should_hide_publicly' ),
    'version_1_2_1'   => false,
    'ajax_hidden'     => false,
    'term_absent'     => false,
    'errors'          => array(),
);

$file = WPMU_PLUGIN_DIR . '/mdo-internal-mentta-category.php';
if ( is_readable( $file ) ) {
    $source = (string) file_get_contents( $file );
    $out['version_1_2_1'] = false !== strpos( $source, 'Version: 1.2.1' )
        && false !== strpos( $source, 'HTTP_REFERER' );
}

if ( ! $out['plugin_loaded'] ) {
    $out['errors'][] = 'plugin_not_loaded';
} else {
    $out['ajax_hidden'] = (bool) mdo_mentta_should_hide_publicly();
    if ( ! $out['ajax_hidden'] ) { $out['errors'][] = 'ajax_not_hidden'; }
}

if ( ! $out['version_1_2_1'] ) { $out['errors'][] = 'version_not_1_2_1'; }

$slugs = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'slugs',
) );
if ( is_wp_error( $slugs ) ) {
    $out['errors'][] = 'term_query_error';
} else {
    $out['term_absent'] = ! in_array( 'mentta', array_map( 'strtolower', (array) $slugs ), true );
    if ( ! $out['term_absent'] ) { $out['errors'][] = 'mentta_still_in_term_query'; }
}

$out['ok'] = empty( $out['errors'] )
    && $out['plugin_loaded']
    && $out['version_1_2_1']
    && $out['ajax_hidden']
    && $out['term_absent'];

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
exit( $out['ok'] ? 0 : 1 );
