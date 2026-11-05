<?php
/**
 * Club EMDO phase-1 regression test: paid registered orders, idempotence,
 * excluded shipping, manual adjustments and insufficient-funds handling.
 */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
$options = array();
function get_option( string $name, $default = false ) {
	global $options;
	return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
}
function update_option( string $name, $value, $autoload = false ): bool {
	global $options;
	$options[ $name ] = $value;
	return true;
}
function current_time( $kind = 'mysql', $gmt = false ): string { return '2026-11-05 10:00:00'; }
function get_user_by( string $field, int $id ) { return $id > 0 ? (object) array( 'ID' => $id ) : false; }
function wc_get_order( int $id ) { global $orders; return $orders[ $id ] ?? false; }

class WC_Order {
	private int $user;
	private bool $paid;
	private bool $paid_at;
	private array $products;
	public function __construct( int $user, bool $paid, bool $paid_at, array $products ) {
		$this->user = $user; $this->paid = $paid; $this->paid_at = $paid_at; $this->products = $products;
	}
	public function is_paid(): bool { return $this->paid; }
	public function get_date_paid() { return $this->paid_at ? '2026-11-05' : false; }
	public function get_customer_id(): int { return $this->user; }
	public function get_items( string $kind = '' ): array { return $this->products; }
}
class Test_Line {
	public function __construct( private float $net, private float $tax ) {}
	public function get_total(): float { return $this->net; }
	public function get_total_tax(): float { return $this->tax; }
}
class Fake_Club_DB {
	public string $prefix = 'wp_';
	public int $balance = 0;
	public array $events = array();
	private ?array $snapshot = null;
	public function prepare( string $sql, ...$args ): string {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) { $args = $args[0]; }
		$i = 0;
		return preg_replace_callback( '/%[ds]/', static function( $match ) use ( $args, &$i ) {
			$arg = $args[ $i++ ];
			return '%d' === $match[0] ? (string) (int) $arg : "'" . addslashes( (string) $arg ) . "'";
		}, $sql );
	}
	public function get_var( string $sql ): int { return $this->balance; }
	public function query( string $sql ) {
		if ( 'START TRANSACTION' === $sql ) {
			$this->snapshot = array( $this->balance, $this->events );
			return 1;
		}
		if ( 'ROLLBACK' === $sql ) {
			if ( $this->snapshot ) { list( $this->balance, $this->events ) = $this->snapshot; }
			$this->snapshot = null;
			return 1;
		}
		if ( 'COMMIT' === $sql ) { $this->snapshot = null; return 1; }
		if ( str_contains( $sql, 'INSERT IGNORE INTO wp_mdo_club_rewards_entries' ) ) {
			$pattern = "/VALUES \(\d+,(-?\d+),'[^']*','([^']*)',/";
			if ( ! preg_match( $pattern, $sql, $match ) ) { throw new RuntimeException( 'Unexpected ledger SQL ' . $sql ); }
			$key = $match[2];
			if ( isset( $this->events[ $key ] ) ) { return 0; }
			$this->events[ $key ] = (int) $match[1];
			return 1;
		}
		if ( str_contains( $sql, 'INSERT IGNORE INTO wp_mdo_club_rewards_wallets' ) ) { return 1; }
		if ( str_contains( $sql, 'UPDATE wp_mdo_club_rewards_wallets' ) ) {
			if ( ! preg_match( '/balance_cents=\w+_cents\+\((-?\d+)\)/', $sql, $m ) ) {
				throw new RuntimeException( 'Unexpected balance SQL ' . $sql );
			}
			$next = $this->balance + (int) $m[1];
			if ( $next < 0 ) { return 0; }
			$this->balance = $next;
			return 1;
		}
		throw new RuntimeException( 'Unknown query: ' . $sql );
	}
}
$wpdb = new Fake_Club_DB();
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-rewards-ledger.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-rewards.php';
function expect_true( bool $cond, string $message ): void {
	if ( ! $cond ) { throw new RuntimeException( $message ); }
}
function expect_eq( $actual, $expected, string $message ): void {
	if ( $actual !== $expected ) { throw new RuntimeException( $message . ': ' . var_export( $actual, true ) . ' != ' . var_export( $expected, true ) ); }
}

