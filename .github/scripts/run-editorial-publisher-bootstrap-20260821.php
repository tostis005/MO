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
require_once $root . '/wp-load.php';
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "EMDO_ABSPATH_MISSING\n" );
    exit( 22 );
}
fwrite( STDERR, "EMDO_BOOTSTRAP_OK\n" );
require $publisher;
