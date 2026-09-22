<?php
/**
 * Read-only audit of Montjam ham/paleta weight ranges against supplier table supplied 2026-09-22.
 */
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT: WordPress not loaded\n"); exit(2); }
if (!function_exists('wc_get_product')) { fwrite(STDERR,"ABORT: WooCommerce unavailable\n"); exit(3); }

$expected = [
    14287 => ['key'=>'jamon_negra_dop','expected'=>['6-65-kg','65-7-kg','7-75-kg','75-8-kg','8-85-kg','85-9-kg','9-95-kg']],
    14264 => ['key'=>'jamon_negra','expected'=>['6-65-kg','65-7-kg','7-75-kg','75-8-kg','8-85-kg','85-9-kg','9-95-kg']],
    14271 => ['key'=>'jamon_roja','expected'=>['7-75-kg','75-8-kg','8-85-kg','85-9-kg','9-95-kg']],
    14294 => ['key'=>'jamon_verde','expected'=>['75-8-kg','8-85-kg','85-9-kg','9-95-kg']],
    14301 => ['key'=>'paleta_negra_dop','expected'=>['4-45-kg','45-5-kg','5-55-kg','55-6-kg']],
    14275 => ['key'=>'paleta_negra','expected'=>['4-45-kg','45-5-kg','5-55-kg','55-6-kg']],
    14305 => ['key'=>'paleta_verde','expected'=>['45-5-kg','5-55-kg','55-6-kg']],
];

$out=[];
foreach($expected as $product_id=>$spec){
    $p=wc_get_product($product_id);
    if(!$p || !$p->is_type('variable')){
        $out[]=['id'=>$product_id,'key'=>$spec['key'],'error'=>'missing_or_not_variable'];
        continue;
    }
    $all=[];
    foreach($p->get_children() as $vid){
        $v=wc_get_product($vid);
        if(!$v) continue;
        $attrs=$v->get_attributes();
        $size=(string)($attrs['pa_tamano']??'');
        $all[]=[
            'id'=>(int)$vid,
            'size'=>$size,
            'status'=>$v->get_status(),
            'stock_status'=>$v->get_stock_status(),
            'regular_price'=>$v->get_regular_price('edit'),
            'price'=>$v->get_price('edit'),
            'sku'=>$v->get_sku(),
        ];
    }
    $published=array_values(array_map(fn($r)=>$r['size'],array_filter($all,fn($r)=>$r['status']==='publish')));
    $any=array_values(array_map(fn($r)=>$r['size'],$all));
    $missing_published=array_values(array_diff($spec['expected'],$published));
    $missing_entirely=array_values(array_diff($spec['expected'],$any));
    $existing_not_published=array_values(array_intersect($missing_published,$any));
    $parent_terms=wp_get_object_terms($product_id,'pa_tamano',['fields'=>'slugs']);

    $out[]=[
        'id'=>$product_id,
        'key'=>$spec['key'],
        'title'=>$p->get_name(),
        'expected'=>$spec['expected'],
        'published_sizes'=>$published,
        'all_variations'=>$all,
        'parent_size_terms'=>is_wp_error($parent_terms)?[]:array_values($parent_terms),
        'missing_published'=>$missing_published,
        'missing_entirely'=>$missing_entirely,
        'exists_but_not_published'=>$existing_not_published,
    ];
}

echo "MONTJAM_WEIGHT_AUDIT: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
