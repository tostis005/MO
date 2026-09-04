<?php
/**
 * Read-only production audit for EMDO homepage hero and Specials/Promotions backend.
 */
if (!defined('ABSPATH')) { exit(1); }

function emdo_hs_out($label, $value) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ': ' . (string) $value . "\n";
}

emdo_hs_out('SITEURL', get_option('siteurl'));
emdo_hs_out('ACTIVE_THEME', [
    'stylesheet' => get_stylesheet(),
    'template' => get_template(),
    'stylesheet_dir' => get_stylesheet_directory(),
    'template_dir' => get_template_directory(),
]);

$front_id = (int) get_option('page_on_front');
$front = $front_id ? get_post($front_id) : null;
emdo_hs_out('FRONT_PAGE', $front ? [
    'id' => $front->ID,
    'title' => $front->post_title,
    'slug' => $front->post_name,
    'status' => $front->post_status,
    'template' => get_page_template_slug($front->ID),
    'content' => $front->post_content,
] : null);

$plugins = (array) get_option('active_plugins', []);
emdo_hs_out('ACTIVE_PLUGINS', $plugins);

$post_types = get_post_types([], 'objects');
$pt_dump = [];
foreach ($post_types as $name => $obj) {
    $pt_dump[$name] = [
        'label' => $obj->label,
        'singular' => isset($obj->labels->singular_name) ? $obj->labels->singular_name : '',
        'public' => (bool) $obj->public,
        'show_ui' => (bool) $obj->show_ui,
        'show_in_menu' => (bool) $obj->show_in_menu,
    ];
}
emdo_hs_out('POST_TYPES', $pt_dump);

// Inspect likely Specials/Promotions post types and a handful of recent records.
foreach ($post_types as $name => $obj) {
    $haystack = strtolower($name . ' ' . $obj->label . ' ' . (isset($obj->labels->singular_name) ? $obj->labels->singular_name : ''));
    if (!preg_match('/especial|special|promo|promoc|oferta|offer|highlight|feature/', $haystack)) {
        continue;
    }
    $posts = get_posts([
        'post_type' => $name,
        'post_status' => ['publish','draft','pending','private','future'],
        'numberposts' => 20,
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => false,
    ]);
    foreach ($posts as $p) {
        $meta = [];
        foreach (get_post_meta($p->ID) as $k => $vals) {
            if (preg_match('/product|image|thumb|url|link|price|active|status|start|end|order|position|title|subtitle|text|desc|badge|label|home|desktop|mobile/i', $k)) {
                $meta[$k] = array_map('maybe_unserialize', $vals);
            }
        }
        emdo_hs_out('LIKELY_SPECIAL_RECORD', [
            'post_type' => $name,
            'id' => $p->ID,
            'title' => $p->post_title,
            'status' => $p->post_status,
            'content' => $p->post_content,
            'excerpt' => $p->post_excerpt,
            'featured_image_id' => get_post_thumbnail_id($p->ID),
            'meta' => $meta,
        ]);
    }
}

// Inspect option names likely owned by the EMDO/specials/promo plugin; avoid sensitive key/token/password values.
global $wpdb;
$rows = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name REGEXP 'emdo|mercado.*origen|especial|special|promo|promoc|oferta' ORDER BY option_name LIMIT 200", ARRAY_A);
foreach ($rows as $row) {
    if (preg_match('/pass|token|secret|key|auth|salt/i', $row['option_name'])) {
        continue;
    }
    $val = maybe_unserialize($row['option_value']);
    emdo_hs_out('LIKELY_OPTION', ['name' => $row['option_name'], 'value' => $val]);
}

// Existing Montjam product data needed for the special card.
$product = get_page_by_path('jamon-de-bellota-100-iberico-montjam', OBJECT, 'product');
if ($product) {
    emdo_hs_out('MONTJAM_PRODUCT', [
        'id' => $product->ID,
        'title' => $product->post_title,
        'url' => get_permalink($product->ID),
        'featured_image_id' => get_post_thumbnail_id($product->ID),
        'featured_image_url' => get_the_post_thumbnail_url($product->ID, 'full'),
    ]);
}

// Find theme/front-page related files and emit lines containing hero/home spacing selectors.
$dirs = array_unique(array_filter([get_stylesheet_directory(), get_template_directory()]));
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php','css','scss','js'], true) || $file->getSize() > 1000000) continue;
        $rel = str_replace($dir . DIRECTORY_SEPARATOR, '', $path);
        if (!preg_match('/front|home|style|hero|landing|main|custom/i', $rel)) continue;
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) continue;
        $matches = [];
        foreach ($lines as $i => $line) {
            if (preg_match('/hero|front-page|home-|homepage|landing|producers|productores|origen/i', $line)) {
                $matches[] = ($i + 1) . ': ' . trim($line);
                if (count($matches) >= 80) break;
            }
        }
        if ($matches) emdo_hs_out('THEME_FILE_MATCHES', ['file' => $rel, 'matches' => $matches]);
    }
}
