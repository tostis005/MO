<?php
if (!defined('ABSPATH')) { fwrite(STDERR, "ABORT: no WP\n"); exit(2); }
if (!function_exists('wc_get_product')) { fwrite(STDERR, "ABORT: no Woo\n"); exit(3); }

$ids = [14287, 14301];
$tax = 'pa_con-dop';

if (!taxonomy_exists($tax)) { fwrite(STDERR, "ABORT: taxonomy missing\n"); exit(4); }
$yes = get_term_by('slug', 'si', $tax);
$no  = get_term_by('slug', 'no', $tax);
if (!$yes || is_wp_error($yes) || 'Sí' !== $yes->name) {
    fwrite(STDERR, "ABORT: expected pa_con-dop term Sí/si not found\n");
    exit(5);
}
if (!$no || is_wp_error($no)) {
    fwrite(STDERR, "ABORT: expected pa_con-dop term No/no not found\n");
    exit(6);
}

$before = [];
foreach ($ids as $id) {
    $p = wc_get_product($id);
    if (!$p || 'publish' !== get_post_status($id)) {
        fwrite(STDERR, "ABORT: product $id unavailable or not published\n");
        exit(7);
    }
    if (stripos($p->get_name(), 'D.O.P. Jabugo Montjam') === false) {
        fwrite(STDERR, "ABORT: product $id title is not expected Montjam DOP product: ".$p->get_name()."\n");
        exit(8);
    }
    $producer = wp_get_object_terms($id, 'pa_productor', ['fields'=>'slugs']);
    if (is_wp_error($producer) || !in_array('montjam', $producer, true)) {
        fwrite(STDERR, "ABORT: product $id is not assigned to Montjam\n");
        exit(9);
    }
    $dop = wp_get_object_terms($id, $tax, ['fields'=>'slugs']);
    if (is_wp_error($dop)) {
        fwrite(STDERR, "ABORT: cannot read DOP for $id\n");
        exit(10);
    }
    $attrs = $p->get_attributes();
    if (!isset($attrs[$tax])) {
        fwrite(STDERR, "ABORT: product $id does not declare pa_con-dop attribute\n");
        exit(11);
    }
    $before[] = [
        'id'=>$id,
        'title'=>$p->get_name(),
        'dop_slugs'=>$dop,
        'attribute_options'=>$attrs[$tax]->get_options(),
    ];
}

echo "=== BEFORE ===\n";
echo wp_json_encode($before, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";

foreach ($ids as $id) {
    $result = wp_set_object_terms($id, [(int)$yes->term_id], $tax, false);
    if (is_wp_error($result)) {
        fwrite(STDERR, "ABORT: failed setting DOP Sí on $id: ".$result->get_error_message()."\n");
        exit(12);
    }
    wc_delete_product_transients($id);
    clean_post_cache($id);
}

clean_term_cache([(int)$yes->term_id, (int)$no->term_id], $tax);
wp_cache_flush();

$after = [];
foreach ($ids as $id) {
    clean_post_cache($id);
    $p = wc_get_product($id);
    $dop = wp_get_object_terms($id, $tax, ['fields'=>'all']);
    $attrs = $p ? $p->get_attributes() : [];
    $slugs = is_wp_error($dop) ? [] : array_map(fn($t)=>$t->slug, $dop);
    $options = isset($attrs[$tax]) ? array_map('intval', $attrs[$tax]->get_options()) : [];
    $row = [
        'id'=>$id,
        'title'=>$p ? $p->get_name() : '',
        'dop_slugs'=>$slugs,
        'dop_names'=>is_wp_error($dop) ? [] : array_map(fn($t)=>$t->name, $dop),
        'attribute_options'=>$options,
    ];
    $after[] = $row;

    if ($slugs !== ['si'] || $options !== [(int)$yes->term_id]) {
        fwrite(STDERR, "ABORT: verification failed for $id\n");
        echo wp_json_encode($row, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
        exit(13);
    }
}

$yes_fresh = get_term((int)$yes->term_id, $tax);
$no_fresh = get_term((int)$no->term_id, $tax);

echo "=== AFTER ===\n";
echo wp_json_encode($after, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
echo 'term_counts=' . wp_json_encode([
    'si'=>!is_wp_error($yes_fresh)?(int)$yes_fresh->count:null,
    'no'=>!is_wp_error($no_fresh)?(int)$no_fresh->count:null,
], JSON_UNESCAPED_UNICODE) . "\n";
echo "MONTJAM_DOP_FILTER_FIX_OK\n";
