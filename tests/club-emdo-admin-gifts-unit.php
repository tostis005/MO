<?php
/** Read-only gift card administration report regression checks. */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );

class Fake_Club_Gift_Reports_DB {
	public string $prefix = 'wp_';
	public array $queries = array();
	public array $args = array();
	public function esc_like( string $value ): string {
		return addcslashes( $value, '_%\\' );
	}
	public function prepare( string $sql, ...$args ): string {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$this->args[] = $args;
		$i = 0;
		return (string) preg_replace_callback( '/%[ds]/', static function( array $matches ) use ( $args, &$i ): string {
			$value = $args[ $i++ ] ?? null;
			return '%d' === $matches[0] ? (string) (int) $value : "'" . addslashes( (string) $value ) . "'";
		}, $sql );
	}
	public function get_row( string $sql, $mode ): array {
		$this->queries[] = $sql;
		return array(
			'issued_count' => '4',
			'issued_cents' => '14500',
			'unredeemed_count' => '2',
			'unredeemed_cents' => '7500',
			'redeemed_count' => '2',
		);
	}
	public function get_var( string $sql ): int {
		$this->queries[] = $sql;
		return 2;
	}
	public function get_results( string $sql, $mode ): array {
		$this->queries[] = $sql;
		return array(
			array(
				'id' => '14', 'amount_cents' => '5000', 'recipient_email' => 'amigo@example.com',
				'order_id' => '204', 'item_id' => '20', 'redeemed_user_id' => '0',
				'redeemed_at' => null, 'created_at' => '2026-11-06 12:00:00',
			),
		);
	}
}
$wpdb = new Fake_Club_Gift_Reports_DB();
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-ledger.php';
$assert = static function( bool $valid, string $message ): void {
	if ( ! $valid ) { throw new RuntimeException( $message ); }
};

$totals = MDO_Club_Ledger::gift_report_totals();
$assert( 4 === $totals['issued_count'], 'Issued gift card count' );
$assert( 14500 === $totals['issued_cents'], 'Issued amount in cents' );
$assert( 2 === $totals['unredeemed_count'], 'Unredeemed card count' );
$assert( 7500 === $totals['unredeemed_cents'], 'Outstanding unused gift card amount' );
$assert( 2 === $totals['redeemed_count'], 'Redeemed card count' );
$assert( str_contains( $wpdb->queries[0], 'FROM wp_mdo_club_gifts' ), 'Aggregate query uses owned gift table' );
$report = MDO_Club_Ledger::gift_report( 2, 25, 'amigo@example.com', 'unredeemed' );
$assert( 2 === $report['total'], 'Paged search reports count' );
$assert( 1 === count( $report['rows'] ), 'Paged search returns records' );
$assert( 'amigo@example.com' === $report['rows'][0]['recipient_email'], 'Recipient visible to authorized admin' );
$assert( ! array_key_exists( 'code_hash', $report['rows'][0] ), 'No gift code hash in displayed row' );
$queries = implode( "\n", $wpdb->queries );
$assert( str_contains( $queries, 'redeemed_user_id=0' ), 'Unredeemed status filter' );
$assert( str_contains( $queries, "recipient_email LIKE '%amigo@example.com%'" ), 'Email search filters results' );
$assert( str_contains( $queries, 'LIMIT 25 OFFSET 25' ), 'Correct offset and bounded result page' );
$assert( str_contains( $queries, 'ORDER BY id DESC' ), 'Newest purchases first' );
$assert( ! str_contains( $queries, 'SELECT code_hash' ), 'Do not expose code hashes' );
$assert( ! str_contains( $queries, 'SELECT *' ), 'Never select all sensitive gift columns' );

$wpdb->queries = array();
$report = MDO_Club_Ledger::gift_report( 1, 100, '#204', 'redeemed' );
$queries = implode( "\n", $wpdb->queries );
$assert( str_contains( $queries, 'redeemed_user_id>0' ), 'Redeemed status filter' );
$assert( str_contains( $queries, 'order_id=204' ), 'Search by WooCommerce order ID' );
$assert( str_contains( $queries, 'LIMIT 50 OFFSET 0' ), 'Limit capped at 50 cards' );
$wpdb->queries = array();
MDO_Club_Ledger::gift_report( 1, 25, "x' OR 1=1 --", '' );
$queries = implode( "\n", $wpdb->queries );
$assert( str_contains( $queries, "recipient_email LIKE '") && ! str_contains( $queries, "WHERE 1=1" ), 'Search inputs stay inside SQL value binding' );

$ui = (string) file_get_contents( dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-admin.php' );
foreach ( array(
	"self::tabs( 'wallets' )",
	"self::tabs( 'gifts' )",
	'Saldos de usuarios',
	'Tarjetas regalo adquiridas',
	'Canjeada por',
	'Correo enviado',
	'Filtrar',
	'Sin canjear',
	'manage_woocommerce',
	'wc_get_order(',
	'get_edit_order_url()',
) as $needle ) {
	$assert( str_contains( $ui, $needle ), 'Missing admin report element: ' . $needle );
}
$assert( str_contains( $ui, 'MDO_Club_Ledger::gift_report(' ), 'Admin uses read-only ledger report' );
$assert( ! str_contains( $ui, 'MDO_Club_Ledger::gift_code(' ), 'Admin never reveals card codes' );
echo "Club EMDO gift-card admin reporting: OK\n";
