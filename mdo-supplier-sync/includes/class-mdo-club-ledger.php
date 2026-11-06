<?php
/**
 * Club: contabilidad de saldo en céntimos y tarjetas regalo.
 *
 * Las operaciones son atómicas e idempotentes mediante claves de evento únicas.
 * No se deben sustituir por cupones/descuentos: el saldo es un medio de pago.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Ledger {
	const VERSION = '2';
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
			admin_id bigint(20) unsigned NOT NULL DEFAULT 0,
			note varchar(255) NOT NULL DEFAULT '',
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
		if ( ! is_scalar( $amount ) || ! is_numeric( str_replace( ',', '.', (string) $amount ) ) ) {
			return 0;
		}
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
	public static function change( int $user_id, int $delta, string $event, string $kind, int $order_id = 0, bool $inside_transaction = false, int $admin_id = 0, string $note = '' ): bool {
		if ( $user_id <= 0 || 0 === $delta || '' === $event ) { return false; }
		global $wpdb;
		if ( ! $inside_transaction ) { $wpdb->query( 'START TRANSACTION' ); }
		$insert = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table( 'ledger' ) . ' (user_id,amount_cents,kind,event_key,order_id,admin_id,note,created_at) VALUES (%d,%d,%s,%s,%d,%d,%s,%s)',
			$user_id, $delta, $kind, $event, $order_id, $admin_id, substr( $note, 0, 255 ), current_time( 'mysql', true )
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
	/**
	 * Desglose contable del saldo disponible.
	 * Política: al usar el monedero se consume primero saldo promocional;
	 * solo después, saldo comprado (recargas y tarjetas regalo).
	 * La devolución de una compra revierte el gasto total, sin alterar el
	 * historial contable. Ambas cantidades siempre suman el saldo actual.
	 *
	 * @return array{prepaid:int,rewards:int,total:int}
	 */
	public static function breakdown( int $user_id ): array {
		$total = self::balance( $user_id );
		if ( $total <= 0 ) { return array( 'prepaid' => 0, 'rewards' => 0, 'total' => 0 ); }
		global $wpdb;
		$rows = $wpdb->get_row( $wpdb->prepare(
			'SELECT
				COALESCE(SUM(CASE WHEN kind=%s THEN amount_cents ELSE 0 END),0) AS reward_credits,
				COALESCE(SUM(CASE WHEN kind IN (%s,%s) THEN amount_cents ELSE 0 END),0) AS prepaid_credits,
				COALESCE(SUM(CASE WHEN kind IN (%s,%s) THEN amount_cents ELSE 0 END),0) AS spend_net
			FROM ' . self::table( 'ledger' ) . ' WHERE user_id=%d',
			'reward', 'wallet', 'gift', 'spend', 'release', $user_id
		), ARRAY_A );
		$rewards_issued = max( 0, (int) ( $rows['reward_credits'] ?? 0 ) );
		$net_spent = max( 0, -(int) ( $rows['spend_net'] ?? 0 ) );
		$rewards = min( $total, max( 0, $rewards_issued - $net_spent ) );
		return array(
			'prepaid' => $total - $rewards,
			'rewards' => $rewards,
			'total' => $total,
		);
	}
	/** @param int[] $user_ids @return array<int,int> */
	public static function balances_for_users( array $user_ids ): array {
		$user_ids = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
		if ( ! $user_ids ) { return array(); }
		global $wpdb;
		$holders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT user_id,balance_cents FROM ' . self::table( 'wallets' ) . ' WHERE user_id IN (' . $holders . ')',
			$user_ids
		), ARRAY_A ) ?: array();
		$balances = array_fill_keys( $user_ids, 0 );
		foreach ( $rows as $row ) {
			$balances[ (int) $row['user_id'] ] = (int) $row['balance_cents'];
		}
		return $balances;
	}
	public static function totals(): array {
		global $wpdb;
		$row = $wpdb->get_row(
			'SELECT COALESCE(SUM(balance_cents),0) AS balance_cents, COALESCE(SUM(CASE WHEN balance_cents>0 THEN 1 ELSE 0 END),0) AS positive_wallets FROM ' . self::table( 'wallets' ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : array( 'balance_cents' => 0, 'positive_wallets' => 0 );
	}
	public static function movements( int $user_id, int $limit = 25 ): array {
		global $wpdb;
		if ( $user_id <= 0 ) { return array(); }
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT amount_cents,kind,order_id,admin_id,note,created_at FROM ' . self::table( 'ledger' ) . ' WHERE user_id=%d ORDER BY id DESC LIMIT %d',
			$user_id, min( 50, max( 1, $limit ) )
		), ARRAY_A ) ?: array();
	}
	/**
	 * Read-only totals for gift cards already issued after an eligible paid order.
	 * Unpaid / unissued gift orders belong to WooCommerce, not this ledger.
	 * No card secrets or plaintext redemption codes are retrieved.
	 */
	public static function gift_report_totals(): array {
		global $wpdb;
		$row = $wpdb->get_row(
			'SELECT COUNT(*) AS issued_count,
				COALESCE(SUM(amount_cents),0) AS issued_cents,
				COALESCE(SUM(CASE WHEN redeemed_user_id=0 THEN 1 ELSE 0 END),0) AS unredeemed_count,
				COALESCE(SUM(CASE WHEN redeemed_user_id=0 THEN amount_cents ELSE 0 END),0) AS unredeemed_cents,
				COALESCE(SUM(CASE WHEN redeemed_user_id>0 THEN 1 ELSE 0 END),0) AS redeemed_count
			 FROM ' . self::table( 'gifts' ),
			ARRAY_A
		);
		return array(
			'issued_count' => (int) ( $row['issued_count'] ?? 0 ),
			'issued_cents' => (int) ( $row['issued_cents'] ?? 0 ),
			'unredeemed_count' => (int) ( $row['unredeemed_count'] ?? 0 ),
			'unredeemed_cents' => (int) ( $row['unredeemed_cents'] ?? 0 ),
			'redeemed_count' => (int) ( $row['redeemed_count'] ?? 0 ),
		);
	}
	/**
	 * Paginated read-only gift card report. Only operational metadata is
	 * selected: never expose code_hash, signing secrets or gift codes.
	 *
	 * @return array{rows:array,total:int}
	 */
	public static function gift_report( int $page = 1, int $per_page = 25, string $search = '', string $status = '' ): array {
		global $wpdb;
		$page = max( 1, $page );
		$per_page = min( 50, max( 1, $per_page ) );
		$search = trim( $search );
		$wheres = array();
		$values = array();

		if ( 'unredeemed' === $status ) {
			$wheres[] = 'redeemed_user_id=0';
		} elseif ( 'redeemed' === $status ) {
			$wheres[] = 'redeemed_user_id>0';
		}
		if ( '' !== $search ) {
			$pattern = '%' . $wpdb->esc_like( $search ) . '%';
			// Search recipient email, or exact WooCommerce order ID when numeric.
			if ( preg_match( '/^#?([0-9]{1,12})$/', $search, $matches ) ) {
				$wheres[] = '(recipient_email LIKE %s OR order_id=%d)';
				$values[] = $pattern;
				$values[] = (int) $matches[1];
			} else {
				$wheres[] = 'recipient_email LIKE %s';
				$values[] = $pattern;
			}
		}
		$from = ' FROM ' . self::table( 'gifts' );
		$where = $wheres ? ' WHERE ' . implode( ' AND ', $wheres ) : '';
		$count_sql = 'SELECT COUNT(*)' . $from . $where;
		if ( $values ) {
			$count_sql = $wpdb->prepare( $count_sql, $values );
		}
		$total = (int) $wpdb->get_var( $count_sql );
		$query = 'SELECT id,amount_cents,recipient_email,order_id,item_id,redeemed_user_id,redeemed_at,created_at'
			. $from . $where . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$query_values = array_merge( $values, array( $per_page, ( $page - 1 ) * $per_page ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $query_values ), ARRAY_A );
		return array( 'rows' => is_array( $rows ) ? $rows : array(), 'total' => $total );
	}
	private static function gift_secret(): string {
		// Clave dedicada y persistente: cambiar las sales de WordPress no invalida
		// tarjetas ya emitidas. No compartir este secreto ni exportarlo.
		$secret = (string) get_option( 'mdo_club_gift_secret', '' );
		if ( '' === $secret ) {
			add_option( 'mdo_club_gift_secret', wp_generate_password( 64, true, true ), '', 'no' );
			$secret = (string) get_option( 'mdo_club_gift_secret', '' );
		}
		return '' !== $secret ? $secret : wp_salt( 'auth' );
	}
	/**
	 * New cards use only 20 digits (~66 bits of possible codes).
	 * Derived from a dedicated persistent HMAC key: no sequential/guessable ID,
	 * stable across mail retries, with no cleartext gift code stored in the DB.
	 */
	public static function gift_code( int $order_id, int $item_id ): string {
		$raw = hash_hmac( 'sha256', $order_id . ':' . $item_id, self::gift_secret(), true );
		$digits = '';
		for ( $i = 0; $i < 20; $i++ ) {
			$digits .= (string) ( ord( $raw[ $i ] ) % 10 );
		}
		return $digits;
	}
	/**
	 * Original issued cards retain their old code. Their hashes are already
	 * stored in the DB, so changing the visible code unconditionally would
	 * invalidate cards sent before this upgrade.
	 */
	private static function legacy_gift_code( int $order_id, int $item_id ): string {
		$token = strtoupper( substr( hash_hmac( 'sha256', $order_id . ':' . $item_id, self::gift_secret() ), 0, 24 ) );
		return 'CLUB-' . implode( '-', str_split( $token, 4 ) );
	}
	public static function issued_gift_code( int $order_id, int $item_id ): string {
		global $wpdb;
		$stored_hash = $wpdb->get_var( $wpdb->prepare(
			'SELECT code_hash FROM ' . self::table( 'gifts' ) . ' WHERE order_id=%d AND item_id=%d',
			$order_id, $item_id
		) );
		if ( is_string( $stored_hash ) && hash_equals( $stored_hash, self::gift_hash( self::legacy_gift_code( $order_id, $item_id ) ) ) ) {
			return self::legacy_gift_code( $order_id, $item_id );
		}
		return self::gift_code( $order_id, $item_id );
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
		$code = trim( $code );
		// Codes issued since November 2026 contain only digits. Old cards
		// with the CLUB- prefix remain redeemable and cannot be invalidated.
		if ( $user_id <= 0 || ! preg_match( '/^(?:[0-9]{20}|CLUB-(?:[A-F0-9]{4}-){5}[A-F0-9]{4})$/i', $code ) ) { return false; }
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