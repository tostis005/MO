<?php
/** Snapshot exactly the production data touched by the multilingual deployment. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string)get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) { fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" ); exit( 2 ); }
$file = getenv( 'MDO_BACKUP_FILE' );
if ( ! is_string( $file ) || '' === $file ) { fwrite( STDERR, "MDO_BACKUP_FILE missing\n" ); exit( 3 ); }

global $wpdb;
$snapshot = array(
    'created_at' => gmdate( 'c' ),
    'siteurl' => $siteurl,
    'active_plugins' => get_option( 'active_plugins', array() ),
    'trp_settings_exists' => false !== get_option( 'trp_settings', false ),
    'trp_settings' => get_option( 'trp_settings', null ),
    'trp_machine_translation_settings_exists' => false !== get_option( 'trp_machine_translation_settings', false ),
    'trp_machine_translation_settings' => get_option( 'trp_machine_translation_settings', null ),
    'oil_posts' => array(),
    'existing_trp_tables' => array(),
);
foreach ( array( 1056, 1220, 1599 ) as $id ) {
    $post = get_post( $id );
    if ( $post ) { $snapshot['oil_posts'][(string)$id] = array( 'post_content'=>$post->post_content, 'post_excerpt'=>$post->post_excerpt, 'post_modified'=>$post->post_modified, 'post_modified_gmt'=>$post->post_modified_gmt ); }
}
$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'trp_' ) . '%' ) );
foreach ( $tables as $table ) {
    if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) { continue; }
    $count = (int)$wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
    $snapshot['existing_trp_tables'][$table] = array( 'count'=>$count );
}
$json = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $json ) || false === file_put_contents( $file, $json ) ) { fwrite( STDERR, "Could not write backup snapshot\n" ); exit( 4 ); }
echo "Change-set backup: {$file}\n";
