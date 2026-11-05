<?php
/** Club EMDO: offline transfer/Bizum and online prepaid-order regression tests. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('MDO_CLUB_RELEASE_APPROVED', true);
$options = ['mdo_club_prepaid_enabled' => 'yes'];
$orders = [];
$sent = [];
function get_option($name, $default=false) { global $options; return $options[$name] ?? $default; }
function wc_get_order(int $id) { global $orders; return $orders[$id] ?? null; }
function sanitize_email($s): string { return strtolower(trim((string)$s)); }
function is_email($s): bool { return (bool)filter_var($s, FILTER_VALIDATE_EMAIL); }
function home_url($s=''): string { return 'https://example.test'.$s; }
function wc_get_page_permalink($p): string { return 'https://example.test/mi-cuenta/'; }
function wc_price($number): string { return number_format((float)$number, 2, '.', '').' €'; }
function wp_strip_all_tags($s): string { return strip_tags((string)$s); }
function esc_html($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_url($s): string { return (string)$s; }
function wp_mail($email, $subject, $body, $headers): bool {
	global $sent;
	$sent[] = compact('email','subject','body','headers');
	return true;
}
class MDO_Club_Ledger {
	public static array $events = [];
	public static array $gifts = [];
	public static function cents($number): int { return (int)round((float)$number*100); }
	public static function money(int $cents): string { return number_format($cents/100, 2, '.', ''); }
	public static function change(int $user, int $delta, string $event, string $type, int $order=0, bool $transaction=false): bool {
		if (isset(self::$events[$event])) return true;
		self::$events[$event] = [$user,$delta,$type,$order];
		return true;
	}
	public static function issue_gift(int $order, int $item, int $amount, string $email): bool {
		self::$gifts[$order.':'.$item] = [$amount,$email];
		return true;
	}
	public static function gift_code(int $order, int $item): string { return 'CLUB-TEST-'.$order.'-'.$item; }
}
class WC_Order {
	public array $notes = [];
	public function __construct(
		public int $id,
		public bool $paid,
		public array $items,
		public string $total,
		public int $customer=42
	) {}
	public function is_paid(): bool { return $this->paid; }
	public function get_items($type='') { return $type === 'coupon' ? [] : $this->items; }
	public function get_total(): string { return $this->total; }
	public function get_customer_id(): int { return $this->customer; }
	public function add_order_note(string $s): void { $this->notes[] = $s; }
}
class WC_Order_Item_Product {
	public array $meta;
	public function __construct(public int $id, string $kind, int $cents, string $recipient='') {
		$this->meta = [
			'_mdo_club_kind'=>$kind,
			'_mdo_club_amount_cents'=>$cents,
			'_mdo_club_recipient'=>$recipient,
			'_mdo_club_message'=>'Feliz cumpleaños',
		];
	}
	public function get_meta(string $k) { return $this->meta[$k] ?? ''; }
	public function get_quantity(): int { return 1; }
	public function get_total(): float { return (float)MDO_Club_Ledger::money($this->meta['_mdo_club_amount_cents']); }
	public function get_total_tax(): float { return 0; }
	public function update_meta_data(string $k, $v): void { $this->meta[$k]=$v; }
	public function save(): void {}
}
class Test_Cart {
	public array $items = [];
	public function get_cart(): array { return $this->items; }
}
class Test_WC {
	public Test_Cart $cart;
	public function __construct() { $this->cart=new Test_Cart(); }
}
$wc = new Test_WC();
function WC() { global $wc; return $wc; }
require_once dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-club.php';
function expect_true($condition,$description): void {
	if (!$condition) throw new RuntimeException($description);
}
$wc->cart->items = [['mdo_club_kind'=>'gift']];
$available = MDO_Club::prepaid_gateways([
	'card'=>'Card gateway', 'bizum'=>'Bizum gateway',
	'bacs'=>'Bank transfer gateway', 'cod'=>'Cash on delivery',
	'cheque'=>'Manual offline cheque',
]);
expect_true(isset($available['bizum'],$available['bacs'],$available['card'],$available['cheque']), 'Woo gateways remain available for prepaid orders');
expect_true(!isset($available['cod']), 'Cash on delivery is not suitable for virtual prepaid credit');

$wallet_item = new WC_Order_Item_Product(30,'wallet',2500);
$orders[501] = new WC_Order(501,false,[30=>$wallet_item],'25.00');
MDO_Club::issue_prepaid(501);
expect_true(!MDO_Club_Ledger::$events, 'On-hold transfer must not credit wallet');
$orders[501]->paid = true; // Administrator changes order to completed after confirming payment.
MDO_Club::issue_prepaid(501);
MDO_Club::issue_prepaid(501);
expect_true(count(MDO_Club_Ledger::$events)===1, 'Completed offline transfer credits wallet only once');
expect_true(MDO_Club_Ledger::$events['topup:501:30'][1]===2500, 'Whole value must be credited');

$gift_item = new WC_Order_Item_Product(31,'gift',5000,'friend@example.test');
$orders[502] = new WC_Order(502,false,[31=>$gift_item],'50.00');
MDO_Club::issue_prepaid(502);
expect_true(!$sent, 'Pending gift order does not send a card');
$orders[502]->paid = true;
MDO_Club::issue_prepaid(502);
MDO_Club::issue_prepaid(502);
expect_true(count(MDO_Club_Ledger::$gifts)===1, 'Completed gift order creates unique card');
expect_true(count($sent)===1, 'Repeated completion does not duplicate gift email');
expect_true($sent[0]['email']==='friend@example.test', 'Gift email is sent to designated recipient');

$orders[503] = new WC_Order(503,true,[40=>new WC_Order_Item_Product(40,'gift',10000,'friend@example.test')],'50.00');
MDO_Club::issue_prepaid(503);
expect_true(!isset(MDO_Club_Ledger::$gifts['503:40']), 'Underpaid gift is never issued');
echo "Club EMDO offline prepaid payments: OK\n";
