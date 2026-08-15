<?php
/**
 * Staging-only: initialize and repair TranslatePress native ES -> EN storage.
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
$required = array( 'check_table', 'get_table_name', 'check_original_table', 'check_original_meta_table', 'get_table_name_for_original_strings' );
foreach ( $required as $method ) {
    if ( ! is_object( $query ) || ! method_exists( $query, $method ) ) {
        fwrite( STDERR, "TranslatePress query method unavailable: {$method}\n" );
        exit( 4 );
    }
}

// These are TranslatePress's own schema-creation methods. The staging database was
// missing the regular original_strings table even though the language dictionary existed.
$query->check_original_table();
$query->check_original_meta_table();
$query->check_table( 'es_ES', 'en_US' );

$table     = $query->get_table_name( 'en_US', 'es_ES' );
$originals = $query->get_table_name_for_original_strings();

global $wpdb;
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $originals ) ) !== $originals ) {
    fwrite( STDERR, "TranslatePress original strings table still missing: {$originals}\n" );
    exit( 5 );
}

// Backfill original ids for dictionary rows that pre-dated the repaired original table.
if ( method_exists( $query, 'original_ids_insert' ) && method_exists( $query, 'original_ids_reindex' ) ) {
    $max_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM `{$table}`" );
    $batch  = 500;
    for ( $lower = 0; $lower < $max_id; $lower += $batch ) {
        $query->original_ids_insert( 'en_US', $lower, $batch );
    }
    if ( method_exists( $query, 'original_ids_cleanup' ) ) {
        $query->original_ids_cleanup();
    }
    for ( $lower = 0; $lower < $max_id; $lower += $batch ) {
        $query->original_ids_reindex( 'en_US', $lower, $batch );
    }
}

$original_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$originals}`" );
$linked_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE original_id IS NOT NULL" );
$null_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE original_id IS NULL" );

echo "Native dictionary: {$table}\n";
echo "Original strings table: {$originals}\n";
echo "Original strings rows: {$original_count}\n";
echo "Dictionary rows linked to original_id: {$linked_count}\n";
echo "Dictionary rows without original_id: {$null_count}\n";
