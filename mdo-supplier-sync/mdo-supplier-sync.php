<?php
/**
 * Plugin Name: EMDO
 * Description: Gestión y sincronización de catálogos de proveedores con WooCommerce/WCFM.
 * Version: 1.0.28
 * Author: El Mercado de Origen
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: mdo-supplier-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.28' );
define( 'MDO_SUPPLIER_SYNC_DB_VERSION', '1.2.0' );
define( 'MDO_SUPPLIER_SYNC_FILE', __FILE__ );
define( 'MDO_SUPPLIER_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MDO_SUPPLIER_SYNC_URL', plugin_dir_url( __FILE__ ) );

require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-database.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-supplier-repository.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-pricing.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-iberico-variations.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-text.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-rich-description-source.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-tolecarnes.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-iberico-family.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-huerta-ana-mary-v4.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-huerta-defaults.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-huerta-catalog-quality.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-huerta-description-policy.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-huerta-unit-price.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-yith-extras.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-taxonomy.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-audit.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-rescue.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-final.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-precision.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-tag-closure.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-canonical-closure.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-ham-catalog-direct-closure.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-cured-catalog.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-cured-producer.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-adobados-catalog.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-accessories-catalog.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-auto-categorizer.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-variable-upgrade.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-description-guard.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-description-migration.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-product-slugs.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-stock-guard.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-scheduler.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-nightly-scheduler.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-admin.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-product-bulk-admin.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-minimum-order.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-shipping-destinations.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-catalog-ranking.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-catalog-priority-admin.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-tolecarnes-weight-info.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-promotions.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-specials.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-specials-router.php';
require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-home-specials.php';

register_activation_hook( __FILE__, array( 'MDO_Database', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MDO_Scheduler', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		MDO_Database::maybe_upgrade();
		MDO_Description_Migration::run_once();
		MDO_Pricing::init();
		MDO_Iberico_Variations::init();
		MDO_Rich_Description_Source::init();
		MDO_Ham_Taxonomy::init();
		MDO_Ham_Catalog_Audit::init();
		MDO_Ham_Catalog_Rescue::init();
		MDO_Ham_Catalog_Final::init();
		MDO_Ham_Catalog_Precision::init();
		MDO_Ham_Catalog_Tag_Closure::init();
		MDO_Ham_Catalog_Canonical_Closure::init();
		MDO_Ham_Catalog_Direct_Closure::init();
		MDO_Cured_Catalog::init();
		MDO_Cured_Producer::init();
		MDO_Adobados_Catalog::init();
		try {
			MDO_Adobados_Catalog::migrate_catalog( false );
		} catch ( Throwable $error ) {
			error_log( '[EMDO adobados catalog] Migración: ' . $error->getMessage() );
		}
		MDO_Accessories_Catalog::init();
		try {
			MDO_Accessories_Catalog::migrate_catalog( false );
		} catch ( Throwable $error ) {
			error_log( '[EMDO accessories catalog] Migración: ' . $error->getMessage() );
		}
		MDO_Variable_Upgrade::init();
		MDO_Description_Guard::init();
		MDO_Product_Slugs::init();
		MDO_Stock_Guard::init();
		MDO_Scheduler::init();
		MDO_Nightly_Scheduler::init();
		MDO_Huerta_Defaults::init();
		MDO_Huerta_Catalog_Quality::init();
		MDO_Huerta_Description_Policy::init();
		MDO_Huerta_Unit_Price::init();
		MDO_Minimum_Order::init();
		MDO_Shipping_Destinations::init();
		MDO_Tolecarnes_Weight_Info::init();
		MDO_Specials::init();
		MDO_Specials_Router::init();
		MDO_Home_Specials::init();
		MDO_Promotions::init();
		if ( is_admin() ) {
			MDO_Admin::init();
			MDO_Product_Bulk_Admin::init();
			MDO_Catalog_Priority_Admin::init();
		}
	}
);
