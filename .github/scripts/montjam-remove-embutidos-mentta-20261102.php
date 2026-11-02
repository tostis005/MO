<?php
/**
 * Montjam: retirar de Mentta seis productos de embutidos y curados, manteniendo
 * TODOS los jamones y paletas, incluidos los loncheados, y la venta local.
 * Operacion de categorias exclusivamente; idempotente y con preflight/rollback.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
    fwrite(STDERR, "ABORT: WP-CLI y WooCommerce son necesarios\n"); exit(2);
}
$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) {
    fwrite(STDERR, "ABORT: esto solo se ejecuta en produccion\n"); exit(3);
}
$vendor = get_user_by('login', 'montjam');
$producer = get_term_by('slug', 'montjam', 'pa_productor');
$root = get_term_by('slug', 'mentta', 'product_cat');
$normal = get_term_by('slug', 'embutidos-y-curados', 'product_cat');
if (!$vendor || !$producer || !$root || !$normal) {
    fwrite(STDERR, "ABORT: falta productor, vendedor o categoria\n"); exit(4);
}
$descendants = get_term_children((int)$root->term_id, 'product_cat');
if (is_wp_error($descendants)) { fwrite(STDERR, "ABORT: arbol de Mentta no disponible\n"); exit(5); }
$mentta_ids = array_values(array_unique(array_merge([(int)$root->term_id], array_map('intval', $descendants))));
$remove = [
    14630 => 'lomo-bellota-100-iberico-montjam',
    14632 => 'chorizo-iberico-bellota-montjam',
    14634 => 'salchichon-iberico-bellota-montjam',
    14636 => 'morcon-iberico-montjam',
    16912 => 'lomo-bellota-100-iberico-loncheado-montjam-90g',
    16914 => 'chorizo-cular-iberico-extra-loncheado-montjam-90g',
];
// Jamones, paletas, loncheados de jamon/paleta y duplicado de jamon exclusivo.
$keep = [14264, 14271, 14275, 14287, 14294, 14301, 14305, 17129, 16908, 16910];
$expected = array_merge(array_keys($remove), $keep);
sort($expected, SORT_NUMERIC);
$ids = get_posts([
    'post_type' => 'product', 'post_status' => ['publish','private','draft','pending'],
    'posts_per_page' => -1, 'fields' => 'ids', 'suppress_filters' => true,
    'tax_query' => [['taxonomy' => 'pa_productor', 'field' => 'term_id', 'terms' => [(int)$producer->term_id]]],
]);
$ids = array_map('intval', $ids);
sort($ids, SORT_NUMERIC);
// Evitar suprimir por error categorias de nuevos productos Montjam sin revisar.
if ($ids !== $expected) {
    fwrite(STDERR, 'ABORT: inventario Montjam distinto: ' . wp_json_encode(['expected'=>$expected,'actual'=>$ids]) . "\n"); exit(6);
}
$snapshot = [];
$plans = [];
foreach ($ids as $id) {
    $p = wc_get_product($id);
    $producer_slugs = wp_get_object_terms($id, 'pa_productor', ['fields'=>'slugs']);
    $cats = wp_get_object_terms($id, 'product_cat', ['fields'=>'ids']);
    if (!$p || is_wp_error($producer_slugs) || !in_array('montjam',$producer_slugs,true)
        || is_wp_error($cats) || (int)get_post_field('post_author', $id) !== (int)$vendor->ID) {
        fwrite(STDERR, "ABORT: identidad de Montjam no coincide para #$id\n"); exit(7);
    }
    $cats = array_map('intval', $cats);
    $snapshot[$id] = ['categories'=>$cats,'status'=>$p->get_status(),
        'visibility'=>$p->get_catalog_visibility(),'regular'=>(string)$p->get_regular_price('edit'),
        'price'=>(string)$p->get_price('edit'),'stock'=>$p->get_stock_status(),
        'name'=>$p->get_name()];
    if (isset($remove[$id])) {
        if (!$p->is_type('simple') || $p->get_slug() !== $remove[$id] ||
            $p->get_status() !== 'publish' || !in_array((int)$normal->term_id, $cats, true) ||
            in_array($id, $keep, true)) {
            fwrite(STDERR, "ABORT: identidad o categoria de embutido distinta en #$id\n"); exit(8);
        }
        $affected = array_values(array_intersect($cats, $mentta_ids));
        $plans[$id] = ['id'=>$id,'name'=>$p->get_name(),'remove_mentta_term_ids'=>$affected];
    }
}
echo 'MONTJAM_MENTTA_REMOVAL_PREFLIGHT '.wp_json_encode(
    ['target_count'=>count($plans),'targets'=>array_values($plans),'keep_ids'=>$keep],
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
)."\n";
$changed = [];
try {
    foreach ($plans as $id=>$plan) {
        if (!$plan['remove_mentta_term_ids']) { continue; }
        $result = wp_remove_object_terms((int)$id, $plan['remove_mentta_term_ids'], 'product_cat');
        if (is_wp_error($result) || $result !== true) {
            throw new RuntimeException("No se pudieron retirar las categorias Mentta de #$id");
        }
        $changed[] = $id;
    }
    foreach ($snapshot as $id=>$before) {
        $p = wc_get_product($id);
        $actual = wp_get_object_terms($id, 'product_cat', ['fields'=>'ids']);
        if (!$p || is_wp_error($actual)) { throw new RuntimeException("Producto no disponible #$id"); }
        $actual = array_map('intval', $actual);
        $expected_cats = isset($plans[$id])
            ? array_values(array_diff($before['categories'], $mentta_ids))
            : $before['categories'];
        sort($actual, SORT_NUMERIC); sort($expected_cats, SORT_NUMERIC);
        if ($actual !== $expected_cats || $p->get_status() !== $before['status']
            || $p->get_catalog_visibility() !== $before['visibility']
            || (string)$p->get_regular_price('edit') !== $before['regular']
            || (string)$p->get_price('edit') !== $before['price']
            || $p->get_stock_status() !== $before['stock']
            || $p->get_name() !== $before['name']) {
            throw new RuntimeException("Verificacion de categoria/datos fallida en #$id");
        }
        if (isset($plans[$id]) && in_array((int)$normal->term_id, $actual, true) === false) {
            throw new RuntimeException("Categoria de venta local perdida en #$id");
        }
    }
    foreach ($changed as $id) {
        wc_delete_product_transients($id);
        clean_post_cache($id);
        if (function_exists('rocket_clean_post')) { rocket_clean_post($id); }
    }
    if (class_exists('WC_Cache_Helper')) { WC_Cache_Helper::get_transient_version('product', true); }
    if ($changed && function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
    echo 'MONTJAM_MENTTA_REMOVAL_OK '.wp_json_encode(
        ['products_out_of_mentta'=>count($plans),'changed'=>count($changed),
         'removed_ids'=>$changed,'already_out_of_mentta'=>array_values(array_diff(array_keys($plans),$changed)),
         'ham_shoulder_products_untouched'=>$keep,'local_catalog_unchanged'=>true],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
    )."\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'MONTJAM_MENTTA_REMOVAL_FAILED '.$e->getMessage()."\n");
    foreach ($changed as $id) {
        $r = wp_set_object_terms($id, $snapshot[$id]['categories'], 'product_cat', false);
        if (is_wp_error($r)) { fwrite(STDERR,"ROLLBACK_FAILED #$id: ".$r->get_error_message()."\n"); }
        wc_delete_product_transients($id);
    }
    exit(20);
}
