<?php
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;

function ssd_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

$selectos_id = 0;
if (class_exists('MDO_Database')) {
    $supplier_table = MDO_Database::table('suppliers');
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $supplier_table)) === $supplier_table) {
        $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT vendor_user_id FROM `$supplier_table`
             WHERE LOWER(name)=LOWER(%s) OR code=%s OR source_url LIKE %s
             ORDER BY id DESC LIMIT 1",
            'Selectos de Castilla', 'selectos-de-castilla', '%selectosdecastilla.com%'
        ));
    }
}
if (!$selectos_id) {
    $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta}
         WHERE meta_key='store_name' AND LOWER(meta_value)=LOWER(%s) LIMIT 1",
        'Selectos de Castilla'
    ));
}
if (!$selectos_id) {
    $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE LOWER(display_name)=LOWER(%s) LIMIT 1",
        'Selectos de Castilla'
    ));
}
if (!$selectos_id) throw new RuntimeException('Selectos de Castilla vendor not found');

$hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM {$wpdb->usermeta}
     WHERE meta_key='store_name' AND LOWER(meta_value)=LOWER(%s) LIMIT 1",
    'Hidalgo de la Jara'
));
if (!$hidalgo_id) {
    $hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE LOWER(display_name)=LOWER(%s) LIMIT 1",
        'Hidalgo de la Jara'
    ));
}
if (!$hidalgo_id) throw new RuntimeException('Hidalgo de la Jara vendor not found');

$methods = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
$vendor_locs = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';
$zones = $wpdb->prefix . 'woocommerce_shipping_zones';
$zone_locs = $wpdb->prefix . 'woocommerce_shipping_zone_locations';

foreach ([$methods,$vendor_locs,$zones,$zone_locs] as $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        throw new RuntimeException('Missing table: ' . $table);
    }
}

$vendor_summary = function($uid) {
    $u = get_user_by('id', $uid);
    return [
        'id' => (int)$uid,
        'login' => $u ? $u->user_login : null,
        'display_name' => $u ? $u->display_name : null,
        'store_name' => get_user_meta($uid, 'store_name', true),
        '_wcfmmp_shipping' => get_user_meta($uid, '_wcfmmp_shipping', true),
    ];
};
ssd_out('SELECTOS_VENDOR', $vendor_summary($selectos_id));
ssd_out('HIDALGO_VENDOR', $vendor_summary($hidalgo_id));

$countries = function_exists('WC') && WC()->countries ? WC()->countries->get_countries() : [];

$all_zones = $wpdb->get_results("SELECT zone_id,zone_name,zone_order FROM `$zones` ORDER BY zone_order,zone_id", ARRAY_A);
foreach ($all_zones as $z) {
    $zid = (int)$z['zone_id'];
    $locs = $wpdb->get_results($wpdb->prepare(
        "SELECT location_code,location_type FROM `$zone_locs` WHERE zone_id=%d ORDER BY location_type,location_code",
        $zid
    ), ARRAY_A);
    $hid = $wpdb->get_results($wpdb->prepare(
        "SELECT instance_id,method_id,is_enabled,settings FROM `$methods` WHERE vendor_id=%d AND zone_id=%d ORDER BY instance_id",
        $hidalgo_id,$zid
    ), ARRAY_A);
    $sel = $wpdb->get_results($wpdb->prepare(
        "SELECT instance_id,method_id,is_enabled,settings FROM `$methods` WHERE vendor_id=%d AND zone_id=%d ORDER BY instance_id",
        $selectos_id,$zid
    ), ARRAY_A);
    foreach ($hid as &$r) $r['settings_decoded'] = maybe_unserialize($r['settings']);
    unset($r);
    foreach ($sel as &$r) $r['settings_decoded'] = maybe_unserialize($r['settings']);
    unset($r);
    $named = [];
    foreach ($locs as $loc) {
        $code = strtoupper((string)$loc['location_code']);
        $named[] = [
            'code' => $loc['location_code'],
            'type' => $loc['location_type'],
            'name' => $loc['location_type']==='country' ? ($countries[$code] ?? $code) : null,
        ];
    }
    if ($hid || $sel || stripos((string)$z['zone_name'],'portugal')!==false || stripos((string)$z['zone_name'],'espa')!==false || stripos((string)$z['zone_name'],'balea')!==false) {
        ssd_out('ZONE', [
            'zone_id'=>$zid,
            'zone_name'=>$z['zone_name'],
            'zone_order'=>(int)$z['zone_order'],
            'locations'=>$named,
            'hidalgo_methods'=>$hid,
            'selectos_methods'=>$sel,
        ]);
    }
}

$hid_overrides = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$vendor_locs` WHERE vendor_id=%d ORDER BY 1", $hidalgo_id), ARRAY_A);
$sel_overrides = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$vendor_locs` WHERE vendor_id=%d ORDER BY 1", $selectos_id), ARRAY_A);
ssd_out('HIDALGO_LOCATION_OVERRIDES', $hid_overrides);
ssd_out('SELECTOS_LOCATION_OVERRIDES', $sel_overrides);
ssd_out('SELECTOS_SHIPPING_DIAGNOSTIC_OK');
