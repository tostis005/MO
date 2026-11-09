<?php
/**
 * Production-safe correction, 2026-11-09.
 * Green-brida Montjam shoulder #14305: keep only existing published 6-6.5 kg
 * in visible size selector, choose it as default. Retain its €151.94 regular
 * price, retain older private variations, and retain YITH slicing/boneless addon.
 * Usage: MDO_GREEN_MODE=audit|apply|verify wp eval-file ...
 */
if (!defined('ABSPATH') || !class_exists('WC_Product_Variable') || !defined('WP_CLI') || !WP_CLI) {
  fwrite(STDERR,"ABORT_WP_CLI_REQUIRED\n"); exit(2);
}
$mode=getenv('MDO_GREEN_MODE') ?: 'audit';
if(!in_array($mode,['audit','apply','verify'],true)) { fwrite(STDERR,"ABORT_BAD_MODE\n"); exit(2); }
global $wpdb;
$id=14305; $slug='6-65-kg';
$expected='Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)';
$p=wc_get_product($id);
if(!$p || !$p->is_type('variable') || $p->get_name()!==$expected || $p->get_status()!=='publish') {
  fwrite(STDERR,"ABORT_WRONG_PRODUCT\n"); exit(3);
}
$prod=wp_get_object_terms($id,'pa_productor',['fields'=>'slugs']);
if(is_wp_error($prod) || !in_array('montjam',$prod,true)) { fwrite(STDERR,"ABORT_NOT_MONTJAM\n"); exit(3); }
$term=get_term_by('slug',$slug,'pa_tamano');
if(!$term || is_wp_error($term)) { fwrite(STDERR,"ABORT_WEIGHT_TERM_MISSING\n"); exit(4); }
$target_id=(int)$term->term_id;
$attrs=$p->get_attributes();
if(!isset($attrs['pa_tamano']) || !($attrs['pa_tamano'] instanceof WC_Product_Attribute)) {
  fwrite(STDERR,"ABORT_WEIGHT_ATTRIBUTE_MISSING\n");exit(4);
}
$size_attribute=$attrs['pa_tamano'];
$children=[];
foreach($p->get_children() as $vid) {
  $v=wc_get_product($vid);
  if(!$v || !$v->is_type('variation')) { fwrite(STDERR,"ABORT_BAD_VARIATION\n");exit(5); }
  $w=(string)($v->get_attributes()['pa_tamano']??'');
  if($w==='') { fwrite(STDERR,"ABORT_VARIATION_WITHOUT_WEIGHT\n");exit(5); }
  $children[$w][]=$v;
}
if(!isset($children[$slug]) || count($children[$slug])!==1) { fwrite(STDERR,"ABORT_TARGET_MISSING_OR_DUPLICATE\n");exit(5); }
$valid=$children[$slug][0];
if($valid->get_status()!=='publish' || $valid->get_stock_status()!=='instock'
  || abs((float)$valid->get_regular_price('edit')-151.94)>0.0001
  || $valid->get_sale_price('edit')!=='' || abs((float)$valid->get_price('edit')-151.94)>0.0001) {
    fwrite(STDERR,"ABORT_AVAILABLE_VARIATION_PRICE_OR_STOCK_CHANGED\n");exit(6);
}
foreach($children as $w=>$variations) {
  if($w===$slug) continue;
  foreach($variations as $v) {
    if($v->get_status()==='publish') { fwrite(STDERR,"ABORT_OTHER_WEIGHT_STILL_PUBLISHED\n"); exit(6); }
  }
}
$bt=$wpdb->prefix.'yith_wapo_blocks'; $at=$wpdb->prefix.'yith_wapo_addons';
foreach([$bt,$at] as $t) if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t))!==$t) {
  fwrite(STDERR,"ABORT_YITH_TABLE_MISSING\n");exit(7);
}
$blocks=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$bt` WHERE name=%s",'Montjam · Formato · 14305'),ARRAY_A);
if(count($blocks)!==1 || (int)$blocks[0]['id']!==110 || (int)$blocks[0]['visibility']!==1) {
  fwrite(STDERR,"ABORT_YITH_BLOCK_MISMATCH\n");exit(7);
}
$bsettings=maybe_unserialize($blocks[0]['settings']);
if(($bsettings['rules']['show_in_products']??null)!==['14305']) {
  fwrite(STDERR,"ABORT_YITH_ASSOCIATION_MISMATCH\n");exit(7);
}
$addons=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$at` WHERE block_id=%d",(int)$blocks[0]['id']),ARRAY_A);
if(count($addons)!==1 || (int)$addons[0]['id']!==195 || (int)$addons[0]['visibility']!==1) {
  fwrite(STDERR,"ABORT_YITH_ADDON_MISMATCH\n");exit(7);
}
$aopts=maybe_unserialize($addons[0]['options']);
$asets=maybe_unserialize($addons[0]['settings']);
$labels=['Pieza entera','Loncheado a cuchillo + huesos + taquitos','Deshuesado'];
$prices=['0.00','46.20','17.325'];
if(($aopts['label']??[])!==$labels || ($aopts['price']??[])!==$prices ||
  ($aopts['addon_enabled']??[])!==['yes','yes','yes'] ||
  ($asets['title']??'')!=='FORMATO' || ($asets['type']??'')!=='select') {
    fwrite(STDERR,"ABORT_YITH_OPTIONS_MISMATCH\n");exit(7);
}
$current_terms=wp_get_object_terms($id,'pa_tamano',['fields'=>'ids']);
if(is_wp_error($current_terms)) { fwrite(STDERR,"ABORT_WEIGHT_TERMS\n");exit(8); }
$old_term_ids=array_map('intval',$current_terms);
$old_attribute_ids=array_map('intval',$size_attribute->get_options());
$old_default=$p->get_default_attributes();
$normalized=$old_term_ids===[$target_id] && $old_attribute_ids===[$target_id]
  && ($old_default['pa_tamano']??null)===$slug;
