<?php
/**
 * Ephemeral production runtime probe used only by guarded deployments.
 *
 * Copied to a random document-root filename, loaded once through PHP-FPM and
 * removed immediately. It verifies the active theme code that the web runtime
 * actually sees rather than inferring it from optimized Home HTML.
 */
header( 'Content-Type: text/plain; charset=UTF-8' );
header( 'Cache-Control: no-store, max-age=0' );
header( 'Pragma: no-cache' );

require_once __DIR__ . '/wp-load.php';

$version = defined( 'ELMERCADO_THEME_VERSION' ) ? (string) ELMERCADO_THEME_VERSION : 'missing';
$helper  = function_exists( 'elmercado_home_public_category_count_010212' ) ? '1' : '0';

printf( "theme=%s\nhelper=%s\n", $version, $helper );

@unlink( __FILE__ );
