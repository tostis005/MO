<?php
/**
 * Club EMDO cashback regression: product totals only and authenticated buyers only.
 * No WordPress database or gateway is needed for these behavioral checks.
 */
declare(strict_types=1);

define('ABSPATH', __DIR__);
define('MDO_CLUB_RELEASE_APPROVED', true);

$options = array('mdo_club_rewards_enabled' => 'yes', 'mdo_club_reward_percent' => '5');
$current_user = 0;
$orders = array();
$users = array(42 => true, 43 => true);
function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
function get_current_user_id(): int { global $current_user; return $current_user; }
function is_user_logged_in(): bool { return get_current_user_id() > 0; }
function get_user_by($field, $value) { global $users; return $field === 'id' && isset($users[(int) $value]) ? (object) array('ID' => (int) $value) : false; }
function wc_get_order($id) { global $orders; return $orders[$id] ?? false; }
class WC_Order {
    private array $meta = array();
    public int $saves = 0;
    public function __construct(
        private int $id,
        private int $customer,
        private array $items,
        private bool $paid = true,
        private bool $has_paid_date = true,
        public string $billing_email = ''
    ) {}
    public function get_customer_id(): int { return $this->customer; }
    public function set_customer_id(int $id): void { $this->customer = $id; }
    public function get_id(): int { return $this->id; }
    public function is_paid(): bool { return $this->paid; }
    public function get_date_paid() { return $this->has_paid_date ? '2026-11-11' : false; }
    public function get_meta($key) { return $this->meta[$key] ?? ''; }
    public function update_meta_data($key, $value): void { $this->meta[$key] = $value; }
    public function delete_meta_data($key): void { unset($this->meta[$key]); }
    public function save(): void { ++$this->saves; }
    public function get_items($type = ''): array { return $type === '' || $type === 'line_item' ? $this->items : array(); }
}
class RewardItem {
    public function __construct(private int $product, private float $total, private float $tax = 0, private string $kind = '') {}
    public function get_product_id(): int { return $this->product; }
    public function get_total(): float { return $this->total; }
    public function get_total_tax(): float { return $this->tax; }
    public function get_meta($name): string { return $name === '_mdo_club_kind' ? $this->kind : ''; }
}
class MDO_Club_Ledger {
    public static array $credits = array();
    public static function cents($amount): int { return (int) round((float) $amount * 100); }
    public static function change(int $user, int $amount, string $event, string $kind, int $order): bool {
        if (isset(self::$credits[$event])) { return true; }
        self::$credits[$event] = compact('user', 'amount', 'kind', 'order');
        return true;
    }
}
require_once dirname(__DIR__) . '/mdo-supplier-sync/includes/class-mdo-club.php';
function check(bool $pass, string $message): void {
    if (!$pass) { throw new RuntimeException($message); }
}
function register_order(WC_Order $order): WC_Order {
    global $orders;
    $orders[$order->get_id()] = $order;
    return $order;
}
function credit_for(int $order_id): int {
    return (int) (MDO_Club_Ledger::$credits['reward:' . $order_id]['amount'] ?? 0);
}
$products = array(new RewardItem(101, 100.0, 10.0)); // Tax-inclusive product base: 110 EUR.
$shipping = 25.0; // Shipping plus its own tax (if any) must never enter the item sum.
$current_user = 42;
$logged = register_order(new WC_Order(101, 42, $products, true, true, 'buyer@example.test'));
MDO_Club::capture_reward_checkout_identity($logged);
check(42 === (int) $logged->get_meta('_mdo_club_checkout_user_id'), 'Logged-in checkout recorded');
$current_user = 0; // Asynchronous payment callbacks have no logged-in user.
MDO_Club::award_reward(101);
check(550 === credit_for(101), '5% cashback of 110 EUR is 5.50 EUR; 25 EUR shipping excluded');
check(42 === MDO_Club_Ledger::$credits['reward:101']['user'], 'Credit belongs only to the checkout account ID');
MDO_Club::award_reward(101);
check(1 === count(MDO_Club_Ledger::$credits), 'Duplicate payment/completed hooks must not credit twice');

$guest = register_order(new WC_Order(102, 0, $products, true, true, 'buyer@example.test'));
MDO_Club::capture_reward_checkout_identity($guest);
MDO_Club::award_reward(102);
check(0 === credit_for(102), 'Guest with same email as registered user receives no credit');
$guest->set_customer_id(42);
MDO_Club::award_reward(102);
check(0 === credit_for(102), 'Linking a guest order to an account afterward must not back-credit');

$unmarked = register_order(new WC_Order(103, 42, $products));
MDO_Club::award_reward(103);
check(0 === credit_for(103), 'Account-associated orders without confirmed logged-in checkout do not qualify');

$current_user = 43;
$mismatch = register_order(new WC_Order(104, 42, $products));
MDO_Club::capture_reward_checkout_identity($mismatch);
check('' === $mismatch->get_meta('_mdo_club_checkout_user_id'), 'Different logged-in account cannot stamp another account order');
MDO_Club::award_reward(104);
check(0 === credit_for(104), 'Identity mismatch has no cashback');

$current_user = 42;
$block = register_order(new WC_Order(105, 42, $products));
MDO_Club::capture_store_api_reward_checkout_identity($block);
check(1 === $block->saves, 'Store API hook persists the checkout identity before payment');
$current_user = 0;
MDO_Club::award_reward(105);
check(550 === credit_for(105), 'Store API registered checkout can be credited asynchronously');

$current_user = 42;
$draft = register_order(new WC_Order(106, 42, $products));
MDO_Club::capture_store_api_reward_checkout_identity($draft);
$current_user = 0;
MDO_Club::capture_store_api_reward_checkout_identity($draft);
check('' === $draft->get_meta('_mdo_club_checkout_user_id'), 'Guest retry clears stale authenticated Store API draft');
MDO_Club::award_reward(106);
check(0 === credit_for(106), 'Reused guest draft does not receive cashback');

$current_user = 42;
$unpaid = register_order(new WC_Order(107, 42, $products, false));
MDO_Club::capture_reward_checkout_identity($unpaid);
MDO_Club::award_reward(107);
check(0 === credit_for(107), 'Unpaid order never receives cashback');

$prepaid = register_order(new WC_Order(108, 42, array(new RewardItem(902, 50.0, 0, 'gift'))));
MDO_Club::capture_reward_checkout_identity($prepaid);
MDO_Club::award_reward(108);
check(0 === credit_for(108), 'Prepaid gift cards are excluded');

$current_user = 0;
echo "Club EMDO cashback authenticated checkout and shipping exclusions: OK\n";
