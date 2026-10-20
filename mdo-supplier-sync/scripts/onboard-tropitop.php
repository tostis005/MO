<?php
/**
 * Seed an inactive Tropitop supplier in the EMDO source registry.
 *
 * Execute on the site's WordPress runtime with WP-CLI:
 *   wp eval-file mdo-supplier-sync/scripts/onboard-tropitop.php
 *   MDO_TROPITOP_VENDOR_ID=123 wp eval-file mdo-supplier-sync/scripts/onboard-tropitop.php
 *
 * This intentionally does not create a fake WCFM account, enter unverified
 * commercial terms, scrape copyrighted media or make products purchasable.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Bootstrap only the data classes when WP-CLI is run with --skip-plugins,
 // avoiding resource-heavy unrelated front-end plugins during provisioning.
if ( ! class_exists( 'MDO_Database' ) ) {
    require_once dirname( __DIR__ ) . '/includes/class-mdo-database.php';
}
if ( ! class_exists( 'MDO_Supplier_Repository' ) ) {
    require_once dirname( __DIR__ ) . '/includes/class-mdo-supplier-repository.php';
}
if ( ! class_exists( 'MDO_Supplier_Repository' ) ) {
    WP_CLI::error( 'El repositorio EMDO no está disponible.' );
}
global $wpdb;
$table = MDO_Database::table( 'suppliers' );
$existing = $wpdb->get_row(
    $wpdb->prepare( "SELECT id, vendor_user_id FROM {$table} WHERE code = %s LIMIT 1", 'tropitop' ),
    ARRAY_A
);
if ( $existing ) {
    WP_CLI::success( 'Tropitop ya está registrado en EMDO (ID ' . (int) $existing['id'] . '). Sin cambios.' );
    return;
}

$vendor_id = absint( getenv( 'MDO_TROPITOP_VENDOR_ID' ) ?: 0 );
if ( $vendor_id ) {
    $vendor = get_user_by( 'id', $vendor_id );
    if ( ! $vendor || ! in_array( 'wcfm_vendor', (array) $vendor->roles, true ) ) {
        WP_CLI::error( 'El ID de vendedor indicado no corresponde a un usuario WCFM válido.' );
    }
}
$id = MDO_Supplier_Repository::save( array(
    'code'               => 'tropitop',
    'name'               => 'Tropitop',
    'source_url'         => 'https://tropitop.com/es/aguacates/33-3063-comprar-aguacate-bacon.html',
    'vendor_user_id'     => $vendor_id ?: null,
    'connector'          => 'tropitop',
    'commercial_rule'    => 'custom',
    'commission_percent' => null,
    'fixed_fee'          => null,
    'minimum_order_amount' => null,
    'currency'           => 'EUR',
    'sync_frequency'     => 'manual',
    'notification_email' => '',
    'notes'              => 'NATURE TROPICAL FRUIT, S.L.U. · B44531150 · Les Alqueries, Castellón. Envíos, precios de variantes, cuenta WCFM, relación comercial y permiso de imágenes pendientes de validación; NO ACTIVAR venta ni cron sin pasar QA.',
    'active'             => 0,
) );
if ( ! $id ) { WP_CLI::error( 'No se pudo crear el registro del proveedor.' ); }
WP_CLI::success( 'Creada ficha EMDO inactiva Tropitop #' . $id . '. Asignar vendedor WCFM y validar logística antes de activar.' );
