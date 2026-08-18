<?php
if (!defined('ABSPATH')) { exit; }

function emdo_audit_out($label, $value) {
    echo $label . ' ' . wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

$targets = [
    'politica','condiciones-especiales','contacto','politica-de-cookies',
    'politica-de-privacidad','privacidad','aviso-legal','envios','devoluciones',
    'shipping','returns','legal-notice','privacy-policy','cookie-policy','terms-and-conditions'
];

foreach ($targets as $slug) {
    $p = get_page_by_path($slug, OBJECT, 'page');
    if (!$p) { continue; }
    emdo_audit_out('PAGE', [
        'id' => $p->ID,
        'slug' => $p->post_name,
        'title' => $p->post_title,
        'status' => $p->post_status,
        'lang' => function_exists('pll_get_post_language') ? pll_get_post_language($p->ID) : '',
        'translations' => function_exists('pll_get_post_translations') ? pll_get_post_translations($p->ID) : [],
        'elementor_edit_mode' => get_post_meta($p->ID, '_elementor_edit_mode', true),
        'template' => get_post_meta($p->ID, '_wp_page_template', true),
    ]);
}

$q = new WP_Query([
    'post_type' => 'page',
    'post_status' => ['publish','draft','private'],
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

foreach ($q->posts as $p) {
    $hay = mb_strtolower($p->post_title . ' ' . $p->post_name);
    if (preg_match('/(pol[ií]tica|priv|cookie|term|condici|contact|legal|env[ií]o|shipping|return|devol|refund)/u', $hay)) {
        emdo_audit_out('MATCH', [
            'id' => $p->ID,
            'slug' => $p->post_name,
            'title' => $p->post_title,
            'status' => $p->post_status,
            'lang' => function_exists('pll_get_post_language') ? pll_get_post_language($p->ID) : '',
            'elementor_edit_mode' => get_post_meta($p->ID, '_elementor_edit_mode', true),
            'template' => get_post_meta($p->ID, '_wp_page_template', true),
        ]);
    }

    if ($p->post_status === 'publish') {
        $text = preg_replace('/\s+/u', ' ', trim(wp_strip_all_tags((string) $p->post_content)));
        if (preg_match('/\b(?:NIF|CIF|DNI|tax\s*id|fiscal)\b/ui', $text) || preg_match('/Jos[eé]\s+Antonio/ui', $text)) {
            emdo_audit_out('PUBLIC_HINT', [
                'id' => $p->ID,
                'slug' => $p->post_name,
                'title' => $p->post_title,
                'excerpt' => mb_substr($text, 0, 1500),
            ]);
        }
    }
}

foreach (['woocommerce_store_address','woocommerce_store_address_2','woocommerce_store_city','woocommerce_store_postcode','woocommerce_default_country','admin_email'] as $k) {
    emdo_audit_out('OPTION', [$k => get_option($k, '')]);
}

foreach (get_users(['role' => 'administrator', 'fields' => ['ID','display_name','user_nicename']]) as $u) {
    emdo_audit_out('ADMIN', ['id' => $u->ID, 'display_name' => $u->display_name, 'nicename' => $u->user_nicename]);
}

global $wpdb;
$rows = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name REGEXP '(nif|cif|vat|tax[_-]?id|fiscal)' LIMIT 100", ARRAY_A);
foreach ($rows as $r) {
    if (preg_match('/(api|key|token|secret|password|credential)/i', $r['option_name'])) { continue; }
    $v = maybe_unserialize($r['option_value']);
    $s = wp_json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (strlen((string) $s) > 1500) { $s = substr((string) $s, 0, 1500) . '...'; }
    emdo_audit_out('ID_OPTION', ['name' => $r['option_name'], 'value' => $s]);
}

emdo_audit_out('MENU_LOCATIONS', get_nav_menu_locations());
foreach (wp_get_nav_menus() as $m) {
    emdo_audit_out('MENU', [
        'id' => $m->term_id,
        'name' => $m->name,
        'slug' => $m->slug,
        'lang' => function_exists('pll_get_term_language') ? pll_get_term_language($m->term_id) : '',
    ]);
    foreach (wp_get_nav_menu_items($m->term_id) ?: [] as $it) {
        emdo_audit_out('MENU_ITEM', [
            'menu' => $m->term_id,
            'id' => $it->ID,
            'title' => $it->title,
            'url' => $it->url,
            'object_id' => $it->object_id,
            'order' => $it->menu_order,
        ]);
    }
}
