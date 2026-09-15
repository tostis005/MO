<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Reviews_Schema {
	private const VERSION = '1.0.0';

	public static function maybe_install(): void {
		if ( self::VERSION !== (string) get_option( 'mdo_reviews_db_version', '' ) ) {
			self::install();
		}
	}

	private static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = MDO_Database::table( 'reviews' );
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source varchar(40) NOT NULL DEFAULT 'external',
			source_review_id varchar(191) DEFAULT NULL,
			source_key char(64) NOT NULL,
			content_fingerprint char(64) DEFAULT NULL,
			source_url text DEFAULT NULL,
			author_name varchar(191) DEFAULT NULL,
			author_email varchar(191) DEFAULT NULL,
			author_avatar_url text DEFAULT NULL,
			rating tinyint(3) unsigned DEFAULT NULL,
			review_title text DEFAULT NULL,
			review_text longtext NOT NULL,
			review_date datetime DEFAULT NULL,
			review_date_gmt datetime DEFAULT NULL,
			vendor_user_id bigint(20) unsigned DEFAULT NULL,
			suggested_vendor_user_id bigint(20) unsigned DEFAULT NULL,
			wc_product_id bigint(20) unsigned DEFAULT NULL,
			source_product_id bigint(20) unsigned DEFAULT NULL,
			wc_order_id bigint(20) unsigned DEFAULT NULL,
			assignment_type varchar(40) DEFAULT NULL,
			assignment_confidence decimal(5,4) NOT NULL DEFAULT 0,
			assignment_reason text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			validation_method varchar(20) DEFAULT NULL,
			validated_by bigint(20) unsigned DEFAULT NULL,
			validated_at datetime DEFAULT NULL,
			source_payload longtext DEFAULT NULL,
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_key (source_key),
			KEY content_fingerprint (content_fingerprint),
			KEY status_date (status, review_date),
			KEY vendor_status_date (vendor_user_id, status, review_date),
			KEY suggested_vendor (suggested_vendor_user_id),
			KEY wc_product_id (wc_product_id),
			KEY source_product_id (source_product_id),
			KEY wc_order_id (wc_order_id)
		) {$charset_collate};";
		dbDelta( $sql );
		update_option( 'mdo_reviews_db_version', self::VERSION, false );
	}
}
