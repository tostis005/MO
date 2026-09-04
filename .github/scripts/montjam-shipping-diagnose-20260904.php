<?php
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;

function msd_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

$montjam = get_user_by('login', 'montjam');
if (!$montjam) throw new RuntimeException('Montjam vendor not found');

$ids = [(int)$montjam->ID];
$hidalgo_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users} WHERE display_name LIKE '%Hidalgo%'");
$hidalgo_meta_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('store_name','wcfmmp_profile_settings') AND meta_value LIKE %s",
    '%Hidalgo%'
));
$ids = array_values(array_unique(array_merge($ids, array_map('intval', $hidalgo_ids), array_map('intval', $hidalgo_meta_ids))));

foreach ($ids as $uid) {
    $u = get_user_by('id', $uid);
    if (!$u) continue;
    $store = get_user_meta($uid, 'store_name', true);
    $profile = get_user_meta($uid, 'wcfmmp_profile_settings', true);
    $summary = [
        'id' => $uid,
        'login' => $u->user_login,
        'display_name' => $u->display_name,
        'store_name' => $store,
    ];
    if (is_array($profile)) {
        foreach ($profile as $k => $v) {
            if (stripos((string)$k, 'ship') !== false || stripos((string)$k, 'zone') !== false || stripos((string)$k, 'country') !== false) {
                $summary['profile_' . $k] = $v;
            }
        }
    }
    msd_out('VENDOR', $summary);

    $metas = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND (meta_key LIKE '%%ship%%' OR meta_key LIKE '%%zone%%' OR meta_key LIKE '%%wcfm%%') ORDER BY meta_key",
        $uid
    ), ARRAY_A);
    $clean = [];
    foreach ($metas as $m) {
        $key = $m['meta_key'];
        if (in_array($key, ['session_tokens'], true)) continue;
        $val = maybe_unserialize($m['meta_value']);
        if ($key === 'wcfmmp_profile_settings' && is_array($val)) {
            $filtered = [];
            foreach ($val as $k => $v) {
                if (stripos((string)$k, 'ship') !== false || stripos((string)$k, 'zone') !== false || stripos((string)$k, 'country') !== false) $filtered[$k] = $v;
            }
            $val = $filtered;
        }
        $clean[] = ['meta_key' => $key, 'value' => $val];
    }
    msd_out('USERMETA_' . $uid, $clean);
}

$wcfm_locations = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';
$wcfm_methods   = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
$tables = [$wcfm_locations, $wcfm_methods];
msd_out('SHIPPING_TABLES', $tables);
foreach ($tables as $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) continue;
    $cols = $wpdb->get_results("DESCRIBE `$table`", ARRAY_A);
    msd_out('TABLE_SCHEMA_' . $table, $cols);
    $colnames = array_column($cols, 'Field');
    if (in_array('vendor_id', $colnames, true)) {
        foreach ($ids as $uid) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE vendor_id=%d ORDER BY 1", $uid), ARRAY_A);
            if ($rows) msd_out('ROWS_' . $table . '_' . $uid, $rows);
        }
    }
}

// Map every WooCommerce zone currently used by Hidalgo de la Jara (vendor 6).
$core_zones = $wpdb->prefix . 'woocommerce_shipping_zones';
$core_locs  = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
$core_methods = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
$hidalgo_zone_ids = array_map('intval', $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT zone_id FROM `$wcfm_methods` WHERE vendor_id=%d AND is_enabled=1 ORDER BY zone_id",
    6
)));
msd_out('HIDALGO_ZONE_IDS', $hidalgo_zone_ids);
foreach ($hidalgo_zone_ids as $zone_id) {
    $zone = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$core_zones` WHERE zone_id=%d", $zone_id), ARRAY_A);
    $locs = $wpdb->get_results($wpdb->prepare(
        "SELECT location_code, location_type FROM `$core_locs` WHERE zone_id=%d ORDER BY location_type, location_code",
        $zone_id
    ), ARRAY_A);
    $global_methods = $wpdb->get_results($wpdb->prepare(
        "SELECT instance_id, method_id, method_order, is_enabled FROM `$core_methods` WHERE zone_id=%d ORDER BY method_order, instance_id",
        $zone_id
    ), ARRAY_A);
    $hid_methods = $wpdb->get_results($wpdb->prepare(
        "SELECT instance_id, method_id, is_enabled, settings FROM `$wcfm_methods` WHERE vendor_id=%d AND zone_id=%d ORDER BY instance_id",
        6, $zone_id
    ), ARRAY_A);
    foreach ($hid_methods as &$m) $m['settings_decoded'] = maybe_unserialize($m['settings']);
    unset($m);
    msd_out('ZONE_' . $zone_id, [
        'zone' => $zone,
        'locations' => $locs,
        'global_methods' => $global_methods,
        'hidalgo_methods' => $hid_methods,
    ]);
}

// Also show any current Montjam zone methods, if they exist.
$montjam_methods = $wpdb->get_results($wpdb->prepare(
    "SELECT instance_id, method_id, zone_id, is_enabled, settings FROM `$wcfm_methods` WHERE vendor_id=%d ORDER BY zone_id, instance_id",
    (int)$montjam->ID
), ARRAY_A);
foreach ($montjam_methods as &$m) $m['settings_decoded'] = maybe_unserialize($m['settings']);
unset($m);
msd_out('MONTJAM_METHODS', $montjam_methods);
