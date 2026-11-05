<?php
/**
 * Regression for gift-card / wallet top-up carts submitted from cached public
 * Club pages through admin-post.php, including guests and non-empty carts.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('MDO_CLUB_RELEASE_APPROVED', true);
$options = [
	'mdo_club_prepaid_enabled' => 'yes',
	'mdo_club_product_wallet' => 901,
	'mdo_club_product_gift' => 902,
];
$notices = [];
function get_option(string $key, $default=false) { global $options; return $options[$key] ?? $default; }
function wc_get_product(int $id) { return in_array($id,[901,902],true) ? new Test_Product() : false; }
function wc_add_notice(string $text, string $type='success'): void { global $notices; $notices[] = [$type,$text]; }
function wp_generate_uuid4(): string { static $n=0; $n++; return 'uuid-'.$n; }
class Test_Product {
	public function is_purchasable(): bool { return true; }
	public function is_virtual(): bool { return true; }
}
class Test_Session {
	public bool $cookieSet=false;
	public bool $saved=false;
	public function set_customer_session_cookie(bool $v): void {$this->cookieSet=$v;}
	public function save_data(): void {$this->saved=true;}
}
class Test_Cart {
	public array $items=[];
	public bool $totals=false;
	public bool $cleared=false;
	public function is_empty(): bool { return !$this->items; }
	public function empty_cart(): void { $this->items=[];$this->cleared=true; }
	public function get_cart(): array { return $this->items; }
	public function add_to_cart(int $id, int $qty, int $variation, array $variations, array $metadata) {
		if (!MDO_Club::validate_cart(true,$id)) return false;
		$this->items[]=['product_id'=>$id,'quantity'=>$qty]+$metadata;
		return 'cart-key-'.count($this->items);
	}
	public function calculate_totals(): void { $this->totals=true; }
}
class Test_WC {
	public ?Test_Cart $cart=null;
	public ?Test_Session $session=null;
}
$wc=new Test_WC();
function WC() {global $wc; return $wc;}
function wc_load_cart(): void {
	global $wc;
	if (!$wc->session) $wc->session=new Test_Session();
	if (!$wc->cart) $wc->cart=new Test_Cart();
}
require_once dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-club.php';
function affirm(bool $condition,string $message):void {
	if (!$condition) throw new RuntimeException($message);
}
// Simulate public gift form submitted by a guest without preloaded Woo session.
affirm(MDO_Club::prepare_prepaid_cart('gift',5000,'recipient@example.test','Felicitaciones'), 'Gift purchase adds to a fresh session');
affirm($wc->session->cookieSet && $wc->session->saved, 'Guest session cookie and cart persist across the checkout redirect');
affirm($wc->cart->totals && count($wc->cart->items)===1, 'Guest checkout has a calculated cart');
$item=$wc->cart->items[0];
affirm($item['product_id']===902 && $item['mdo_club_kind']==='gift', 'Hidden virtual gift product in cart');
affirm($item['mdo_club_amount_cents']===5000 && $item['mdo_club_recipient']==='recipient@example.test', 'Gift amount and recipient survive');
affirm($item['mdo_club_message']==='Felicitaciones','Gift message stored on cart');
// Always start a separate digital purchase. No checkbox needed.
$wc->cart->items=[['product_id'=>123,'quantity'=>1]];
affirm(MDO_Club::prepare_prepaid_cart('wallet',2500,'',''), 'Existing goods replaced automatically by top-up');
affirm($wc->cart->cleared && count($wc->cart->items)===1,'Old cart automatically replaced');
affirm($wc->cart->items[0]['product_id']===901 && $wc->cart->items[0]['mdo_club_amount_cents']===2500,'Top-up product and amount added correctly');
// A broken/disabled product must never clear other items.
$wc->cart->items=[['product_id'=>123,'quantity'=>1]];
$options['mdo_club_product_gift']=0;
affirm(!MDO_Club::prepare_prepaid_cart('gift',5000,'recipient@example.test',''), 'Unavailable digital product refuses purchase');
affirm($wc->cart->items[0]['product_id']===123, 'Unavailable gift does not erase customer's existing cart');
$options['mdo_club_product_gift']=902;
$wc->cart->items=[['product_id'=>902,'quantity'=>1,'mdo_club_kind'=>'gift','mdo_club_amount_cents'=>5000]];

// Source-level checks ensure no same-page-only POST for new gift purchases.
$src=file_get_contents(dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-club.php');
affirm(str_contains($src,"admin_post_nopriv_mdo_club_purchase"),'Guest purchase endpoint exists');
affirm(str_contains($src,'admin-post.php'),'Purchase forms bypass public page cache');
affirm(!str_contains($src, 'name="mdo_club_replace_cart"'), 'No standalone-order confirmation checkbox remains');
affirm(str_contains($src,'wp_safe_redirect( wc_get_checkout_url(), 303 )'),'Successful purchases redirect to normal Woo checkout');
affirm(str_contains($src,'wc_print_notices()'),'Purchase errors display to customer');
echo "Club EMDO purchase-to-checkout unit tests: OK\n";
