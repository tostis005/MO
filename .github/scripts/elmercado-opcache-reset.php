<?php
/**
 * Ephemeral deployment probe for PHP-FPM OPcache invalidation.
 *
 * This file is copied to a random production-root filename only during a
 * guarded deployment, executes once, and removes itself immediately.
 */
header( 'Content-Type: text/plain; charset=UTF-8' );
header( 'Cache-Control: no-store, max-age=0' );

$ok = function_exists( 'opcache_reset' ) ? opcache_reset() : false;
clearstatcache( true );

echo $ok ? "OPCACHE_RESET_OK\n" : "OPCACHE_RESET_UNAVAILABLE\n";

@unlink( __FILE__ );
