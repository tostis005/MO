<?php
/**
 * Montjam 2026-11-09: exact user-supplied prices for five original whole-piece products.
 * Only published size-variation regular prices are changed. The DOP products,
 * Mentta-only copies, format add-ons/loncheado, and other merchandise are excluded.
 * Run with MDO_MONTJAM_PRICE_MODE=audit|apply|verify through WP-CLI.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WC_Product_Variable')) {
    fwrite(STDERR, "ABORT: WooCommerce via WP-CLI required\n"); exit(2);
}
$mode = getenv('MDO_MONTJAM_PRICE_MODE') ?: 'audit';
if (!in_array($mode, ['audit','apply','verify'], true)) {
    fwrite(STDERR, "ABORT: unrecognized mode\n"); exit(2);
}
$targets = [
  14264 => [
    'name'=>'Jamón de bellota 100% ibérico Montjam (brida negra)',
    'weights'=>[
      '6-65-kg'=>'279.30','65-7-kg'=>'301.64','7-75-kg'=>'358.88',
      '75-8-kg'=>'383.63','8-85-kg'=>'408.38','85-9-kg'=>'433.13','9-95-kg'=>'457.88'
    ],
  ],
  14271 => [
    'name'=>'Jamón de bellota ibérico 50% raza ibérica Montjam (brida roja)',
    'weights'=>[
      '7-75-kg'=>'328.97','75-8-kg'=>'351.66','8-85-kg'=>'374.34',
      '85-9-kg'=>'397.03','9-95-kg'=>'419.72'
    ],
  ],
  14294 => [
    'name'=>'Jamón de cebo de campo ibérico 50% raza ibérica Montjam (brida verde)',
    'weights'=>[
      '75-8-kg'=>'255.75','8-85-kg'=>'272.25','85-9-kg'=>'288.75','9-95-kg'=>'305.25'
    ],
  ],
  14275 => [
    'name'=>'Paleta de bellota 100% ibérica Montjam (brida negra)',
    'weights'=>[
      '4-45-kg'=>'140.25','45-5-kg'=>'156.75','5-55-kg'=>'173.25','55-6-kg'=>'189.75'
    ],
  ],
  14305 => [
    'name'=>'Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)',
    'weights'=>['5-55-kg'=>'122.72','55-6-kg'=>'134.41'],
  ],
];
$vendor=get_user_by('login','montjam');
if (!$vendor) { fwrite(STDERR, "ABORT: vendor not found\n"); exit(3); }
function mj_exact_other_fields($v) {
    return [
      'parent'=>(int)$v->get_parent_id(),'status'=>$v->get_status(),
      'sku'=>$v->get_sku(),'attrs'=>$v->get_attributes(),
      'stock_status'=>$v->get_stock_status(),'manage_stock'=>$v->get_manage_stock(),
      'stock_qty'=>$v->get_stock_quantity(),'image_id'=>$v->get_image_id(),
      'description'=>$v->get_description(),'weight'=>$v->get_weight(),
      'sale'=>$v->get_sale_price('edit')
    ];
}
function mj_exact_equal($a,$b) { return abs((float)$a-(float)$b)<0.000001; }
$rows=[]; $parents=[];
foreach ($targets as $pid=>$spec) {
    $p=wc_get_product($pid);
    $tax=wp_get_object_terms($pid,'pa_productor',['fields'=>'slugs']);
    if (!$p || !$p->is_type('variable') || $p->get_status()!=='publish' ||
        $p->get_name()!==$spec['name'] ||
        (int)get_post_field('post_author',$pid)!==(int)$vendor->ID ||
        is_wp_error($tax) || !in_array('montjam',$tax,true)) {
        fwrite(STDERR, "ABORT: wrong product identity #$pid\n"); exit(4);
    }
    $parents[$pid]=[
       'name'=>$p->get_name(),'slug'=>$p->get_slug(),
       'status'=>$p->get_status(),'categories'=>$p->get_category_ids(),
       'visibility'=>$p->get_catalog_visibility(),'sku'=>$p->get_sku(),
       'image'=>$p->get_image_id(),'children'=>$p->get_children()
    ];
    $seen=[];
    foreach ($p->get_children() as $vid) {
        $v=wc_get_product($vid);
        if (!$v || !$v->is_type('variation') || (int)$v->get_parent_id()!==$pid) {
            fwrite(STDERR, "ABORT: invalid variation #$vid\n"); exit(5);
        }
        if ($v->get_status()!=='publish') continue;
        $weight=(string)($v->get_attributes()['pa_tamano']??'');
        if (!array_key_exists($weight,$spec['weights']) || isset($seen[$weight])) {
            fwrite(STDERR, "ABORT: unexpected published weight $weight for #$pid\n"); exit(6);
        }
        if ($v->get_sale_price('edit')!=='' ||
            !is_numeric($v->get_regular_price('edit')) ||
            !mj_exact_equal($v->get_price('edit'),$v->get_regular_price('edit'))) {
            fwrite(STDERR, "ABORT: active/sale-price conflict #$vid\n"); exit(7);
        }
        $seen[$weight]=true;
        $rows[]=[
          'product_id'=>$pid,'title'=>$spec['name'],'variation_id'=>(int)$vid,
          'weight'=>$weight,'old'=>(string)$v->get_regular_price('edit'),
          'target'=>$spec['weights'][$weight],
          'other'=>mj_exact_other_fields($v)
        ];
    }
    $actual=array_keys($seen);$wanted=array_keys($spec['weights']);sort($actual);sort($wanted);
    if ($actual!==$wanted) {
        fwrite(STDERR, "ABORT: missing/unexpected published sizes #$pid\n"); exit(8);
    }
}
if(count($rows)!==22 || count($parents)!==5) {
    fwrite(STDERR, "ABORT: expected 22 sizes across five products\n"); exit(9);
}
$out=array_map(static function($r) {
    return [
      'product_id'=>$r['product_id'],'title'=>$r['title'],'variation_id'=>$r['variation_id'],
      'weight'=>$r['weight'],'before'=>$r['old'],'target'=>$r['target'],
      'display_before'=>number_format((float)$r['old'],2,'.',''),
      'display_diff'=>number_format((float)$r['target']-(float)number_format((float)$r['old'],2,'.',''),2,'.',''),
      'needs_exact_write'=>!mj_exact_equal($r['old'],$r['target'])
    ];
},$rows);
echo 'MONTJAM_EXACT_PLAN '.wp_json_encode(['mode'=>$mode,'count'=>count($rows),'rows'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if ($mode==='audit') {
    echo "MONTJAM_EXACT_AUDIT_OK\n"; exit(0);
}
if ($mode==='verify') {
    foreach ($rows as $r) {
        if (!mj_exact_equal($r['old'],$r['target'])) {
            fwrite(STDERR, "VERIFY_FAILED #".$r['variation_id']."\n"); exit(10);
        }
    }
    echo "MONTJAM_EXACT_VERIFY_OK\n"; exit(0);
}
$modified=[];
try {
    foreach ($rows as $r) {
        if (mj_exact_equal($r['old'],$r['target'])) continue;
        $v=wc_get_product($r['variation_id']);
        $v->set_regular_price($r['target']);
        $v->save();
        $modified[]=$r;
    }
    foreach(array_keys($parents) as $pid) {
        WC_Product_Variable::sync($pid,true);
        wc_delete_product_transients($pid);
        clean_post_cache($pid);
    }
    foreach ($rows as $r) {
        $v=wc_get_product($r['variation_id']);
        if (!$v || !mj_exact_equal($v->get_regular_price('edit'),$r['target']) ||
            !mj_exact_equal($v->get_price('edit'),$r['target']) ||
            mj_exact_other_fields($v)!==$r['other']) {
            throw new RuntimeException("Variation verification failed #".$r['variation_id']);
        }
    }
    foreach ($parents as $pid=>$old) {
        $p=wc_get_product($pid);
        $now=[
           'name'=>$p->get_name(),'slug'=>$p->get_slug(),'status'=>$p->get_status(),
           'categories'=>$p->get_category_ids(),'visibility'=>$p->get_catalog_visibility(),
           'sku'=>$p->get_sku(),'image'=>$p->get_image_id(),'children'=>$p->get_children()
        ];
        if ($now!==$old) throw new RuntimeException("Parent metadata changed #$pid");
    }
    if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
    if(function_exists('rocket_clean_domain')) rocket_clean_domain();
    echo 'MONTJAM_EXACT_APPLY_OK '.wp_json_encode([
       'variations_total'=>count($rows),'exact_value_writes'=>count($modified),
       'unrelated_fields_verified'=>true,'add_on_prices_not_touched'=>true,
       'DOP_and_Mentta_products_not_touched'=>true
    ],JSON_UNESCAPED_SLASHES)."\n";
} catch(Throwable $e) {
    fwrite(STDERR, "MONTJAM_EXACT_APPLY_ERROR ".$e->getMessage()."\n");
    foreach($modified as $r) {
        try {
            $v=wc_get_product($r['variation_id']);
            if($v) { $v->set_regular_price($r['old']); $v->save(); }
        } catch(Throwable $rollback_error) {
            fwrite(STDERR, "ROLLBACK_FAILED #".$r['variation_id']."\n");
        }
    }
    foreach(array_keys($parents) as $pid) {
        WC_Product_Variable::sync($pid,true); wc_delete_product_transients($pid);
    }
    exit(20);
}
