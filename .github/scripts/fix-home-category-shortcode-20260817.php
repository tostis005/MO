<?php
if (!defined('ABSPATH')) { exit(1); }

$front = (int) get_option('page_on_front');
if (!$front) {
    fwrite(STDERR, "front_page_missing\n");
    exit(1);
}

$raw = get_post_meta($front, '_elementor_data', true);
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "elementor_data_invalid\n");
    exit(1);
}

$target_id = '97675ae';
$fixed_shortcode = '[wpos_product_categories height="400" number="6" ids="149,16,27,164,105,243"]';
$changed = 0;

function mdo_fix_home_category_widget(&$nodes, $target_id, $fixed_shortcode, &$changed) {
    if (!is_array($nodes)) return;
    foreach ($nodes as &$node) {
        if (!is_array($node)) continue;
        if (isset($node['id']) && $node['id'] === $target_id) {
            if (!isset($node['settings']) || !is_array($node['settings'])) {
                $node['settings'] = array();
            }
            $node['settings']['editor'] = $fixed_shortcode;
            $changed++;
        }
        if (!empty($node['elements']) && is_array($node['elements'])) {
            mdo_fix_home_category_widget($node['elements'], $target_id, $fixed_shortcode, $changed);
        }
    }
}

function mdo_get_home_category_widget_editor($nodes, $target_id) {
    if (!is_array($nodes)) return null;
    foreach ($nodes as $node) {
        if (!is_array($node)) continue;
        if (isset($node['id']) && $node['id'] === $target_id) {
            return isset($node['settings']['editor']) ? (string) $node['settings']['editor'] : '';
        }
        if (!empty($node['elements']) && is_array($node['elements'])) {
            $found = mdo_get_home_category_widget_editor($node['elements'], $target_id);
            if (null !== $found) return $found;
        }
    }
    return null;
}

mdo_fix_home_category_widget($data, $target_id, $fixed_shortcode, $changed);
if (1 !== $changed) {
    fwrite(STDERR, "unexpected_widget_matches={$changed}\n");
    exit(1);
}

$json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!$json) {
    fwrite(STDERR, "json_encode_failed\n");
    exit(1);
}

update_post_meta($front, '_elementor_data', wp_slash($json));
clean_post_cache($front);

if (class_exists('Elementor\\Plugin')) {
    try {
        $plugin = \Elementor\Plugin::$instance;
        if ($plugin && isset($plugin->files_manager)) {
            $plugin->files_manager->clear_cache();
        }
    } catch (Throwable $e) {}
}

if (function_exists('rocket_clean_domain')) {
    rocket_clean_domain();
}
if (function_exists('wp_cache_clear_cache')) {
    wp_cache_clear_cache();
}
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
}
do_action('litespeed_purge_all');
do_action('autoptimize_action_cachepurged');
wp_cache_flush();

$verify_raw = get_post_meta($front, '_elementor_data', true);
$verify_data = json_decode((string) $verify_raw, true);
if (!is_array($verify_data)) {
    fwrite(STDERR, "verification_json_invalid\n");
    exit(1);
}
$verify_editor = mdo_get_home_category_widget_editor($verify_data, $target_id);
if ($verify_editor !== $fixed_shortcode) {
    fwrite(STDERR, "verification_failed\n");
    fwrite(STDERR, "actual_editor=" . (string) $verify_editor . "\n");
    exit(1);
}
if (false !== strpos($verify_editor, "\xC2\xA0ids=")) {
    fwrite(STDERR, "nbsp_before_ids_still_present\n");
    exit(1);
}

$rendered = do_shortcode($fixed_shortcode);
if (preg_match('#href=["\'][^"\']*/mentta/?["\']#i', $rendered) || preg_match('#>\s*MENTTA\s*<#i', $rendered)) {
    fwrite(STDERR, "rendered_block_still_contains_mentta\n");
    exit(1);
}

echo "front_page_id={$front}\n";
echo "widget_id={$target_id}\n";
echo "shortcode={$verify_editor}\n";
echo "rendered_mentta=no\n";
echo "home_category_shortcode_fixed\n";
