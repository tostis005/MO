<?php
/** Initialize production TranslatePress gettext storage from the live plugin schema. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string) get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) {
    fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" );
    exit( 2 );
}
if ( ! class_exists( 'TRP_Translate_Press' ) ) {
    fwrite( STDERR, "TranslatePress is not loaded.\n" );
    exit( 3 );
}

global $wpdb;
$es_table = $wpdb->prefix . 'trp_gettext_es_es';
$en_table = $wpdb->prefix . 'trp_gettext_en_us';
$dictionary_table = $wpdb->prefix . 'trp_dictionary_es_es_en_us';

$exists = static function ( $table ) use ( $wpdb ) {
    return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
};

if ( ! $exists( $es_table ) ) {
    fwrite( STDERR, "Source TranslatePress gettext table missing: {$es_table}\n" );
    exit( 4 );
}

if ( ! $exists( $en_table ) ) {
    echo "Creating {$en_table} from live schema {$es_table}\n";
    $sql = 'CREATE TABLE `' . str_replace( '`', '``', $en_table ) . '` LIKE `' . str_replace( '`', '``', $es_table ) . '`';
    $result = $wpdb->query( $sql );
    if ( false === $result || ! $exists( $en_table ) ) {
        fwrite( STDERR, "Failed creating {$en_table}: {$wpdb->last_error}\n" );
        exit( 5 );
    }
} else {
    echo "English gettext table already exists: {$en_table}\n";
}

foreach ( array( $es_table, $en_table, $dictionary_table ) as $table ) {
    $ok = $exists( $table );
    echo "{$table} exists=" . ( $ok ? 'yes' : 'no' ) . PHP_EOL;
    if ( ! $ok ) {
        fwrite( STDERR, "Required TranslatePress table missing: {$table}\n" );
        exit( 6 );
    }
}

wp_cache_flush();
echo "TranslatePress gettext storage ready.\n";
