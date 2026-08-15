<?php
/** Initialize TranslatePress gettext storage using the installed plugin's own API. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string) get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) { fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" ); exit( 2 ); }
if ( ! class_exists( 'TRP_Translate_Press' ) ) { fwrite( STDERR, "TranslatePress is not loaded.\n" ); exit( 3 ); }

$trp = TRP_Translate_Press::get_trp_instance();
$gm = $trp->get_component( 'gettext_manager' );
if ( ! is_object( $gm ) || ! method_exists( $gm, 'check_gettext_table' ) ) { fwrite( STDERR, "TranslatePress gettext manager API unavailable.\n" ); exit( 4 ); }

/* Core per-language gettext tables. check_gettext_table() is the plugin-native
 * initializer and delegates to create_gettext_table()/dbDelta when missing. */
foreach ( array( 'es_ES', 'en_US' ) as $language ) {
    $result = $gm->check_gettext_table( $language );
    echo "check_gettext_table({$language})=" . var_export( $result, true ) . PHP_EOL;
}

/* TranslatePress versions can expose auxiliary check/create methods under
 * slightly different names. Call only zero-argument public native checkers;
 * never construct their SQL ourselves. */
$reflection = new ReflectionClass( $gm );
$called = array();
foreach ( $reflection->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
    $name = $method->getName();
    if ( 'check_gettext_table' === $name ) { continue; }
    if ( 0 !== strpos( $name, 'check_' ) ) { continue; }
    if ( false === strpos( $name, 'gettext' ) && false === strpos( $name, 'original' ) ) { continue; }
    if ( 0 !== $method->getNumberOfRequiredParameters() ) { continue; }
    try {
        $value = $method->invoke( $gm );
        $called[] = $name;
        echo "{$name}()=" . var_export( $value, true ) . PHP_EOL;
    } catch ( Throwable $e ) {
        fwrite( STDERR, "Non-fatal auxiliary initializer {$name} failed: {$e->getMessage()}\n" );
    }
}

/* Verify the exact table names requested by the installed component. */
global $wpdb;
foreach ( array( 'es_ES', 'en_US' ) as $language ) {
    if ( ! method_exists( $gm, 'get_gettext_table_name' ) ) { continue; }
    $table = $gm->get_gettext_table_name( $language );
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
    echo "{$language} gettext table={$table} exists=" . ( $exists ? 'yes' : 'no' ) . PHP_EOL;
    if ( ! $exists ) { fwrite( STDERR, "TranslatePress failed to initialize {$table}.\n" ); exit( 5 ); }
}

wp_cache_flush();
echo 'Auxiliary native checkers invoked: ' . ( $called ? implode( ',', $called ) : 'none required' ) . PHP_EOL;
