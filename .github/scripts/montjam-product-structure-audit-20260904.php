<?php
/**
 * Read-only audit for Montjam product creation.
 * Prints only public/non-sensitive product and vendor structure needed to mirror existing Hidalgo de la Jara products.
 */
if (!defined('ABSPATH')) { exit(1); }

function emdo_mj_out($label, $value) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ': ' . (string) $value . "\n";
}

emdo_mj_out('SITEURL', get_option('siteurl'));
emdo_mj_out('WOOCOMMERCE_ACTIVE', class_exists('WooCommerce') ? 'yes' : 'no');
emdo_mj_out('DOKAN_ACTIVE', function_exists('dokan') || defined('DOKAN_PLUGIN_VERSION') ? 'yes' : 'no');

$plugins = (array) get_option('active_plugins', []);
$interesting_plugins = array_values(array_filter($plugins, function($p) {
    return preg_match('/dokan|woocommerce|addon|extra|option|product/i', $p);
}));
emdo_mj_out('INTERESTING_PLUGINS', $interesting_plugins);

$users = get_users([
    'search' => '*Hidalgo*',
    'search_columns' => ['user_login','display_name'],
    'number' => 20,
]);
foreach ($users as $u) {
    emdo_mj_out('HIDALGO_USER', [
        'ID' => $u->ID,
        'login' => $u->user_login,
        'display_name' => $u->display_name,
        'roles' => $u->roles,
        'store_name' => get_user_meta($u->ID, 'dokan_store_name', true),
        'store_settings' => get_user_meta($u->ID, 'dokan_profile_settings', true),
    ]);
}

$mont_users = get_users([
    'search' => '*Mont*',
    'search_columns' => ['user_login','display_name'],
    'number' => 20,
]);
foreach ($mont_users as $u) {
    emdo_mj_out('MONT_USER', [
        'ID' => $u->ID,
        'login' => $u->user_login,
        'display_name' => $u->display_name,
        'roles' => $u->roles,
        'store_name' => get_user_meta($u->ID, 'dokan_store_name', true),
    ]);
}

$targets = [
    'jamon-de-bellota-100-iberico',
    'paleta-de-bellota-100-iberico',
    'jamon-de-bellota-50-iberica-brida-roja',
];

foreach ($targets as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'product');
    if (!$post) {
        emdo_mj_out('PRODUCT_MISSING', $slug);
        continue;
    }
    emdo_mj_out('PRODUCT', [
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => $post->post_title,
        'author' => $post->post_author,
        'status' => $post->post_status,
    ]);

    $taxes = get_object_taxonomies('product');
    $tax_dump = [];
    foreach ($taxes as $tax) {
        $terms = wp_get_post_terms($post->ID, $tax, ['fields' => 'all']);
        if (!is_wp_error($terms) && $terms) {
            $tax_dump[$tax] = array_map(function($t){ return ['id'=>$t->term_id,'slug'=>$t->slug,'name'=>$t->name]; }, $terms);
        }
    }
    emdo_mj_out('TAXONOMIES_' . $post->ID, $tax_dump);

    $all_meta = get_post_meta($post->ID);
    $keep = [];
    foreach ($all_meta as $key => $vals) {
        if (preg_match('/addon|option|extra|dokan|vendor|product_attributes|default_attributes|price|sku|stock|manage_stock|shipping|weight|thumbnail|yoast|rank_math/i', $key)) {
            $keep[$key] = array_map(function($v){
                $u = maybe_unserialize($v);
                return $u;
            }, $vals);
        }
    }
    emdo_mj_out('META_' . $post->ID, $keep);

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($post->ID);
        if ($product && $product->is_type('variable')) {
            $vars = [];
            foreach ($product->get_children() as $vid) {
                $v = wc_get_product($vid);
                if (!$v) continue;
                $vars[] = [
                    'id' => $vid,
                    'attributes' => $v->get_attributes(),
                    'regular_price' => $v->get_regular_price(),
                    'sale_price' => $v->get_sale_price(),
                    'price' => $v->get_price(),
                    'sku' => $v->get_sku(),
                    'status' => $v->get_status(),
                ];
            }
            emdo_mj_out('VARIATIONS_' . $post->ID, $vars);
        }
    }
}
