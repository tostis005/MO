<?php
/**
 * Scope: Tropitop vendor WCFM 4971 / EMDO #8 only.
 * 10% EMDO revenue share, 90% producer share (current WCFM commission_for=vendor).
 * Idempotent, does not activate seller or Woo products.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
foreach ( array( 'home', 'siteurl' ) as $option ) {
    $host = strtolower( (string) wp_parse_url( (string) get_option( $option ), PHP_URL_HOST ) );
    if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'Refusing to update a non-production site.' );
    }
}
global $wpdb;
$table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE code=%s LIMIT 1", 'tropitop' ), ARRAY_A );
if ( ! $supplier || (int) $supplier['id'] !== 8 || (int) $supplier['vendor_user_id'] !== 4971 ) {
    throw new RuntimeException( 'Unexpected supplier/vendor identity.' );
}
$vendor = get_user_by( 'id', 4971 );
if ( ! $vendor || $vendor->user_login !== 'tropitop' || ! in_array( 'wcfm_vendor', (array) $vendor->roles, true ) ) {
    throw new RuntimeException( 'Unexpected WCFM vendor account.' );
}
$options = get_option( 'wcfm_commission_options', array() );
if ( empty( $options['commission_for'] ) ) { $options = get_option( 'wcfmmp_commission_options', array() ); }
if ( ! is_array( $options ) || ! in_array( $options['commission_for'] ?? '', array( 'vendor', 'admin' ), true ) ) {
    throw new RuntimeException( 'Cannot determine WCFM commission payout direction.' );
}
$direction = (string) $options['commission_for'];
$vendor_percent = $direction === 'vendor' ? '90' : '10';
$previous_commission = get_user_meta( 4971, '_wcfmmp_commission', true );
$profile = get_user_meta( 4971, 'wcfmmp_profile_settings', true );
$profile = is_array( $profile ) ? $profile : array();
$commission = array(
    'commission_mode' => 'percent',
    'commission_percent' => $vendor_percent,
    'commission_fixed' => '',
    'tax_name' => '',
    'tax_percent' => '',
);
if ( false === $wpdb->update( $table, array(
    'commercial_rule' => 'percentage',
    'commission_percent' => '10.0000',
    'notes' => 'Acuerdo confirmado: El Mercado de Origen 10% de comisión sobre productos y Tropitop 90%. Portes independientes. Proveedor pendiente de publicación.',
    'updated_at' => current_time( 'mysql' ),
), array( 'id' => 8 ) ) ) { throw new RuntimeException( 'Failed to persist EMDO commission.' ); }
update_user_meta( 4971, '_wcfmmp_commission', $commission );
$profile['commission'] = $commission;
update_user_meta( 4971, 'wcfmmp_profile_settings', $profile );
$stored = $wpdb->get_row( $wpdb->prepare( "SELECT active,commercial_rule,commission_percent FROM {$table} WHERE id=%d", 8 ), ARRAY_A );
$comm_now = get_user_meta( 4971, '_wcfmmp_commission', true );
$profile_now = get_user_meta( 4971, 'wcfmmp_profile_settings', true );
if ( ! $stored || $stored['commercial_rule'] !== 'percentage' || abs( (float)$stored['commission_percent'] - 10.0 ) > 0.001 ||
    ! is_array( $comm_now ) || (string) ( $comm_now['commission_percent'] ?? '' ) !== $vendor_percent ||
    ! is_array( $profile_now ) || ( $profile_now['commission'] ?? null ) !== $commission ) {
    throw new RuntimeException( 'Post-write commission validation failed.' );
}
echo 'TROPITOP_COMMISSION_10_APPLIED=' . wp_json_encode( array(
    'supplier_id' => 8,
    'vendor_id' => 4971,
    'emdo_percent' => 10,
    'wcfm_commission_for' => $direction,
    'wcfm_configured_percent' => (int) $vendor_percent,
    'supplier_active' => (int) $stored['active'],
    'seller_disabled' => (bool) get_user_meta( 4971, '_disable_vendor', true ),
    'previous_vendor_commission' => $previous_commission,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
