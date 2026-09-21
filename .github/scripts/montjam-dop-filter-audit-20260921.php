<?php
if (!defined('ABSPATH')) { fwrite(STDERR, "ABORT: no WP\n"); exit(2); }
if (!function_exists('wc_get_product')) { fwrite(STDERR, "ABORT: no Woo\n"); exit(3); }

$ids = [14287, 14301];
$tax = 'pa_con-dop';

echo "=== TAXONOMY ===\n";
echo 'exists=' . (taxonomy_exists($tax) ? '1' : '0') . "\n";
if (!taxonomy_exists($tax)) exit(4);

$terms = get_terms(['taxonomy'=>$tax,'hide_empty'=>false]);
if (is_wp_error($terms)) { fwrite(STDERR, $terms->get_error_message()."\n"); exit(5); }
echo wp_json_encode(array_map(fn($t)=>[
    'id'=>(int)$t->term_id,'slug'=>$t->slug,'name'=>$t->name,'count'=>(int)$t->count
], $terms), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";

echo "=== PRODUCTS ===\n";
$out = [];
foreach ($ids as $id) {
    $p = wc_get_product($id);
    if (!$p) { fwrite(STDERR, "missing product $id\n"); exit(6); }
    $producer = wp_get_object_terms($id, 'pa_productor', ['fields'=>'slugs']);
    $dop = wp_get_object_terms($id, $tax, ['fields'=>'all']);
    $attrs = $p->get_attributes();
    $attr_dump = [];
    foreach ($attrs as $key=>$attr) {
        if ($attr instanceof WC_Product_Attribute) {
            $attr_dump[$key] = [
                'name'=>$attr->get_name(),
                'options'=>$attr->get_options(),
                'visible'=>$attr->get_visible(),
                'variation'=>$attr->get_variation(),
            ];
        }
    }
    $out[] = [
        'id'=>$id,
        'title'=>$p->get_name(),
        'slug'=>get_post_field('post_name',$id),
        'status'=>get_post_status($id),
        'producer_slugs'=>is_wp_error($producer)?[]:$producer,
        'dop_terms'=>is_wp_error($dop)?[]:array_map(fn($t)=>[
            'id'=>(int)$t->term_id,'slug'=>$t->slug,'name'=>$t->name
        ],$dop),
        'attributes'=>$attr_dump,
        '_product_attributes_raw'=>get_post_meta($id,'_product_attributes',true),
    ];
}
echo wp_json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
echo "AUDIT_OK\n";
