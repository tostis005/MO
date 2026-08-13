<?php
/** Read-only inspection of production catalog status and vendor state. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$vendors = array(
    3    => '1957',
    6    => 'Hidalgo de la Jara',
    4507 => 'Tolecarnes',
    4508 => 'Puente Robles',
    4509 => 'El Catedrático',
);

$visibility_ids = function_exists('wc_get_product_visibility_term_ids') ? wc_get_product_visibility_term_ids() : array();
$exclude_catalog_id = (int)($visibility_ids['exclude-from-catalog'] ?? 0);
$outofstock_id = (int)($visibility_ids['outofstock'] ?? 0);

foreach ($vendors as $author_id => $vendor) {
    $user = get_userdata($author_id);
    $status_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT post_status, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d GROUP BY post_status ORDER BY post_status",
        $author_id
    ), ARRAY_A);
    $statuses = array();
    foreach ((array)$status_rows as $row) { $statuses[(string)$row['post_status']] = (int)$row['n']; }

    $catalog_hidden = 0;
    $outofstock = 0;
    $archived_instock = 0;
    $archived_visible = 0;
    $rows = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','archived')",
        $author_id
    ));
    foreach (array_map('intval',(array)$rows) as $id) {
        $p = wc_get_product($id);
        if (!$p instanceof WC_Product) { continue; }
        if ('hidden' === $p->get_catalog_visibility()) { ++$catalog_hidden; }
        if (!$p->is_in_stock()) { ++$outofstock; }
        if ('archived' === get_post_status($id) && $p->is_in_stock()) { ++$archived_instock; }
        if ('archived' === get_post_status($id) && 'hidden' !== $p->get_catalog_visibility() && $p->is_in_stock()) { ++$archived_visible; }
    }

    $supplier = $wpdb->get_row($wpdb->prepare(
        "SELECT id, code, active FROM {$wpdb->prefix}mdo_suppliers WHERE vendor_user_id=%d ORDER BY id DESC LIMIT 1",
        $author_id
    ), ARRAY_A);
    $source_statuses = array();
    if ($supplier) {
        $src = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS n FROM {$wpdb->prefix}mdo_source_products WHERE supplier_id=%d GROUP BY status ORDER BY status",
            (int)$supplier['id']
        ), ARRAY_A);
        foreach ((array)$src as $row) { $source_statuses[(string)$row['status']] = (int)$row['n']; }
    }

    $settings = get_user_meta($author_id, 'wcfmmp_profile_settings', true);
    echo 'CATALOG_VENDOR_STATE ' . wp_json_encode(array(
        'id'=>$author_id,
        'vendor'=>$vendor,
        'roles'=>$user instanceof WP_User ? array_values($user->roles) : array(),
        'disable_meta'=>get_user_meta($author_id,'_disable_vendor',true),
        'offline_meta'=>get_user_meta($author_id,'_wcfm_store_offline',true),
        'disabled_helper'=>function_exists('elmercado_wcfm_vendor_is_disabled_010210') ? elmercado_wcfm_vendor_is_disabled_010210($author_id) : null,
        'store_name'=>is_array($settings) ? (string)($settings['store_name'] ?? '') : '',
        'post_statuses'=>$statuses,
        'catalog_hidden'=>$catalog_hidden,
        'outofstock'=>$outofstock,
        'archived_instock'=>$archived_instock,
        'archived_visible_nonhidden'=>$archived_visible,
        'supplier'=>$supplier,
        'source_statuses'=>$source_statuses,
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
}

$global = $wpdb->get_results(
    "SELECT post_status, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type='product' GROUP BY post_status ORDER BY post_status",
    ARRAY_A
);
echo 'CATALOG_GLOBAL_STATUS ' . wp_json_encode($global, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
echo "FILTER_INSPECTION_DONE\n";