expect_eq( MDO_Club_Rewards::rate(), 1.0, 'Reward rate default 1%' );
expect_true( MDO_Club_Rewards::enabled(), 'Rewards enabled by default for phase one' );
expect_eq( MDO_Club_Rewards_Ledger::parse_adjustment( '+5,00' ), 500, 'Positive adjustment' );
expect_eq( MDO_Club_Rewards_Ledger::parse_adjustment( '-2.50' ), -250, 'Negative adjustment' );
expect_eq( MDO_Club_Rewards_Ledger::parse_adjustment( '5001' ), 0, 'Oversized adjustment rejected' );
expect_eq( MDO_Club_Rewards_Ledger::parse_adjustment( '2e3' ), 0, 'Exponent notation rejected' );
expect_eq( MDO_Club_Rewards_Ledger::format_amount( 8800 ), '88.00', 'Currency conversion' );

$orders = array(
	1001 => new WC_Order( 42, true, true, array( new Test_Line( 80.00, 8.00 ) ) ),
	1002 => new WC_Order( 0, true, true, array( new Test_Line( 100.00, 10.00 ) ) ),
	1003 => new WC_Order( 42, false, false, array( new Test_Line( 100.00, 10.00 ) ) ),
	1004 => new WC_Order( 42, true, false, array( new Test_Line( 100.00, 10.00 ) ) ),
);
expect_eq( MDO_Club_Rewards::eligible_cents( $orders[1001] ), 8800, 'Products including VAT; shipping excluded by source' );
MDO_Club_Rewards::credit_purchase( 1001 );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 88, 'Paid registered order: 1% of 88 is 88 cents' );
MDO_Club_Rewards::credit_purchase( 1001 );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 88, 'Repeated payment hooks are idempotent' );
MDO_Club_Rewards::credit_purchase( 1002 );
MDO_Club_Rewards::credit_purchase( 1003 );
MDO_Club_Rewards::credit_purchase( 1004 );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 88, 'Guest, unpaid and not-confirmed orders rejected' );

expect_true( MDO_Club_Rewards_Ledger::change( 42, 500, 'adjust:test1', 'adjustment', 0, 5, 'Cortesía comercial' ), 'Manual credit succeeds' );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 588, 'Manual credit increases balance' );
expect_true( MDO_Club_Rewards_Ledger::change( 42, -200, 'adjust:test2', 'adjustment', 0, 5, 'Corrección devolución' ), 'Manual debit succeeds' );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 388, 'Manual debit decreases balance' );
expect_true( ! MDO_Club_Rewards_Ledger::change( 42, -100000, 'adjust:too-much', 'adjustment', 0, 5, 'No puede ser negativo' ), 'Cannot overdraft rewards' );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 388, 'Rejected debit does not affect balance' );
expect_true( ! isset( $wpdb->events['adjust:too-much'] ), 'Rejected debit rolled back event' );

$options['mdo_club_rewards_percent'] = '2';
$orders[1005] = new WC_Order( 42, true, true, array( new Test_Line( 50.00, 0.00 ) ) );
MDO_Club_Rewards::credit_purchase( 1005 );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 488, 'Admin 2% setting applies to subsequent purchases' );
$options['mdo_club_rewards_enabled'] = 'no';
$orders[1006] = new WC_Order( 42, true, true, array( new Test_Line( 99.00, 1.00 ) ) );
MDO_Club_Rewards::credit_purchase( 1006 );
expect_eq( MDO_Club_Rewards_Ledger::balance( 42 ), 488, 'Admin can pause future rewards' );
echo "Club EMDO phase 1 tests: OK\n";
