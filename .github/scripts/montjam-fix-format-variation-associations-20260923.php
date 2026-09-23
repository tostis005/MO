<?php
/**
 * Fix YITH WAPO FORMAT block associations for every published Montjam ham/paleta variation.
 * Root cause: newly created weight variations were not added to yith_wapo_blocks_assoc,
 * so YITH removed the FORMAT selector after those weights were selected.
 */
if(!defined('ABSPATH')){fwrite(STDERR,"ABORT: WordPress not loaded\n");exit(2);}
if(!function_exists('wc_get_product')){fwrite(STDERR,"ABORT: WooCommerce unavailable\n");exit(3);}
global $wpdb;

$assoc=$wpdb->prefix.'yith_wapo_blocks_assoc';
$blocks=$wpdb->prefix.'yith_wapo_blocks';
$products=[14264,14271,14275,14287,14294,14301,14305];

$before_missing=[];
$results=[];
$total_published=0;

foreach($products as $pid){
    $product=wc_get_product($pid);
    if(!$product || !$product->is_type('variable')){
        fwrite(STDERR,"ABORT: invalid product {$pid}\n"); exit(4);
    }

    $block_names=['Montjam · Formato · '.$pid,'Mont Jam · Formato · '.$pid];
    $block_id=0;
    foreach($block_names as $name){
        $found=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$blocks` WHERE name=%s LIMIT 1",$name));
        if($found){$block_id=$found;break;}
    }
    if(!$block_id){
        fwrite(STDERR,"ABORT: FORMAT block not found for {$pid}\n"); exit(5);
    }

    $published=[];
    foreach($product->get_children() as $vid){
        $v=wc_get_product((int)$vid);
        if($v && $v->is_type('variation') && $v->get_status()==='publish'){
            $published[]=(int)$vid;
        }
    }
    $total_published+=count($published);

    $existing=$wpdb->get_col($wpdb->prepare(
        "SELECT object FROM `$assoc` WHERE rule_id=%d AND type='product'",
        $block_id
    ));
    $existing=array_map('intval',$existing);

    foreach($published as $vid){
        if(!in_array($vid,$existing,true)){
            $attrs=wc_get_product($vid)->get_attributes();
            $before_missing[]=[
                'product_id'=>$pid,
                'variation_id'=>$vid,
                'size'=>(string)($attrs['pa_tamano']??''),
                'block_id'=>$block_id,
            ];
        }
    }

    // Rebuild product associations deterministically: parent + every current published variation.
    $deleted=$wpdb->delete($assoc,['rule_id'=>$block_id]);
    if($deleted===false){
        fwrite(STDERR,"ABORT: association delete failed for block {$block_id}: {$wpdb->last_error}\n"); exit(6);
    }

    $desired=array_merge([$pid],$published);
    foreach($desired as $object_id){
        $ok=$wpdb->insert($assoc,[
            'rule_id'=>$block_id,
            'object'=>(string)$object_id,
            'type'=>'product',
        ]);
        if(!$ok){
            fwrite(STDERR,"ABORT: association insert failed block {$block_id}/object {$object_id}: {$wpdb->last_error}\n"); exit(7);
        }
    }

    $after=$wpdb->get_col($wpdb->prepare(
        "SELECT object FROM `$assoc` WHERE rule_id=%d AND type='product' ORDER BY CAST(object AS UNSIGNED)",
        $block_id
    ));
    $after=array_map('intval',$after);
    $a=$after; $b=$desired; sort($a); sort($b);
    if($a!==$b){
        fwrite(STDERR,"ABORT: verification mismatch for block {$block_id}\n"); exit(8);
    }

    $results[]=[
        'product_id'=>$pid,
        'title'=>$product->get_name(),
        'block_id'=>$block_id,
        'published_variations'=>count($published),
        'associated_objects'=>count($after),
        'variation_ids'=>$published,
    ];
}

if($total_published!==34){
    fwrite(STDERR,"ABORT: expected 34 published variations, found {$total_published}\n"); exit(9);
}

wp_cache_flush();
if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
if(function_exists('rocket_clean_domain')) rocket_clean_domain();
if(function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');

echo "MONTJAM_FORMAT_ASSOC_FIXED: ".wp_json_encode([
    'missing_before_count'=>count($before_missing),
    'missing_before'=>$before_missing,
    'total_published_variations'=>$total_published,
    'products'=>$results,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
