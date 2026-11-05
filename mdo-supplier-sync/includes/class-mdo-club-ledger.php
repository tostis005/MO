<?php
/**
 * Club: contabilidad de saldo en céntimos y tarjetas regalo.
 *
 * Las operaciones son atómicas e idempotentes mediante claves de evento únicas.
 * No se deben sustituir por cupones/descuentos: el saldo es un medio de pago.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Ledger {
	const VERSION = '1';
	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'mdo_club_' . $name;
	}
	public static function maybe_install(): void {
		if ( get_option( 'mdo_club_schema_version' ) === self::VERSION ) { return; }
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$wallets = self::table( 'wallets' );
		$ledger = self::table( 'ledger' );
		$gifts = self::table( 'gifts' );
		dbDelta( "CREATE TABLE {$wallets} (
			user_id bigint(20) unsigned NOT NULL,
			balance_cents bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (user_id)
		) ENGINE=InnoDB {$charset};" );
		dbDelta( "CREATE TABLE {$ledger} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			amount_cents bigint(20) NOT NULL,
			kind varchar(32) NOT NULL,
			event_key varchar(128) NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_key (event_key),
			KEY user_id (user_id)
		) ENGINE=InnoDB {$charset};" );
		dbDelta( "CREATE TABLE {$gifts} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code_hash char(64) NOT NULL,
			amount_cents bigint(20) NOT NULL,
			recipient_email varchar(190) NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			redeemed_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			redeemed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code_hash (code_hash),
			UNIQUE KEY order_item (order_id,item_id)
		) ENGINE=InnoDB {$charset};" );
		update_option( 'mdo_club_schema_version', self::VERSION, false );
	}
	public static function cents( $amount ): int {
		return max( 0, (int) round( (float) wc_format_decimal( $amount, 2 ) * 100 ) );
	}
	public static function money( int $cents ): string {
		return number_format( $cents / 100, 2, '.', '' );
	}
	public static function balance( int $user_id ): int {
		if ( $user_id <= 0 ) { return 0; }
		global $wpdb;
		return max( 0, (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT balance_cents FROM ' . self::table( 'wallets' ) . ' WHERE user_id=%d',
			$user_id
		) ) );
	}
	/**
	 * Cambia saldo de forma atómica. Devuelve false si no hay fondos o falla SQL.
	 * Con $inside_transaction=true la transacción es responsabilidad del llamante.
	 */
	public static function change( int $user_id, int $delta, string $event, string $kind, int $order_id = 0, bool $inside_transaction = false ): bool {
		if ( $user_id <= 0 || 0 === $delta || '' === $event ) { return false; }
		global $wpdb;
		if ( ! $inside_transaction ) { $wpdb->query( 'START TRANSACTION' ); }
		$insert = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table( 'ledger' ) . ' (user_id,amount_cents,kind,event_key,order_id,created_at) VALUES (%d,%d,%s,%s,%d,%s)',
			$user_id, $delta, $kind, $event, $order_id, current_time( 'mysql', true )
		) );
		if ( false === $insert ) {
			if ( ! $inside_transaction ) { $wpdb->query( 'ROLLBACK' ); }
			return false;
		}
		if ( 0 === $insert ) { // Ya aplicado: reintento seguro.
			if ( ! $inside_transaction ) { $wpdb->query( 'COMMIT' ); }
			return true;
		}
		$created = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table( 'wallets' ) . ' (user_id,balance_cents) VALUES (%d,0)', $user_id
		) );
		$updated = false;
		if ( false !== $created ) {
			$updated = $wpdb->query( $wpdb->prepare(
				'UPDATE ' . self::table( 'wallets' ) . ' SET balance_cents=balance_cents+(%d) WHERE user_id=%d AND balance_cents+(%d)>=0',
				$delta, $user_id, $delta
			) );
		}
		if ( 1 !== $updated ) {
			$wpdb->query( 'ROLLBACK' ); // Ante falla, abortar también cualquier transacción exterior.
			return false;
		}
		if ( ! $inside_transaction ) { $wpdb->query( 'COMMIT' ); }
		return true;
	}
	public static function movements( int $user_id, int $limit = 25 ): array {
		global $wpdb;
		if ( $user_id <= 0 ) { return array(); }
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT amount_cents,kind,order_id,created_at FROM ' . self::table( 'ledger' ) . ' WHERE user_id=%d ORDER BY id DESC LIMIT %d',
			$user_id, min( 50, max( 1, $limit ) )
		), ARRAY_A ) ?: array();
	}
	public static function gift_code( int $order_id, int $item_id ): string {
		// HMAC determinista: puede reenviarse sin almacenar el código en claro.
		$token = strtoupper( substr( hash_hmac( 'sha256', $order_id . ':' . $item_id, wp_salt( 'auth' ) ), 0, 24 ) );
		return 'CLUB-' . implode( '-', str_split( $token, 4 ) );
	}
	public static function gift_hash( string $code ): string {
		return hash( 'sha256', preg_replace( '/[^A-Z0-9]/', '', strtoupper( $code ) ) );
	}
	public static function issue_gift( int $order_id, int $item_id, int $cents, string $email ): bool {
		if ( $cents <= 0 || ! is_email( $email ) ) { return false; }
		global $wpdb;
		$code = self::gift_code( $order_id, $item_id );
		$inserted = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table( 'gifts' ) . ' (code_hash,amount_cents,recipient_email,order_id,item_id,created_at) VALUES (%s,%d,%s,%d,%d,%s)',
			self::gift_hash( $code ), $cents, $email, $order_id, $item_id, current_time( 'mysql', true )
		) );
		return false !== $inserted;
	}
	public static function gift( string $code ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table( 'gifts' ) . ' WHERE code_hash=%s',
			self::gift_hash( $code )
		), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}
	public static function redeem( int $user_id, string $code ): bool {
		if ( $user_id <= 0 || ! preg_match( '/^CLUB-[A-F0-9-]{24,32}$/i', $code ) ) { return false; }
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table( 'gifts' ) . ' WHERE code_hash=%s FOR UPDATE',
			self::gift_hash( $code )
		), ARRAY_A );
		if ( ! $row || (int) $row['redeemed_user_id'] > 0 || (int) $row['amount_cents'] <= 0 ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
		$ok = self::change(
			$user_id, (int) $row['amount_cents'], 'gift:' . $row['id'], 'gift', (int) $row['order_id'], true
		);
		if ( ! $ok ) { $wpdb->query( 'ROLLBACK' ); return false; }
		$updated = $wpdb->query( $wpdb->prepare(
			'UPDATE ' . self::table( 'gifts' ) . ' SET redeemed_user_id=%d,redeemed_at=%s WHERE id=%d AND redeemed_user_id=0',
			$user_id, current_time( 'mysql', true ), (int) $row['id']
		) );
		if ( 1 !== $updated ) { $wpdb->query( 'ROLLBACK' ); return false; }
		$wpdb->query( 'COMMIT' );
		return true;
	}
}