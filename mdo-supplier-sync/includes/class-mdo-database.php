<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Database {
	public static function activate(): void {
		self::install_schema();
		MDO_Scheduler::activate();
	}

	public static function maybe_upgrade(): void {
		$current = (string) get_option( 'mdo_supplier_sync_db_version', '' );
		if ( MDO_SUPPLIER_SYNC_DB_VERSION !== $current ) {
			self::install_schema();
		}
	}

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'mdo_' . $name;
	}

	private static function install_schema(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$suppliers = self::table( 'suppliers' );
		$products  = self::table( 'source_products' );
		$runs      = self::table( 'sync_runs' );
		$events    = self::table( 'sync_events' );

		$sql_suppliers = "CREATE TABLE {$suppliers} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(80) NOT NULL,
			name varchar(191) NOT NULL,
			source_url text NOT NULL,
			vendor_user_id bigint(20) unsigned DEFAULT NULL,
			connector varchar(80) NOT NULL DEFAULT 'none',
			commercial_rule varchar(40) NOT NULL DEFAULT 'percentage',
			commission_percent decimal(8,4) DEFAULT NULL,
			fixed_fee decimal(12,2) DEFAULT NULL,
			fixed_fee_scope varchar(20) NOT NULL DEFAULT 'order',
			minimum_order_amount decimal(12,2) DEFAULT NULL,
			currency char(3) NOT NULL DEFAULT 'EUR',
			sync_frequency varchar(20) NOT NULL DEFAULT 'weekly',
			notification_email varchar(191) DEFAULT NULL,
			exclusion_url_fragments longtext DEFAULT NULL,
			notes longtext DEFAULT NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			last_sync_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY vendor_user_id (vendor_user_id),
			KEY active (active)
		) {$charset_collate};";

		$sql_products = "CREATE TABLE {$products} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			supplier_id bigint(20) unsigned NOT NULL,
			source_key char(64) NOT NULL,
			source_url text NOT NULL,
			source_product_id varchar(191) DEFAULT NULL,
			wc_product_id bigint(20) unsigned DEFAULT NULL,
			title text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			source_price decimal(12,2) DEFAULT NULL,
			source_stock_status varchar(30) DEFAULT NULL,
			source_hash char(64) DEFAULT NULL,
			source_payload longtext DEFAULT NULL,
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			last_changed_at datetime DEFAULT NULL,
			last_error longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY supplier_source (supplier_id, source_key),
			KEY supplier_status (supplier_id, status),
			KEY wc_product_id (wc_product_id)
		) {$charset_collate};";

		$sql_runs = "CREATE TABLE {$runs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			supplier_id bigint(20) unsigned NOT NULL,
			trigger_type varchar(20) NOT NULL DEFAULT 'scheduled',
			status varchar(20) NOT NULL DEFAULT 'running',
			started_at datetime NOT NULL,
			finished_at datetime DEFAULT NULL,
			products_found int(11) unsigned NOT NULL DEFAULT 0,
			products_new int(11) unsigned NOT NULL DEFAULT 0,
			products_updated int(11) unsigned NOT NULL DEFAULT 0,
			products_excluded int(11) unsigned NOT NULL DEFAULT 0,
			errors_count int(11) unsigned NOT NULL DEFAULT 0,
			message longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY supplier_started (supplier_id, started_at),
			KEY status (status)
		) {$charset_collate};";

		$sql_events = "CREATE TABLE {$events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run_id bigint(20) unsigned DEFAULT NULL,
			supplier_id bigint(20) unsigned NOT NULL,
			source_product_id bigint(20) unsigned DEFAULT NULL,
			wc_product_id bigint(20) unsigned DEFAULT NULL,
			event_type varchar(40) NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'info',
			message longtext NOT NULL,
			payload longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY run_id (run_id),
			KEY supplier_created (supplier_id, created_at),
			KEY severity (severity)
		) {$charset_collate};";

		dbDelta( $sql_suppliers );
		dbDelta( $sql_products );
		dbDelta( $sql_runs );
		dbDelta( $sql_events );
		update_option( 'mdo_supplier_sync_db_version', MDO_SUPPLIER_SYNC_DB_VERSION, true );
	}
}