echo 'MONTJAM_GREEN_PLAN '.wp_json_encode([
  'mode'=>$mode,'product_id'=>$id,'weight'=>$slug,'variation_id'=>$valid->get_id(),
  'whole_piece_price'=>$valid->get_regular_price('edit'),'old_default'=>$old_default,
  'old_term_ids'=>$old_term_ids,'old_attribute_ids'=>$old_attribute_ids,
  'current_slicing_extra'=>$prices[1],'boneless_extra'=>$prices[2],
  'already_normalized'=>$normalized
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($mode==='audit') { echo "MONTJAM_GREEN_PREFLIGHT_OK\n";exit(0); }
if($mode==='apply' && !$normalized) {
  try {
    wp_set_object_terms($id,[$target_id],'pa_tamano',false);
    $attrs['pa_tamano']->set_options([$target_id]);
    $attrs['pa_tamano']->set_visible(true);
    $attrs['pa_tamano']->set_variation(true);
    $p->set_attributes($attrs);
    $def=$p->get_default_attributes();
    $def['pa_tamano']=$slug;
    $p->set_default_attributes($def);
    $p->save();
    WC_Product_Variable::sync($id,true);
    wc_delete_product_transients($id);
    clean_post_cache($id);
  } catch(Throwable $e) {
    fwrite(STDERR,'MONTJAM_GREEN_APPLY_ERROR '.$e->getMessage()."\n");
    wp_set_object_terms($id,$old_term_ids,'pa_tamano',false);
    $restore=wc_get_product($id); $ra=$restore->get_attributes();$ra['pa_tamano']->set_options($old_attribute_ids);
    $restore->set_attributes($ra);$restore->set_default_attributes($old_default);$restore->save();
    WC_Product_Variable::sync($id,true);wc_delete_product_transients($id);
    exit(9);
  }
}
if($mode==='apply' || $mode==='verify') {
  $after=wc_get_product($id);
  $after_attrs=$after->get_attributes();
  $after_terms=wp_get_object_terms($id,'pa_tamano',['fields'=>'ids']);
  $after_ids=array_map('intval',$after_attrs['pa_tamano']->get_options());
  $available=$after->get_available_variations('objects');
  if(!is_array($available) || count($available)!==1 ||
    (string)($available[0]->get_attributes()['pa_tamano']??'')!==$slug ||
    $after_ids!==[$target_id] || array_map('intval',$after_terms)!==[$target_id] ||
    ($after->get_default_attributes()['pa_tamano']??'')!==$slug ||
    abs((float)wc_get_product($valid->get_id())->get_regular_price('edit')-151.94)>0.0001) {
    fwrite(STDERR,"MONTJAM_GREEN_VERIFY_FAILED\n");exit(10);
  }
  echo "MONTJAM_GREEN_".strtoupper($mode)."_OK ".wp_json_encode([
    'weight'=>$slug,'available_variations'=>1,'default'=>$slug,
    'whole_piece_price'=>'151.94','slicing_addon_unchanged'=>true,'slicing_extra'=>'46.20',
    'boneless_extra'=>'17.325','old_private_variations_preserved'=>true
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
if($mode==='apply') {
  if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
  if(function_exists('rocket_clean_domain')) rocket_clean_domain();
  if(function_exists('w3tc_flush_all')) w3tc_flush_all();
  do_action('litespeed_purge_all');
  echo "MONTJAM_GREEN_CACHE_PURGE_OK\n";
}
