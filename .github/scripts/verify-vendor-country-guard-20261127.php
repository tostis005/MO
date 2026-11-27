<?php
/** Read-only verification in a fully loaded WooCommerce runtime. Never creates an order. */
$source = getenv('MDO_GUARD_SOURCE');
if (!class_exists('MDO_Vendor_Country_Guard')) {
    if (!$source || !is_readable($source)) { throw new RuntimeException('Guard source missing'); }
    require $source;
}
if (!function_exists('WC') || !WC()->countries || !class_exists('WC_Shipping_Zones')) {
    throw new RuntimeException('WooCommerce unavailable');
}
function mdo_guard_assert($ok, $label) {
    if (!$ok) { throw new RuntimeException($label); }
}
global $wpdb;
$vendors = $wpdb->get_results(
    "SELECT p.post_author AS vendor, MIN(p.ID) AS product_id, COUNT(*) AS products FROM {$wpdb->posts} p
     WHERE p.post_type='product' AND p.post_status='publish'
     AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id=p.ID AND m.meta_key='_virtual' AND m.meta_value='yes')
     GROUP BY p.post_author", ARRAY_A
);
mdo_guard_assert(is_array($vendors) && count($vendors) > 0, 'No published physical products');
$positive = array();
$negative = array();
$known = WC()->countries->get_countries();
foreach ($vendors as $row) {
    $vendor = (int)$row['vendor'];
    $user = get_userdata($vendor);
    $codes = MDO_Vendor_Country_Guard::allowed_countries($vendor);
    $mode = MDO_Vendor_Country_Guard::mode($vendor);
    echo wp_json_encode(array('vendor'=>$vendor, 'producer'=>$user ? $user->display_name : '', 'mode'=>$mode, 'countries'=>$codes, 'products'=>(int)$row['products']), JSON_UNESCAPED_UNICODE) . PHP_EOL;
    // Unknown WCFM shipping modes are deliberately not guessed in a production rollout.
    mdo_guard_assert(in_array($mode, array('by_zone','by_country','by_weight',''), true), 'Unverified shipping mode for producer ' . $vendor);
    $pid = (int)$row['product_id'];
    $product = wc_get_product($pid);
    if (!$product || !$product->needs_shipping()) { continue; }
    $item = array('product_id'=>$pid, 'data'=>$product, 'quantity'=>1);
    $rate = new WC_Shipping_Rate('guard-test', 'Test explicit zero', 0);
    if ($codes) {
        $country = $codes[0];
        $package = array('destination'=>array('country'=>$country),'contents'=>array('valid'=>$item));
        mdo_guard_assert(MDO_Vendor_Country_Guard::can_ship($vendor, $country), 'Configured country rejected');
        mdo_guard_assert(MDO_Vendor_Country_Guard::filter_rates(array('test'=>$rate),$package) === array('test'=>$rate), 'Valid zero rate changed');
        $positive[] = array('vendor'=>$vendor,'codes'=>$codes,'item'=>$item);
    }
    $unlisted = array_values(array_diff(array_keys($known), $codes));
    if ($unlisted) {
        $country = in_array('IT',$unlisted,true) ? 'IT' : $unlisted[0];
        $package = array('destination'=>array('country'=>$country),'contents'=>array('blocked'=>$item));
        mdo_guard_assert(!MDO_Vendor_Country_Guard::can_ship($vendor,$country), 'Unconfigured country accepted');
        mdo_guard_assert(MDO_Vendor_Country_Guard::filter_rates(array('test'=>$rate),$package) === array(), 'Unconfigured zero rate survived');
        $negative[] = array('vendor'=>$vendor,'codes'=>$codes,'item'=>$item);
    }
}
mdo_guard_assert(count($positive)>0 && count($negative)>0, 'No positive/negative live fixtures');
$mixed = false;
foreach ($positive as $a) {
    foreach ($negative as $b) {
        if ($a['vendor'] === $b['vendor']) { continue; }
        $dest = array_values(array_diff($a['codes'],$b['codes']));
        if (!$dest) { continue; }
        $country = $dest[0];
        $cart = array('valid'=>$a['item'],'blocked'=>$b['item']);
        $blocked = MDO_Vendor_Country_Guard::incompatible($cart,$country);
        mdo_guard_assert(array_keys($blocked) === array('blocked'), 'Mixed cart did not isolate invalid item');
        mdo_guard_assert(isset($cart['valid']) && isset($cart['blocked']), 'Cart was mutated during validation');
        echo 'MIXED_CART_OK country=' . $country . ' valid_vendor=' . $a['vendor'] . ' blocked_vendor=' . $b['vendor'] . PHP_EOL;
        $mixed = true;
        break 2;
    }
}
mdo_guard_assert($mixed,'No mixed-cart fixture with different coverage');
echo "MDO_COUNTRY_GUARD_TESTS_OK\n";
