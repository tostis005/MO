<?php
/**
 * Club EMDO fast regression tests using lightweight WordPress/Woo stubs.
 * These tests do not replace integrated staging tests against WCFM/gateway.
 */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
define( 'MDO_CLUB_RELEASE_APPROVED', true );
$options = array( 'mdo_club_checkout_enabled' => 'yes', 'mdo_club_reward_percent' => '1' );
$logged_in = true;
function get_option( string $name, $default = false ) {
	global $options;
	return $options[ $name ] ?? $default;
}
function add_option( string $name, $value, $deprecated = '', $autoload = 'yes' ): bool {
	global $options;
	if ( isset( $options[ $name ] ) ) { return false; }
	$options[ $name ] = $value;
	return true;
}
function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
	return str_repeat( 'Q', $length );
}
function wp_salt( string $scheme = 'auth' ): string { return 'fallback'; }
function wc_format_decimal( $number, int $dp = 2 ): string {
	return number_format( (float) str_replace( ',', '.', (string) $number ), $dp, '.', '' );
}
function wc_price( $amount, array $args = array() ): string {
	return number_format( (float) $amount, 2, '.', '' ) . ' €';
}
class WC_Email { public string $id = 'new_order'; }
class WC_Order {
	private array $meta;
	public function __construct( array $meta ) { $this->meta = $meta; }
	public function get_meta( string $key ) { return $this->meta[ $key ] ?? ''; }
	public function get_currency(): string { return 'EUR'; }
	public function get_total(): string { return '8.00'; }
}
function get_current_user_id(): int { return 42; }
function is_user_logged_in(): bool { global $logged_in; return $logged_in; }
class Club_Test_DB {
	public string $prefix = 'wp_';
	public int $balance = 8000;
	public function prepare( string $sql, $param ): string { return str_replace( '%d', (string) $param, $sql ); }
	public function get_var( string $sql ): int { return $this->balance; }
}
$wpdb = new Club_Test_DB();
class Club_Test_Session {
	private array $store = array();
	public function get( string $key, $fallback = null ) { return $this->store[ $key ] ?? $fallback; }
	public function set( string $key, $value ): void { $this->store[ $key ] = $value; }
}
class Club_Test_Cart {
	public array $items = array();
	public function get_cart(): array { return $this->items; }
}
class Club_Test_Container {
	public Club_Test_Session $session;
	public function __construct() { $this->session = new Club_Test_Session(); }
}
$wc = new Club_Test_Container();
function WC() { global $wc; return $wc; }
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-ledger.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club-checkout.php';

function expect( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
expect( 2500 === MDO_Club_Ledger::cents( '25,00' ), 'Localized amount' );
expect( 2525 === MDO_Club_Ledger::cents( '25.25' ), 'Decimal amount' );
expect( 0 === MDO_Club_Ledger::cents( array( '999999' ) ), 'Invalid array' );
expect( 0 === MDO_Club_Ledger::cents( '-5' ), 'Negative amount' );
expect( '25.05' === MDO_Club_Ledger::money( 2505 ), 'Cents to euros' );
expect( 1 === (int) MDO_Club::percent(), 'Default reward percentage' );
$code = MDO_Club_Ledger::gift_code( 111, 222 );
expect( (bool) preg_match( '/^[0-9]{20}$/', $code ), 'Gift code is 20 digits with no prefix' );
expect( hash_equals( MDO_Club_Ledger::gift_hash( $code ), MDO_Club_Ledger::gift_hash( strtolower( $code ) ) ), 'Gift normalization' );
expect( $code === MDO_Club_Ledger::gift_code( 111, 222 ), 'Resend stable after key is persisted' );
expect( $code !== MDO_Club_Ledger::gift_code( 111, 223 ), 'Codes differ between gift items' );
expect( MDO_Club_Ledger::gift_hash( $code ) !== MDO_Club_Ledger::gift_hash( MDO_Club_Ledger::gift_code( 111, 223 ) ), 'Different gift codes yield different hashes' );

$cart = new Club_Test_Cart();
$wc->session->set( 'mdo_club_requested_cents', 8000 );
$wc->session->set( 'mdo_club_use_enabled', 'yes' );
expect( 8.0 === MDO_Club_Checkout::total_after_wallet( 88.0, $cart ), 'Partial wallet: 88 - 80 = 8' );
expect( 8000 === $wc->session->get( 'mdo_club_applied_cents' ), 'Booked amount matches' );
expect( 0.0 === MDO_Club_Checkout::total_after_wallet( 55.0, $cart ), 'Wallet cannot overdraw order total' );
$wc->session->set( 'mdo_club_use_enabled', 'no' );
expect( 88.0 === MDO_Club_Checkout::total_after_wallet( 88.0, $cart ), 'Unchecked wallet changes nothing' );
$wc->session->set( 'mdo_club_use_enabled', 'yes' );
$cart->items = array( array( 'mdo_club_kind' => 'gift' ) );
expect( 88.0 === MDO_Club_Checkout::total_after_wallet( 88.0, $cart ), 'No wallet applied on prepaid purchases' );
$cart->items = array();
$options['mdo_club_checkout_enabled'] = 'no';
expect( 88.0 === MDO_Club_Checkout::total_after_wallet( 88.0, $cart ), 'Feature flag closed prevents wallet spend' );

$order = new WC_Order( array(
	'_mdo_club_wallet_cents' => 8000,
	'_mdo_club_full_total_cents' => 8800,
) );
$rows = array(
	'cart_subtotal' => array( 'label' => 'Subtotal:', 'value' => '88.00 €' ),
	'order_total' => array( 'label' => 'Total:', 'value' => '8.00 €' ),
);
MDO_Club_Checkout::before_email( $order, true, false, new WC_Email() );
$producer = MDO_Club_Checkout::email_totals( $rows, $order, 'incl' );
expect( 'Total a facturar:' === $producer['order_total']['label'], 'Producer email invoice label' );
expect( '88.00 €' === $producer['order_total']['value'], 'Producer email amount must be complete' );
MDO_Club_Checkout::after_email( $order, true, false, new WC_Email() );
$customer = MDO_Club_Checkout::email_totals( $rows, $order, 'incl' );
expect( '88.00 €' === $customer['mdo_club_value']['value'], 'Customer sees full order value' );
expect( '−80.00 €' === $customer['mdo_club_wallet']['value'], 'Customer sees applied balance' );
expect( '8.00 €' === $customer['order_total']['value'], 'Customer sees remaining bank charge' );
echo "Club EMDO unit checks: OK\n";