<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Relación muchos-a-muchos entre reseñas EMDO y tiendas WCFM.
 *
 * Se mantienen vendor_user_id/suggested_vendor_user_id en wp_mdo_reviews como
 * compatibilidad y como tienda primaria, pero la tabla de relación es la
 * fuente de verdad cuando una reseña pertenece a varias tiendas.
 */
final class MDO_Reviews_Vendors {
	private const VERSION = '1.0.0';
	private const OPTION = 'mdo_review_vendors_version';

	public static function init(): void {
		self::maybe_install();
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mdo_review_vendors';
	}

	public static function maybe_install(): void {
		if ( self::VERSION === (string) get_option( self::OPTION, '' ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::table();
		$charset_collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				review_id bigint(20) unsigned NOT NULL,
				vendor_user_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (review_id,vendor_user_id),
				KEY vendor_review (vendor_user_id,review_id)
			) {$charset_collate};"
		);
		self::backfill_legacy_assignments();
		update_option( self::OPTION, self::VERSION, false );
	}

	public static function backfill_legacy_assignments(): void {
		global $wpdb;
		$reviews = MDO_Database::table( 'reviews' );
		$table = self::table();
		$now = current_time( 'mysql' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (review_id,vendor_user_id,created_at,updated_at)
				 SELECT id,vendor_user_id,%s,%s FROM {$reviews}
				 WHERE vendor_user_id IS NOT NULL AND vendor_user_id>0",
				$now,
				$now
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (review_id,vendor_user_id,created_at,updated_at)
				 SELECT id,suggested_vendor_user_id,%s,%s FROM {$reviews}
				 WHERE suggested_vendor_user_id IS NOT NULL AND suggested_vendor_user_id>0",
				$now,
				$now
			)
		);
	}

	public static function get( int $review_id, $review = null ): array {
		global $wpdb;
		$table = self::table();
		$ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT vendor_user_id FROM {$table} WHERE review_id=%d ORDER BY vendor_user_id ASC", $review_id ) ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( $ids ) {
			return $ids;
		}
		if ( null === $review ) {
			$reviews = MDO_Database::table( 'reviews' );
			$review = $wpdb->get_row( $wpdb->prepare( "SELECT vendor_user_id,suggested_vendor_user_id FROM {$reviews} WHERE id=%d", $review_id ) );
		}
		if ( $review ) {
			foreach ( array( (int) ( $review->vendor_user_id ?? 0 ), (int) ( $review->suggested_vendor_user_id ?? 0 ) ) as $id ) {
				if ( $id > 0 ) {
					$ids[] = $id;
				}
		}
		}
		return array_values( array_unique( $ids ) );
	}

	public static function replace( int $review_id, array $vendor_ids ): bool {
		global $wpdb;
		$table = self::table();
		$vendor_ids = array_values( array_unique( array_filter( array_map( 'absint', $vendor_ids ) ) ) );
		$wpdb->query( 'START TRANSACTION' );
		try {
			if ( false === $wpdb->delete( $table, array( 'review_id' => $review_id ), array( '%d' ) ) ) {
				throw new RuntimeException( 'No se pudieron limpiar las tiendas previas.' );
			}
			$now = current_time( 'mysql' );
			foreach ( $vendor_ids as $vendor_id ) {
				$ok = $wpdb->insert(
					$table,
					array(
						'review_id' => $review_id,
						'vendor_user_id' => $vendor_id,
						'created_at' => $now,
						'updated_at' => $now,
					),
					array( '%d', '%d', '%s', '%s' )
				);
				if ( false === $ok ) {
					throw new RuntimeException( 'No se pudo guardar una de las tiendas.' );
				}
			}
			$wpdb->query( 'COMMIT' );
			return true;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[EMDO reviews vendors] ' . $error->getMessage() );
			return false;
		}
	}

	public static function add( int $review_id, int $vendor_id ): bool {
		if ( $review_id < 1 || $vendor_id < 1 ) {
			return false;
		}
		$ids = self::get( $review_id );
		$ids[] = $vendor_id;
		return self::replace( $review_id, $ids );
	}

	public static function ids_for_reviews( array $review_ids ): array {
		global $wpdb;
		$review_ids = array_values( array_unique( array_filter( array_map( 'absint', $review_ids ) ) ) );
		$result = array();
		if ( ! $review_ids ) {
			return $result;
		}
		$table = self::table();
		$placeholders = implode( ',', array_fill( 0, count( $review_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT review_id,vendor_user_id FROM {$table} WHERE review_id IN ({$placeholders}) ORDER BY vendor_user_id ASC", ...$review_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $rows as $row ) {
			$result[ (int) $row->review_id ][] = (int) $row->vendor_user_id;
		}
		return $result;
	}

	public static function review_matches_vendor_sql( string $review_alias = 'r' ): string {
		$table = self::table();
		return "({$review_alias}.vendor_user_id=%d OR {$review_alias}.suggested_vendor_user_id=%d OR EXISTS (SELECT 1 FROM {$table} rv WHERE rv.review_id={$review_alias}.id AND rv.vendor_user_id=%d))";
	}

	public static function review_unassigned_sql( string $review_alias = 'r' ): string {
		$table = self::table();
		return "(COALESCE({$review_alias}.vendor_user_id,0)=0 AND COALESCE({$review_alias}.suggested_vendor_user_id,0)=0 AND NOT EXISTS (SELECT 1 FROM {$table} rv WHERE rv.review_id={$review_alias}.id))";
	}
}
