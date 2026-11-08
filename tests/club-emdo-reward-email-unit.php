<?php
/** Email regression: only an ACTUALLY credited registered customer sees a reward. */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ );
define( 'MDO_CLUB_RELEASE_APPROVED', true );
$options = array( 'mdo_club_rewards_enabled' => 'yes' );
function get_option( $key, $default = false ) { global $options; return $options[ $key ] ?? $default; }
function get_user_by( $key, $user_id ) { return (int) $user_id === 42 ? (object) array( 'ID' => 42 ) : false; }
function wc_get_account_endpoint_url( string $endpoint ): string { return 'https://www.elmercadodeorigen.com/mi-cuenta/' . $endpoint . '/'; }
function wc_price( $amount, array $args = array() ): string {
	return '<span class="woocommerce-Price-amount"><bdi>' . number_format( (float) $amount, 2, ',', '.' ) . '&nbsp;€</bdi></span>';
}
function wp_strip_all_tags( $s ): string { return strip_tags( (string) $s ); }
function esc_html( $s ): string { return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_url( $s ): string { return (string) $s; }
function add_action( $hook, $callback, $priority = 10, $argc = 1 ): void {
	$GLOBALS['hooks'][ $hook ][] = array( $callback, $priority, $argc );
}
function add_filter() {}
function add_shortcode() {}
class WC_Email { public function __construct( public string $id ) {} }
class WC_Order {
	public function __construct( public int $customer = 42, public bool $paid = true, public int $reward = 85, public bool $prepaid = false ) {}
	public function get_customer_id(): int { return $this->customer; }
	public function is_paid(): bool { return $this->paid; }
	public function get_meta( string $name ): int {
		return $name === '_mdo_club_award_cents' ? $this->reward : 0;
	}
	public function get_items(): array { return $this->prepaid ? array( new TestPrepaidItem() ) : array(); }
	public function get_currency(): string { return 'EUR'; }
}
class TestPrepaidItem {
	public function get_meta( $key ): string { return $key === '_mdo_club_kind' ? 'gift' : ''; }
}
class MDO_Club_Ledger {
	public static function money( int $cents ): string { return number_format( $cents / 100, 2, '.', '' ); }
}
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-club.php';
function ck( bool $condition, string $msg ): void { if ( ! $condition ) { throw new RuntimeException( $msg ); } }
MDO_Club::init();
$hook = array_filter( $GLOBALS['hooks']['woocommerce_email_before_order_table'] ?? [], fn( $h ) => $h[0] === array( 'MDO_Club', 'customer_reward_email' ) );
ck( 1 === count( $hook ), 'Reward block registered in native WooCommerce email hook' );
ck( 4 === reset( $hook )[2], 'All WooCommerce email hook args retained' );
function render_reward( $order, bool $admin = false, bool $plain = false, string $email_id = 'customer_processing_order' ): string {
	ob_start();
	MDO_Club::customer_reward_email( $order, $admin, $plain, new WC_Email( $email_id ) );
	return (string) ob_get_clean();
}
$example = new WC_Order();
$html = render_reward( $example );
ck( str_contains( $html, '¡Tu compra tiene recompensa!' ), 'Visible branded reward heading' );
ck( str_contains( $html, '0,85' ) && str_contains( $html, '€' ), 'Actual credited euro amount shown' );
ck( str_contains( $html, '/mi-cuenta/monedero/' ), 'Direct wallet CTA' );
ck( str_contains( $html, 'role="presentation"' ) && str_contains( $html, 'background-color:#f4f8f1' ), 'Inline email-client-friendly Club styling' );
ck( ! str_contains( $html, 'puntos' ), 'Never misrepresent currency as loyalty points' );
ck( $html === render_reward( $example, false, false, 'customer_completed_order' ), 'Completed order email also shows already credited reward' );
foreach ( array(
	array( new WC_Order( 0 ), false, false, 'customer_processing_order', 'Guest customer' ),
	array( new WC_Order( 99 ), false, false, 'customer_processing_order', 'Deleted/unregistered customer' ),
	array( new WC_Order( 42, false ), false, false, 'customer_processing_order', 'Unpaid order' ),
	array( new WC_Order( 42, true, 0 ), false, false, 'customer_processing_order', 'No credited reward' ),
	array( new WC_Order( 42, true, 85, true ), false, false, 'customer_processing_order', 'Gift card excluded' ),
	array( new WC_Order( 42, true, 85 ), true, false, 'customer_processing_order', 'Admin/supplier email' ),
	array( new WC_Order( 42, true, 85 ), false, false, 'customer_on_hold_order', 'Pending/offline email' ),
	array( new WC_Order( 42, true, 85 ), false, false, 'new_order', 'Internal WooCommerce email' ),
	array( new WC_Order( 42, true, 85 ), false, false, 'customer_invoice', 'Invoice reminder' ),
) as $case ) {
	ck( '' === render_reward( $case[0], $case[1], $case[2], $case[3] ), 'Do not include credit on ' . $case[4] );
}
$plain = render_reward( $example, false, true );
ck( str_contains( $plain, '0,85' ) && str_contains( $plain, 'monedero' ), 'Plain email includes credit' );
ck( ! str_contains( $plain, '<table' ), 'Plain text contains no HTML' );
ck( MDO_Club::reward_email_snippet( 0 ) === '', 'Zero credit cannot produce block' );
echo "Club EMDO confirmed reward customer emails: OK\n";
