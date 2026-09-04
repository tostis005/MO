<?php
/** Read-only audit of WCFM/YITH structures required for Montjam setup. */
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
function mj2_out($label, $value) {
    if (is_array($value) || is_object($value)) $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo $label . ': ' . (string)$value . "\n";
}

$active = (array)get_option('active_plugins', []);
mj2_out('WCFM_PLUGINS', array_values(array_filter($active, fn($p)=>stripos($p,'wcfm')!==false)));
mj2_out('YITH_WAPO_PLUGINS', array_values(array_filter($active, fn($p)=>stripos($p,'product-add-ons')!==false || stripos($p,'wapo')!==false)));

$hid = get_user_by('id', 6);
if ($hid) {
    $all = get_user_meta(6);
    $public = ['store_name'=>null,'store_slug'=>null,'gravatar'=>null,'banner'=>null,'list_banner_type'=>null,'wcfmmp_profile_settings'=>null,'_wcfmmp_profile_settings'=>null];
    foreach ($public as $k=>$_) {
        if (isset($all[$k][0])) {
            $v = maybe_unserialize($all[$k][0]);
            if (($k==='wcfmmp_profile_settings' || $k==='_wcfmmp_profile_settings') && is_array($v)) {
                $v = array_intersect_key($v, array_flip(['store_name','store_slug','gravatar','banner','list_banner_type']));
            }
            $public[$k] = $v;
        }
    }
    mj2_out('HIDALGO_VENDOR_PUBLIC', ['ID'=>$hid->ID,'user_nicename'=>$hid->user_nicename,'display_name'=>$hid->display_name,'roles'=>$hid->roles,'meta'=>$public]);
}

$tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->esc_like($wpdb->prefix . 'yith_wapo') . "%'");
mj2_out('YITH_TABLES', $tables);
foreach ($tables as $table) {
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `$table`", ARRAY_A);
    mj2_out('SCHEMA_' . basename($table), array_map(fn($r)=>[$r['Field'],$r['Type']], $cols));
}

$ids = [1350,1356];
$groups = $wpdb->prefix . 'yith_wapo_groups';
$types  = $wpdb->prefix . 'yith_wapo_types';
if (in_array($groups, $tables, true)) {
    foreach ($ids as $pid) {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$groups` WHERE FIND_IN_SET(%d, products_id)", $pid), ARRAY_A);
        mj2_out('OLD_WAPO_GROUPS_PRODUCT_' . $pid, $rows);
        if ($rows && in_array($types, $tables, true)) {
            foreach ($rows as $g) {
                $trows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$types` WHERE group_id=%d ORDER BY priority,id", $g['id']), ARRAY_A);
                mj2_out('OLD_WAPO_TYPES_GROUP_' . $g['id'], $trows);
            }
        }
    }
}

$blocks = $wpdb->prefix . 'yith_wapo_blocks';
$assoc  = $wpdb->prefix . 'yith_wapo_blocks_assoc';
$addons = $wpdb->prefix . 'yith_wapo_addons';
if (in_array($blocks, $tables, true)) {
    $brows = $wpdb->get_results("SELECT * FROM `$blocks` ORDER BY priority,id LIMIT 100", ARRAY_A);
    foreach ($brows as &$b) {
        if (isset($b['settings'])) $b['settings'] = maybe_unserialize($b['settings']);
    }
    unset($b);
    mj2_out('WAPO_BLOCKS', $brows);
}
if (in_array($assoc, $tables, true)) {
    foreach ($ids as $pid) {
        $arows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$assoc` WHERE object=%s ORDER BY rule_id,id", (string)$pid), ARRAY_A);
        mj2_out('WAPO_ASSOC_PRODUCT_' . $pid, $arows);
    }
    $all_assoc = $wpdb->get_results("SELECT * FROM `$assoc` ORDER BY rule_id,id LIMIT 200", ARRAY_A);
    mj2_out('WAPO_ASSOC_SAMPLE', $all_assoc);
}
if (in_array($addons, $tables, true)) {
    $arows = $wpdb->get_results("SELECT * FROM `$addons` ORDER BY block_id,priority,id LIMIT 200", ARRAY_A);
    foreach ($arows as &$a) {
        foreach (['settings','options','rules'] as $k) if (isset($a[$k])) $a[$k] = maybe_unserialize($a[$k]);
    }
    unset($a);
    mj2_out('WAPO_ADDONS', $arows);
}

foreach (['pa_productor','pa_origen','pa_curacion','pa_rango-peso','pa_tipo-pieza','pa_calidad','pa_raza-iberica','pa_alimentacion','pa_con-dop','pa_preparacion','pa_tamano'] as $tax) {
    if (!taxonomy_exists($tax)) continue;
    $terms = get_terms(['taxonomy'=>$tax,'hide_empty'=>false]);
    if (is_wp_error($terms)) continue;
    $filtered=[];
    foreach ($terms as $t) {
        if ($tax==='pa_tamano' || preg_match('/mont|huelva|bellota|100|50|24|36|jam[oó]n|paleta|pieza|cuchillo|deshues|lonch|5-|55|6-|65|7-|75|8-|85/i', $t->name.' '.$t->slug)) {
            $filtered[]=['id'=>$t->term_id,'slug'=>$t->slug,'name'=>$t->name];
        }
    }
    mj2_out('TERMS_'.$tax, $filtered);
}
