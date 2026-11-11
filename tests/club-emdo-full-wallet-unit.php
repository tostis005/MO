<?php
/** Unit regression for the single Club EMDO wallet, rewards and manual refunds. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
define( 'MDO_CLUB_RELEASE_APPROVED', true );
define( 'ARRAY_A', 'ARRAY_A' );

$options = array(
	'mdo_club_rewards_enabled' => 'yes', 'mdo_club_reward_percent' => '1',
	'mdo_club_product_wallet' => 901, 'mdo_club_product_gift' => 902,
);
function get_option( string $key, $default = false ) { global $options; return $options[ $key ] ?? $default; }
function current_time( $format = 'mysql', $gmt = false ): string { return '2026-11-05 12:00:00'; }
function wc_format_decimal( $number, int $places = 2 ): string {
	return number_format( (float) str_replace( ',', '.', (string) $number ), $places, '.', '' );
}
function get_user_by( string $field, int $value ) {
	return $value > 0 ? (object) array( 'ID' => $value ) : false;
}
function wc_get_order( int $id ) { global $orders; return $orders[ $id ] ?? false; }
class WC_Order {
	public array $meta = array();
	public array $notes = array();
	public function __construct( private int $user, private bool $paid, private array $items, private bool $has_date = true ) {}
	public function is_paid(): bool { return $this->paid; }
	public function get_date_paid() { return $this->has_date ? '2026-11-05' : false; }
	public function get_customer_id(): int { return $this->user; }
	public function get_items( string $kind = '' ): array { return $this->items; }
	public function get_meta( string $name ) { return $this->meta[ $name ] ?? ''; }
	public function update_meta_data( string $name, $value ): void { $this->meta[ $name ] = $value; }
	public function add_order_note( string $note ): void { $this->notes[] = $note; }
	public function save(): void {}
}
class Test_Club_Item {
	public function __construct( private int $product, private float $net, private float $tax ) {}
	public function get_product_id(): int { return $this->product; }
	public function get_total(): float { return $this->net; }
	public function get_total_tax(): float { return $this->tax; }
	public function get_meta( string $key ) { return ''; }
}
class Test_Club_DB {
	public string $prefix = 'wp_';
	public int $balance = 0;
	public array $events = array();
	private ?array $snapshot = null;
	public function prepare( string $sql, ...$args ): string {
		$i = 0;
		return (string) preg_replace_callback( '/%[ds]/', static function( array $match ) use ( $args, &$i ) {
			$v = $args[ $i++ ] ?? '';
			return '%d' === $match[0] ? (string) (int) $v : "'" . addslashes( (string) $v ) . "'";
		}, $sql );
	}
	public function get_var( string $sql ): int { return $this->balance; }
	public function query( string $sql ) {
		if ( $sql === 'START TRANSACTION' ) {
			$this->snapshot = array( $this->balance, $this->events );
			return 1;
		}
		if ( $sql === 'ROLLBACK' ) {
			if ( $this->snapshot ) { list( $this->balance, $this->events ) = $this->snapshot; }
			$this->snapshot = null;
			return 1;
		}
		if ( $sql === 'COMMIT' ) { $this->snapshot = null; return 1; }
		if ( str_contains( $sql, 'INSERT IGNORE INTO wp_mdo_club_ledger' ) ) {
			if ( ! preg_match( "/VALUES \(\d+,(-?\d+),'[^']*','([^']*)',/", $sql, $match ) ) {
				throw new RuntimeException( 'Unexpected ledger insert ' . $sql );
			}
			$key = $match[2];
			if ( isset( $this->events[ $key ] ) ) { return 0; }
			$this->events[ $key ] = (int) $match[1];
			return 1;
		}
		if ( str_contains( $sql, 'INSERT IGNORE INTO wp_mdo_club_wallets' ) ) { return 1; }
		if ( str_contains( $sql, 'UPDATE wp_mdo_club_wallets' ) ) {
			if ( ! preg_match( '/balance_cents=balance_cents\+\((-?\d+)\)/', $sql, $match ) ) {
				throw new RuntimeException( 'Unexpected wallet update ' . $sql );
			}
			$next = $this->balance + (int) $match[1];
			if ( $next < 0 ) { return 0; }
			$this->balance = $next;
			return 1;
		}
		throw new RuntimeException( 'Unexpected SQL ' . $sql );
	}
}
$wpdb = new Test_Club_DB();
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-ledger.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-admin.php';
function check( bool $ok, string $description ): void {
	if ( ! $ok ) { throw new RuntimeException( $description ); }
}
check( 1.0 === MDO_Club::percent(), 'Reward defaults to 1%' );
check( MDO_Club::enabled( 'rewards' ), 'Rewards may be enabled independently of topups' );
check( ! MDO_Club::enabled( 'prepaid' ), 'Prepaid is not silently activated' );
check( MDO_Club_Admin::parse_amount( '+5,00' ) === 500, 'Positive admin adjustment' );
check( MDO_Club_Admin::parse_amount( '-2.25' ) === -225, 'Negative admin adjustment' );
check( MDO_Club_Admin::parse_amount( '5e2' ) === 0, 'No scientific notation' );
check( MDO_Club_Admin::parse_amount( '5001' ) === 0, 'Admin amount limit' );

$orders = array(
	111 => new WC_Order( 42, true, array( new Test_Club_Item( 20, 80.00, 8.00 ) ) ),
	112 => new WC_Order( 0, true, array( new Test_Club_Item( 20, 100.00, 10.00 ) ) ),
	113 => new WC_Order( 42, false, array( new Test_Club_Item( 20, 100.00, 10.00 ) ), false ),
	114 => new WC_Order( 42, true, array( new Test_Club_Item( 901, 25.00, 0.00 ) ) ),
);
// The buyer was authenticated at checkout, before the payment callback.
$orders[111]->update_meta_data( '_mdo_club_checkout_user_id', 42 );
MDO_Club::award_reward( 111 );
check( MDO_Club_Ledger::balance( 42 ) === 88, 'Purchase of 88 EUR credits 0.88 EUR' );
MDO_Club::award_reward( 111 );
check( MDO_Club_Ledger::balance( 42 ) === 88, 'Same purchase never double credited' );
MDO_Club::award_reward( 112 );
MDO_Club::award_reward( 113 );
MDO_Club::award_reward( 114 );
check( MDO_Club_Ledger::balance( 42 ) === 88, 'Guest, unpaid, and wallet products do not earn rewards' );

check( MDO_Club_Ledger::change( 42, 1000, 'adjust:test:1', 'adjustment', 0, false, 9, 'Compensación manual' ), 'Manual positive adjustment works' );
check( MDO_Club_Ledger::balance( 42 ) === 1088, 'Single wallet includes purchase reward and admin credit' );
check( MDO_Club_Ledger::change( 42, -250, 'adjust:test:2', 'adjustment', 0, false, 9, 'Corrección devolución' ), 'Manual negative adjustment works' );
check( MDO_Club_Ledger::balance( 42 ) === 838, 'Manual debit reduces same wallet' );
check( ! MDO_Club_Ledger::change( 42, -100000, 'adjust:test:3', 'adjustment', 0, false, 9, 'Saldo insuficiente' ), 'No negative balance' );
check( MDO_Club_Ledger::balance( 42 ) === 838, 'Negative balance refusal does not alter funds' );
check( ! isset( $wpdb->events['adjust:test:3'] ), 'Failed adjustment is rolled back' );

$orders[111]->meta['_mdo_club_wallet_cents'] = 500;
MDO_Club::refund_notice( 111, 5 );
check( MDO_Club_Ledger::balance( 42 ) === 838, 'Order refund never changes wallet automatically' );
check( count( $orders[111]->notes ) === 1, 'Refund flagged for manual resolution' );
echo "Club EMDO full-wallet and manual adjustment tests: OK\n";