<?php
/** Temporary harmless no-op for the historical editorial v2 workflow while it is reused as a production image launcher. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts = array();
for ( $i = 1; $i <= 6; $i++ ) {
    $posts[] = array( 'slot' => $i, 'status' => 'no-op' );
}
echo wp_json_encode( array( 'revision' => 2, 'posts' => $posts, 'no_op' => true ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
