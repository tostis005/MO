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

$tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->esc_like($wpdb->prefix) . "%wcfm%shipping%'");
if (!$tables) {
    $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->esc_like($wpdb->prefix) . "%shipping%'");
}
msd_out('SHIPPING_TABLES', $tables);
foreach ($tables as $table) {
    $cols = $wpdb->get_results("DESCRIBE `$table`", ARRAY_A);
    msd_out('TABLE_SCHEMA_' . $table, $cols);
    $colnames = array_column($cols, 'Field');
    $vendor_col = null;
    foreach (['vendor_id','user_id','seller_id','store_id'] as $cand) {
        if (in_array($cand, $colnames, true)) { $vendor_col = $cand; break; }
    }
    if ($vendor_col) {
        foreach ($ids as $uid) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE `$vendor_col`=%d ORDER BY 1", $uid), ARRAY_A);
            if ($rows) msd_out('ROWS_' . $table . '_' . $uid, $rows);
        }
    } else {
        $rows = $wpdb->get_results("SELECT * FROM `$table` LIMIT 100", ARRAY_A);
        if ($rows) msd_out('ROWS_' . $table, $rows);
    }
}

$plugins = get_option('active_plugins', []);
$wcfm = array_values(array_filter($plugins, function($p){ return stripos($p, 'wcfm') !== false; }));
msd_out('ACTIVE_WCFM_PLUGINS', $wcfm);
