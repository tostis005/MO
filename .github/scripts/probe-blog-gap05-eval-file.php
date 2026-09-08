<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 2 ); }
$payload = array(
    'ok' => true,
    'siteurl' => get_option( 'siteurl' ),
    'php' => PHP_VERSION,
    'timestamp' => gmdate( 'c' ),
);
file_put_contents( '/tmp/emdo-gap05-eval-probe.json', wp_json_encode( $payload ) );
echo "EMDO_GAP05_PROBE_OK\n";
