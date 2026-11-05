<?php
/**
 * Club EMDO · Fase 1: recompensas de compras y ajustes de saldo.
 *
 * No se aceptan recargas, tarjetas regalo ni pagos con monedero en esta fase.
 * Los importes son céntimos enteros. Todos los movimientos son auditables.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Rewards_Ledger {
	private const SCHEMA_VERSION = '1';

	public static function wallets_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mdo_club_rewards_wallets';
	}
	public static function entries_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mdo_club_rewards_entries';
	}
	public static function maybe_install(): void {
		if ( self::SCHEMA_VERSION === get_option( 'mdo_club_rewards_schema_version' ) ) { return; }
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$wallets = self::wallets_table();
		$entries = self::entries_table();
		dbDelta( "CREATE TABLE {$wallets} (
			user_id bigint(20) unsigned NOT NULL,
			balance_cents bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (user_id)
		) ENGINE=InnoDB {$charset};" );
		dbDelta( "CREATE TABLE {$entries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			delta_cents bigint(20) NOT NULL,
			type varchar(24) NOT NULL,
			event_key varchar(128) NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			admin_id bigint(20) unsigned NOT NULL DEFAULT 0,
			note varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_key (event_key),
			KEY user_id (user_id),
			KEY order_id (order_id)
		) ENGINE=InnoDB {$charset};" );
		update_option( 'mdo_club_rewards_schema_version', self::SCHEMA_VERSION, false );
	}

	public static function format_amount( int $cents ): string {
		return number_format( $cents / 100, 2, '.', '' );
	}
	/** Signed input in euros, max 5,000 euros per manual adjustment. */
	public static function parse_adjustment( $value ): int {
		if ( ! is_string( $value ) || ! preg_match( '/^[+-]?\d{1,4}(?:[.,]\d{1,2})?$/', trim( $value ) ) ) {
			return 0;
		}
		$amount = (int) round( (float) str_replace( ',', '.', trim( $value ) ) * 100 );
		return abs( $amount ) <= 500000 ? $amount : 0;
	}
	public static function balance( int $user_id ): int {
		if ( $user_id <= 0 ) { return 0; }
		global $wpdb;
		return max( 0, (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT balance_cents FROM ' . self::wallets_table() . ' WHERE user_id=%d', $user_id
		) ) );
	}
	/**
	 * Atomic/idempotent credit or debit. Returns false for insufficient funds.
	 * Unique event keys prevent double rewards from simultaneous payment hooks.
	 */
	public static function change( int $user_id, int $delta, string $event_key, string $type, int $order_id = 0, int $admin_id = 0, string $note = '' ): bool {
		if ( $user_id <= 0 || 0 === $delta || '' === $event_key || strlen( $event_key ) > 128 ) { return false; }
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$inserted = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::entries_table() .
			' (user_id,delta_cents,type,event_key,order_id,admin_id,note,created_at) VALUES (%d,%d,%s,%s,%d,%d,%s,%s)',
			$user_id, $delta, $type, $event_key, $order_id, $admin_id, substr( $note, 0, 255 ), current_time( 'mysql', true )
		) );
		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
		if ( 0 === $inserted ) {
			// The event is already recorded. An individual event is credited once.
			$wpdb->query( 'COMMIT' );
			return true;
		}
		$created = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::wallets_table() . ' (user_id,balance_cents) VALUES (%d,0)', $user_id
		) );
		$updated = false;
		if ( false !== $created ) {
			$updated = $wpdb->query( $wpdb->prepare(
				'UPDATE ' . self::wallets_table() . ' SET balance_cents=balance_cents+(%d) WHERE user_id=%d AND balance_cents+(%d)>=0',
				$delta, $user_id, $delta
			) );
		}
		if ( 1 !== $updated ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
		$wpdb->query( 'COMMIT' );
		return true;
	}
	/** @return array<int,array<string,mixed>> */
	public static function movements( int $user_id, int $max = 25 ): array {
		if ( $user_id <= 0 ) { return array(); }
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT delta_cents,type,order_id,admin_id,note,created_at FROM ' . self::entries_table() .
			' WHERE user_id=%d ORDER BY id DESC LIMIT %d', $user_id, min( 100, max( 1, $max ) )
		), ARRAY_A ) ?: array();
	}
	/** @param int[] $ids @return array<int,int> */
	public static function balances_for_users( array $ids ): array {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		if ( ! $ids ) { return array(); }
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT user_id,balance_cents FROM ' . self::wallets_table() . ' WHERE user_id IN (' . $placeholders . ')',
			$ids
		), ARRAY_A ) ?: array();
		$balances = array_fill_keys( $ids, 0 );
		foreach ( $rows as $row ) { $balances[ (int) $row['user_id'] ] = (int) $row['balance_cents']; }
		return $balances;
	}
	public static function totals(): array {
		global $wpdb;
		$row = $wpdb->get_row(
			'SELECT COALESCE(SUM(balance_cents),0) AS total_cents, COUNT(*) AS wallets, COALESCE(SUM(CASE WHEN balance_cents>0 THEN 1 ELSE 0 END),0) AS positive_wallets FROM ' . self::wallets_table(),
			ARRAY_A
		);
		return is_array( $row ) ? $row : array( 'total_cents' => 0, 'wallets' => 0, 'positive_wallets' => 0 );
	}
}
