<?php
/** Capture the batch-05 publisher result even when WP-CLI stdout is noisy/suppressed. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
ob_start();
require __DIR__ . '/publish-blog-master-gap-05-production.php';
$output = (string) ob_get_clean();
if ( '' === trim( $output ) ) {
    throw new RuntimeException( 'Batch-05 publisher produced no buffered output.' );
}
$path = __DIR__ . '/publisher-output.txt';
if ( false === file_put_contents( $path, $output ) ) {
    throw new RuntimeException( 'Could not persist batch-05 publisher output.' );
}
echo $output;
