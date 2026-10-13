<?php
/**
 * 2026-10-13: Montjam jamon de bellota 100% iberico, black seal without DOP.
 *
 * Apply the already-approved 25%-on-cost prices ONLY to the five heavier
 * published variations on original WooCommerce product #14264.
 * Keep the two light weights and Mentta-only copy #17129 at their current prices.
 *
 * Same pricing formula as the other Montjam products: old * 125 / 110.
 * Script checks current prices and identities, is idempotent, and rolls back
 * updated weights if post-change verification fails.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI
    || !class_exists('WC_Product_Variable') || !function_exists('wc_get_product')) {
    fwrite(STDERR, "ABORT: WooCommerce and WP-CLI required\n");
    exit(2);
}
$source_id = 14264;
$copy_id = 17129;
$expected = array(
    '6-65-kg' => array('id'=>14280,'old'=>'279.297','new'=>'279.297','change'=>false),
    '65-7-kg' => array('id'=>14281,'old'=>'301.641','new'=>'301.641','change'=>false),
    '7-75-kg' => array('id'=>14282,'old'=>'315.810','new'=>'358.875','change'=>true),
    '75-8-kg' => array('id'=>14283,'old'=>'337.590','new'=>'383.625','change'=>true),
    '8-85-kg' => array('id'=>14643,'old'=>'359.370','new'=>'408.375','change'=>true),
    '85-9-kg' => array('id'=>14644,'old'=>'381.150','new'=>'433.125','change'=>true),
    '9-95-kg' => array('id'=>14645,'old'=>'402.930','new'=>'457.875','change'=>true),
);
function mj_black25_snapshot($v) {
    return array(
        'regular'=>(string)$v->get_regular_price('edit'),
        'price'=>(string)$v->get_price('edit'),
        'sale'=>(string)$v->get_sale_price('edit'),
        'attributes'=>$v->get_attributes(),
        'status'=>$v->get_status(),
        'sku'=>$v->get_sku(),
        'manage_stock'=>$v->get_manage_stock(),
        'stock_status'=>$v->get_stock_status(),
        'stock_quantity'=>$v->get_stock_quantity(),
        'image_id'=>$v->get_image_id(),
        'description'=>$v->get_description(),
    );
}
function mj_black25_equal($a, $b) { return abs((float)$a-(float)$b)<0.000001; }
$source=wc_get_product($source_id);
$copy=wc_get_product($copy_id);
$vendor=get_user_by('login','montjam');
$producer=wp_get_object_terms($source_id,'pa_productor',array('fields'=>'slugs'));
if(!$source || !$source->is_type('variable') || $source->get_status()!=='publish'
    || $source->get_slug()!=='jamon-de-bellota-100-iberico-montjam'
    || $source->get_name()!=='Jamón de bellota 100% ibérico Montjam (brida negra)'
    || !($vendor instanceof WP_User)
    || (int)get_post_field('post_author',$source_id)!==(int)$vendor->ID
    || is_wp_error($producer) || !in_array('montjam',$producer,true)
    || !$copy || !$copy->is_type('variable')
    || $copy->get_slug()!=='jamon-de-bellota-100-iberico-montjam-mentta'
    || (int)get_post_meta($copy_id,'_emdo_mentta_source_id',true)!==$source_id) {
    fwrite(STDERR,"ABORT: source or Mentta-only copy identity mismatch\n");exit(3);
}
$copy_children=$copy->get_children();
$copy_expected=array(17130=>'279.297',17131=>'301.641');
$copy_snapshot=array();
foreach($copy_children as $vid) {
    $variation=wc_get_product($vid);
    if(!isset($copy_expected[$vid]) || !$variation || !$variation->is_type('variation')) {
        fwrite(STDERR,"ABORT: unknown Mentta-only copy variation\n");exit(4);
    }
    $snapshot=mj_black25_snapshot($variation);
    if(!mj_black25_equal($snapshot['regular'],$copy_expected[$vid])) {
        fwrite(STDERR,"ABORT: Mentta-only copy baseline unexpectedly changed\n");exit(5);
    }
    $copy_snapshot[$vid]=$snapshot;
}
if(count($copy_snapshot)!==2) {
    fwrite(STDERR,"ABORT: Mentta-only copy does not have exactly two variations\n");exit(6);
}
$copy_meta=array('cats'=>$copy->get_category_ids(),'visible'=>$copy->get_catalog_visibility(),
    'status'=>$copy->get_status(),'price'=>$copy->get_price('edit'));
$parent_meta=array('cats'=>$source->get_category_ids(),'visible'=>$source->get_catalog_visibility(),
    'status'=>$source->get_status(),'name'=>$source->get_name(),'slug'=>$source->get_slug(),
    'sku'=>$source->get_sku(),'image'=>$source->get_image_id(),
    'variation_ids'=>$source->get_children());
$found=array();
$plan=array();
foreach($source->get_children() as $vid) {
    $v=wc_get_product($vid);
    if(!$v || !$v->is_type('variation') || $v->get_status()!=='publish') {
        fwrite(STDERR,"ABORT: unexpected source variation identity/status #$vid\n");exit(7);
    }
    $weight=(string)($v->get_attributes()['pa_tamano']??'');
    if(!isset($expected[$weight]) || (int)$expected[$weight]['id']!==(int)$vid || isset($found[$weight])) {
        fwrite(STDERR,"ABORT: unexpected source variation weight/ID #$vid\n");exit(8);
    }
    $entry=$expected[$weight];
    if($entry['change']
        && !mj_black25_equal($entry['new'], (float)$entry['old']*125/110)) {
        fwrite(STDERR,"ABORT: target price formula mismatch for $weight\n");exit(9);
    }
    $snap=mj_black25_snapshot($v);
    if($snap['sale']!==''
        || !mj_black25_equal($snap['price'],$snap['regular'])
        || (!mj_black25_equal($snap['regular'],$entry['old'])
            && !($entry['change'] && mj_black25_equal($snap['regular'],$entry['new'])))) {
        fwrite(STDERR,"ABORT: unexpected live regular/current price for #$vid\n");exit(10);
    }
    $found[$weight]=(int)$vid;
    $plan[]=array('id'=>$vid,'weight'=>$weight,'old'=>$entry['old'],
        'new'=>$entry['new'],'change'=>$entry['change'],'snapshot'=>$snap);
}
$actual=array_keys($found);$needed=array_keys($expected);sort($actual);sort($needed);
if($needed!==$actual || count($plan)!==7) {
    fwrite(STDERR,"ABORT: missing published source variation\n");exit(11);
}
echo 'MONTJAM_BLACK_25_PREFLIGHT '.wp_json_encode(array(
    'source_id'=>$source_id,'protected_copy_id'=>$copy_id,
    'variations'=>array_map(static function($row){return array(
        'id'=>$row['id'],'weight'=>$row['weight'],
        'current'=>$row['snapshot']['regular'],'target'=>$row['new'],
        'will_change'=>$row['change'] && !mj_black25_equal($row['snapshot']['regular'],$row['new'])
    );},$plan)
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";

$modified=array();
try {
    foreach($plan as $row) {
        if(!$row['change'] || mj_black25_equal($row['snapshot']['regular'],$row['new']))continue;
        $v=wc_get_product($row['id']);
        $v->set_regular_price($row['new']);
        $v->save();
        $modified[]=$row;
    }
    WC_Product_Variable::sync($source_id,true);
    wc_delete_product_transients($source_id);
    clean_post_cache($source_id);
    if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
    $verified=array();
    foreach($plan as $row) {
        $v=wc_get_product($row['id']);
        if(!$v)throw new RuntimeException("Missing product after update #{$row['id']}");
        $after=mj_black25_snapshot($v);
        $old=$row['snapshot'];
        unset($after['regular'],$after['price']);
        unset($old['regular'],$old['price']);
        if($after!==$old
            || !mj_black25_equal($v->get_regular_price('edit'),$row['new'])
            || !mj_black25_equal($v->get_price('edit'),$row['new'])) {
            throw new RuntimeException("Unexpected variation state #{$row['id']}");
        }
        $verified[]=array('id'=>$row['id'],'weight'=>$row['weight'],
            'before'=>$row['snapshot']['regular'],'after'=>$v->get_regular_price('edit'));
    }
    $fresh=wc_get_product($source_id);
    $check=array('cats'=>$fresh->get_category_ids(),'visible'=>$fresh->get_catalog_visibility(),
      'status'=>$fresh->get_status(),'name'=>$fresh->get_name(),'slug'=>$fresh->get_slug(),
      'sku'=>$fresh->get_sku(),'image'=>$fresh->get_image_id(),
      'variation_ids'=>$fresh->get_children());
    if($check!==$parent_meta)throw new RuntimeException('Source parent metadata changed');
    $copy_after=wc_get_product($copy_id);
    $after_meta=array('cats'=>$copy_after->get_category_ids(),'visible'=>$copy_after->get_catalog_visibility(),
      'status'=>$copy_after->get_status(),'price'=>$copy_after->get_price('edit'));
    if($after_meta!==$copy_meta || $copy_after->get_children()!==$copy_children) {
        throw new RuntimeException('Mentta-only copy metadata changed');
    }
    foreach($copy_snapshot as $vid=>$before) {
        $v=wc_get_product($vid);
        if(!$v || mj_black25_snapshot($v)!==$before) {
            throw new RuntimeException("Mentta-only copy variation changed #$vid");
        }
    }
    if(function_exists('rocket_clean_post'))rocket_clean_post($source_id);
    if(function_exists('rocket_clean_domain'))rocket_clean_domain();
    echo 'MONTJAM_BLACK_25_SUCCESS '.wp_json_encode(array(
        'source_id'=>$source_id,'revised_weight_count'=>count($modified),
        'verified_weights'=>$verified,'light_weights_unchanged'=>2,
        'mentta_only_copy_unchanged'=>true,'other_products_untouched'=>true
    ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch(Throwable $e) {
    fwrite(STDERR,"MONTJAM_BLACK_25_FAILED ".$e->getMessage()."\n");
    foreach($modified as $row) {
        try {
            $v=wc_get_product($row['id']);
            if(!$v)continue;
            $v->set_regular_price($row['snapshot']['regular']);
            $v->save();
        } catch(Throwable $rollback_error) {
            fwrite(STDERR,"ROLLBACK_FAILED variation #{$row['id']}: ".$rollback_error->getMessage()."\n");
        }
    }
    WC_Product_Variable::sync($source_id,true);
    wc_delete_product_transients($source_id);
    exit(20);
}
