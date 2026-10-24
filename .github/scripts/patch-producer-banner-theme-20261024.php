<?php
/**
 * Safely register the shared producer-banner module in a production child theme.
 * Preserve every other production function and theme module as-is.
 */
if ( PHP_SAPI !== 'cli' || count( $argv ) !== 2 ) {
    fwrite( STDERR, "Usage: php patch-producer-banner-theme.php /absolute/path/functions.php\n" );
    exit( 2 );
}
$path = $argv[1];
if ( ! is_file( $path ) || ! is_writable( $path ) ) {
    fwrite( STDERR, "functions.php not found or not writable\n" );
    exit( 3 );
}
$current = file_get_contents( $path );
if ( false === $current ) {
    fwrite( STDERR, "Could not read functions.php\n" );
    exit( 4 );
}
$module = "'inc/producer-banner-title-unified-20261024.php',";
if ( str_contains( $current, $module ) ) {
    echo "PRODUCER_BANNER_MODULE_ALREADY_REGISTERED\n";
    exit( 0 );
}
$anchor = "'inc/vendor-home-verification-three.php',";
if ( ! str_contains( $current, $anchor ) ) {
    fwrite( STDERR, "Expected vendor header module registration is missing\n" );
    exit( 5 );
}
$replacement = $anchor . "\n\t" . $module;
$updated = str_replace( $anchor, $replacement, $current, $count );
if ( 1 !== $count || false === file_put_contents( $path, $updated, LOCK_EX ) ) {
    fwrite( STDERR, "Refusing ambiguous or unsuccessful functions.php patch\n" );
    exit( 6 );
}
echo "PRODUCER_BANNER_MODULE_REGISTERED\n";
