<?php
/** Run the batch publisher under a directly bootstrapped WordPress runtime. */
$root = rtrim( (string) getenv( 'EMDO_WP_ROOT' ), '/\\' );
$publisher = (string) getenv( 'EMDO_PUBLISHER_FILE' );
if ( $root === '' || ! is_file( $root . '/wp-load.php' ) ) {
    fwrite( STDERR, "EMDO_BOOTSTRAP_MISSING\n" );
    exit( 20 );
}
if ( $publisher === '' || ! is_file( $publisher ) ) {
    fwrite( STDERR, "EMDO_PUBLISHER_MISSING\n" );
    exit( 21 );
}

/* Reproduce the execution context used by WP-CLI so plugins do not treat this
 * as a half-formed web request while WordPress is bootstrapping. */
if ( ! defined( 'WP_CLI' ) ) {
    define( 'WP_CLI', true );
}
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.elmercadodeorigen.com';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'www.elmercadodeorigen.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '443';

require_once $root . '/wp-load.php';
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "EMDO_ABSPATH_MISSING\n" );
    exit( 22 );
}
fwrite( STDERR, "EMDO_BOOTSTRAP_OK\n" );
require $publisher;
