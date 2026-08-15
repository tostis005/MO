<?php
/**
 * Persist manually reviewed ES -> EN translations into TranslatePress native tables.
 * Staging only. No machine translation.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$siteurl = (string) get_option( 'siteurl' );
if ( strpos( $siteurl, 'https://dev.elmercadodeorigen.com' ) !== 0 ) {
    fwrite( STDERR, "Refusing non-staging site: {$siteurl}\n" );
    exit( 2 );
}
if ( ! class_exists( 'TRP_Translate_Press' ) ) {
    fwrite( STDERR, "TranslatePress is not loaded.\n" );
    exit( 3 );
}

$dir = __DIR__;
$map_files = glob( $dir . '/*.json' );
sort( $map_files, SORT_STRING );
if ( empty( $map_files ) ) {
    fwrite( STDERR, "No reviewed translation maps found in {$dir}\n" );
    exit( 4 );
}
$translations = array();
foreach ( $map_files as $file ) {
    $decoded = json_decode( file_get_contents( $file ), true );
    if ( ! is_array( $decoded ) || JSON_ERROR_NONE !== json_last_error() ) {
        fwrite( STDERR, "Invalid JSON map {$file}: " . json_last_error_msg() . "\n" );
        exit( 5 );
    }
    foreach ( $decoded as $original => $translated ) {
        if ( ! is_string( $original ) || ! is_string( $translated ) ) { continue; }
        $original   = trim( $original );
        $translated = trim( $translated );
        if ( '' === $original || '' === $translated || $original === $translated ) { continue; }
        $translations[ $original ] = $translated;
    }
}

$trp   = TRP_Translate_Press::get_trp_instance();
$query = $trp->get_component( 'query' );
if ( ! is_object( $query ) || ! method_exists( $query, 'check_table' ) || ! method_exists( $query, 'update_strings' ) ) {
    fwrite( STDERR, "TranslatePress query component is unavailable.\n" );
    exit( 6 );
}
$query->check_table( 'es_ES', 'en_US' );

$human_status = method_exists( $query, 'get_constant_human_reviewed' ) ? (int) $query->get_constant_human_reviewed() : 2;
$regular_type = method_exists( $query, 'get_constant_block_type_regular_string' ) ? (int) $query->get_constant_block_type_regular_string() : 0;
$table = $query->get_table_name( 'en_US', 'es_ES' );

global $wpdb;
if ( empty( $table ) || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
    fwrite( STDERR, "Native dictionary table is missing: {$table}\n" );
    exit( 7 );
}

$updates = array();
$inserted = 0;
$existing = 0;
foreach ( $translations as $original => $translated ) {
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE original = %s ORDER BY id ASC", $original ) );
    if ( empty( $ids ) ) {
        $query->insert_strings( array( $original ), 'en_US', $regular_type );
        $ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE original = %s ORDER BY id ASC", $original ) );
        $inserted++;
    } else {
        $existing++;
    }
    foreach ( $ids as $id ) {
        $updates[] = array(
            'id'         => (int) $id,
            'translated' => $translated,
            'status'     => $human_status,
            'block_type' => $regular_type,
        );
    }
}

foreach ( array_chunk( $updates, 80 ) as $chunk ) {
    $query->update_strings( $chunk, 'en_US', array( 'id', 'translated', 'status', 'block_type' ) );
}

/*
 * Theme/plugin gettext strings live in a second TranslatePress native table.
 * Only update rows TranslatePress has actually discovered so their domain,
 * original_id and plural metadata remain intact. No runtime translation filter.
 */
$gettext_updates = array();
if ( method_exists( $query, 'check_gettext_table' ) && method_exists( $query, 'get_gettext_table_name' ) ) {
    $query->check_gettext_table( 'en_US' );
    $gettext_table = $query->get_gettext_table_name( 'en_US' );
    foreach ( $translations as $original => $translated ) {
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, original, domain FROM `{$gettext_table}` WHERE original = %s ORDER BY id ASC",
            $original
        ), ARRAY_A );
        foreach ( $rows as $row ) {
            $gettext_updates[] = array(
                'id'         => (int) $row['id'],
                'original'   => (string) $row['original'],
                'translated' => $translated,
                'domain'     => (string) $row['domain'],
                'status'     => $human_status,
            );
        }
    }
    if ( ! empty( $gettext_updates ) && method_exists( $query, 'update_gettext_strings' ) ) {
        foreach ( array_chunk( $gettext_updates, 100 ) as $chunk ) {
            $query->update_gettext_strings( $chunk, 'en_US', array( 'id', 'original', 'translated', 'domain', 'status' ) );
        }
    }
}

wp_cache_flush();
echo 'Reviewed map files: ' . count( $map_files ) . "\n";
echo 'Reviewed translation pairs: ' . count( $translations ) . "\n";
echo "Dictionary rows inserted for new originals: {$inserted}\n";
echo "Dictionary originals already present: {$existing}\n";
echo 'Dictionary row updates: ' . count( $updates ) . "\n";
echo 'Existing gettext rows updated natively: ' . count( $gettext_updates ) . "\n";
echo "TranslatePress status used: {$human_status}\n";
