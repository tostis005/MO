<?php
/**
 * Plugin Name: EMDO
 * Description: Gestión y sincronización de catálogos de proveedores con WooCommerce/WCFM.
 * Version: 0.5.0
 * Author: El Mercado de Origen
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: mdo-supplier-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MDO_SUPPLIER_SYNC_VERSION', '0.5.0' );
define( 'MDO_SUPPLIER_SYNC_DB_VERSION', '1.2.0' );
define( 'MDO_SUPPLIER_SYNC_FILE', __FILE__ );
define( 'MDO_SUPPLIER_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MDO_SUPPLIER_SYNC_URL', plugin_dir_url( __FILE__ ) );

require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-database.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-supplier-repository.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-text.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-tolecarnes.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-iberico-family.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-scheduler.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-admin.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-product-bulk-admin.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-minimum-order.php';

register_activation_hook( __FILE__, array( 'MDO_Database', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MDO_Scheduler', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		MDO_Database::maybe_upgrade();
		MDO_Scheduler::init();
		MDO_Minimum_Order::init();
		if ( is_admin() ) {
			MDO_Admin::init();
			MDO_Product_Bulk_Admin::init();
		}
	}
);
