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
    if (!is_array($nodes)) {
        return;
    }
    foreach ($nodes as &$node) {
        if (!is_array($node)) {
            continue;
        }
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
    } catch (Throwable $e) {
        // The meta update is the source of truth; cache clearing is best effort.
    }
}

$verify = get_post_meta($front, '_elementor_data', true);
if (false === strpos((string) $verify, $fixed_shortcode)) {
    fwrite(STDERR, "verification_failed\n");
    exit(1);
}
if (false !== strpos((string) $verify, "\xC2\xA0ids=")) {
    fwrite(STDERR, "nbsp_before_ids_still_present\n");
    exit(1);
}

echo "front_page_id={$front}\n";
echo "widget_id={$target_id}\n";
echo "shortcode={$fixed_shortcode}\n";
echo "home_category_shortcode_fixed\n";
