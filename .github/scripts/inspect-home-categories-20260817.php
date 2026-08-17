<?php
if (!defined('ABSPATH')) { exit(1); }
$front = (int) get_option('page_on_front');
echo "front_page_id={$front}\n";
$post = get_post($front);
if ($post) {
    echo "front_title=" . $post->post_title . "\n";
    echo "post_content_has_mentta=" . (stripos($post->post_content, 'mentta') !== false ? 'yes' : 'no') . "\n";
}
$data = get_post_meta($front, '_elementor_data', true);
echo "elementor_data_length=" . strlen((string)$data) . "\n";
echo "elementor_data_has_mentta=" . (stripos((string)$data, 'mentta') !== false ? 'yes' : 'no') . "\n";
$decoded = json_decode((string)$data, true);
function walk_mdo($nodes, $path='root') {
    if (!is_array($nodes)) return;
    foreach ($nodes as $idx => $node) {
        if (!is_array($node)) continue;
        $id = isset($node['id']) ? $node['id'] : '';
        $type = isset($node['widgetType']) ? $node['widgetType'] : (isset($node['elType']) ? $node['elType'] : '');
        $settings = isset($node['settings']) && is_array($node['settings']) ? $node['settings'] : array();
        $blob = strtolower($type . ' ' . wp_json_encode($settings));
        if (strpos($blob, 'categor') !== false || strpos($blob, 'product') !== false || strpos($blob, 'mentta') !== false) {
            echo "NODE path={$path}.{$idx} id={$id} type={$type}\n";
            echo "SETTINGS " . wp_json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        }
        if (!empty($node['elements'])) {
            walk_mdo($node['elements'], $path . '.' . $idx . '.elements');
        }
    }
}
walk_mdo($decoded);
