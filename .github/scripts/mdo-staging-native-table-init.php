<?php
/**
 * Staging-only: initialize TranslatePress native ES -> EN dictionary table.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = get_option( 'siteurl' );
if ( strpos( (string) $siteurl, 'https://dev.elmercadodeorigen.com' ) !== 0 ) {
    fwrite( STDERR, "Refusing non-staging site: {$siteurl}\n" );
    exit( 2 );
}
if ( ! class_exists( 'TRP_Translate_Press' ) ) {
    fwrite( STDERR, "TranslatePress class not loaded\n" );
    exit( 3 );
}
$trp = TRP_Translate_Press::get_trp_instance();
$query = $trp->get_component( 'query' );
if ( ! is_object( $query ) || ! method_exists( $query, 'check_table' ) || ! method_exists( $query, 'get_table_name' ) ) {
    fwrite( STDERR, "TranslatePress query component unavailable\n" );
    exit( 4 );
}
$query->check_table( 'es_ES', 'en_US' );
$table = $query->get_table_name( 'en_US', 'es_ES' );
echo "Native dictionary: {$table}\n";
