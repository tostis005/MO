<?php
/**
 * Restore Mentta taxonomy terms for non-Hidalgo products removed unintentionally
 * on 2026-11-18. Input is the pre-change category audit snapshot.
 */
if ( ! defined('ABSPATH') || ! function_exists('wc_get_product') ) {
    fwrite(STDERR, "WooCommerce unavailable\n"); exit(2);
}
global $wpdb;
$file = getenv('MENTTA_RESTORE_FILE');
if (!$file || !is_readable($file)) {
    fwrite(STDERR, "Mentta restore snapshot missing\n"); exit(2);
}
$data = json_decode(file_get_contents($file), true);
$items = $data['items'] ?? null;
if (!is_array($items) || count($items) !== 24) {
    fwrite(STDERR, "Unexpected restore snapshot size\n"); exit(3);
}
$hidalgo_ids = array(1375,1380,1382,1586,1693,1695,4188,5080,5091,5336,5343,5348);
$resolved = array();
foreach ($items as $item) {
    $id = isset($item['id']) && is_numeric($item['id']) ? (int)$item['id'] : 0;
    $title = (string)$item['title'];
    if (!$id) {
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title = %s", $title
        ));
        if (count($ids) !== 1) {
            throw new RuntimeException('Ambiguous or missing product title in restore snapshot: ' . $title . ' (matches: ' . count($ids) . ')');
        }
        $id = (int)$ids[0];
    }
    if (in_array($id, $hidalgo_ids, true) || isset($resolved[$id])) {
        throw new RuntimeException('Unsafe or duplicate product in restore snapshot: ' . $id);
    }
    $product=wc_get_product($id);
    if (!$product || $product->get_name() !== $title) {
        throw new RuntimeException('Product name mismatch during restore: ' . $id . ' / ' . $title);
    }
    $terms=array_values(array_unique(array_map('intval',$item['mentta_terms'])));
    if (!$terms || !in_array(429,$terms,true) && !in_array(431,$terms,true)) {
        // Some legitimate legacy products belonged to the child category only.
        // All terms in the snapshot must be verified individually below.
    }
    foreach ($terms as $term_id) {
        $term = get_term($term_id,'product_cat');
        if (!$term instanceof WP_Term) throw new RuntimeException('Missing category: '.$term_id);
    }
    $current=wp_get_object_terms($id,'product_cat',array('fields'=>'ids'));
    if (is_wp_error($current)) throw new RuntimeException('Unable to read current categories: '.$id);
    $resolved[$id]=array('id'=>$id,'title'=>$title,'terms'=>$terms,'before'=>array_map('intval',$current));
}
echo 'MENTTA_RESTORE_AUDIT '.wp_json_encode(array(
    'products'=>count($resolved),
    'product_ids'=>array_keys($resolved),
    'dry_run'=>false
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$restored=array();
foreach ($resolved as $record) {
    $id=$record['id'];
    $missing=array_values(array_diff($record['terms'],$record['before']));
    if ($missing) {
        $result=wp_set_object_terms($id,$missing,'product_cat',true);
        if (is_wp_error($result)) throw new RuntimeException('Error restoring product '.$id.': '.$result->get_error_message());
    }
    clean_object_term_cache($id,'product');
    $after=wp_get_object_terms($id,'product_cat',array('fields'=>'ids'));
    if (is_wp_error($after)) throw new RuntimeException('Unable to verify restored product '.$id);
    $after=array_map('intval',$after);
    $expected=array_values(array_unique(array_merge($record['before'],$record['terms'])));
    sort($expected); sort($after);
    if ($expected!==$after) throw new RuntimeException('Unexpected category change for product '.$id);
    $restored[]=array('id'=>$id,'title'=>$record['title'],'restored_term_ids'=>$missing);
}
echo 'MENTTA_RESTORED '.wp_json_encode(array(
    'restored_count'=>count($restored),
    'products'=>$restored,
    'other_categories_preserved'=>true,
    'products_deleted'=>0
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
