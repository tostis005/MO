<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source = 'https://raw.githubusercontent.com/tostis005/MO/main/mu-plugins/mdo-cf7-antispam-20260826.php?ts=' . time();
$response = wp_remote_get( $source, array(
    'timeout'     => 20,
    'redirection' => 3,
    'headers'     => array( 'Cache-Control' => 'no-cache' ),
    'user-agent'  => 'EMDO CF7 Guard Installer',
) );

if ( is_wp_error( $response ) ) {
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: ' . $response->get_error_message() . "\n" );
    exit( 31 );
}

$status = (int) wp_remote_retrieve_response_code( $response );
$code   = (string) wp_remote_retrieve_body( $response );
if ( 200 !== $status || strlen( $code ) < 3000 ) {
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: invalid download status/size' . "\n" );
    exit( 32 );
}
if ( false === strpos( $code, "add_filter( 'wpcf7_spam', 'mdo_cf7_guard_filter_spam'" ) || false === strpos( $code, "register_rest_route( 'mdo/v1', '/cf7-guard'" ) ) {
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: expected guard markers missing' . "\n" );
    exit( 33 );
}

$dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: cannot create mu-plugins dir' . "\n" );
    exit( 34 );
}

$target = trailingslashit( $dir ) . 'mdo-cf7-antispam-20260826.php';
$tmp    = $target . '.tmp';
if ( false === file_put_contents( $tmp, $code, LOCK_EX ) ) {
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: cannot write temp file' . "\n" );
    exit( 35 );
}
if ( ! @rename( $tmp, $target ) ) {
    @unlink( $tmp );
    fwrite( STDERR, 'CF7_GUARD_INSTALL_ABORT: cannot install guard' . "\n" );
    exit( 36 );
}
@chmod( $target, 0644 );
wp_cache_flush();

echo "CF7_GUARD_INSTALL_OK\n";
echo 'TARGET=' . $target . "\n";
echo 'BYTES=' . strlen( $code ) . "\n";
