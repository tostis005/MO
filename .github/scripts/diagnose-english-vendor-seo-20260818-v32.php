<?php
if (!defined('ABSPATH')) { exit; }
global $wpdb;
$out = [];
$out['siteurl'] = get_option('siteurl');
$out['active_plugins'] = get_option('active_plugins', []);
$out['tables'] = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
$out['translation_tables'] = array_values(array_filter($out['tables'], static function($t){ return preg_match('/falang|translate|trp|wpml|icl/i',$t); }));
$out['termmeta_keys'] = $wpdb->get_results("SELECT meta_key, COUNT(*) c FROM {$wpdb->termmeta} GROUP BY meta_key ORDER BY c DESC, meta_key", ARRAY_A);
$out['postmeta_translation_keys'] = $wpdb->get_results("SELECT meta_key, COUNT(*) c FROM {$wpdb->postmeta} WHERE meta_key REGEXP 'falang|translate|trp|wpml|language|lang' GROUP BY meta_key ORDER BY c DESC", ARRAY_A);
$out['options_translation'] = $wpdb->get_results("SELECT option_name, LEFT(option_value,300) option_value FROM {$wpdb->options} WHERE option_name REGEXP 'falang|translatepress|trp_|wpml|icl_' ORDER BY option_name LIMIT 100", ARRAY_A);
$out['pages'] = $wpdb->get_results("SELECT ID,post_title,post_name,post_status,post_parent FROM {$wpdb->posts} WHERE post_type='page' AND post_status IN ('publish','draft','private') ORDER BY ID", ARRAY_A);
$out['product_cat'] = $wpdb->get_results("SELECT t.term_id,t.name,t.slug,tt.parent,tt.count FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id AND tt.taxonomy='product_cat' ORDER BY tt.parent,t.term_id", ARRAY_A);
$out['product_cat_meta'] = $wpdb->get_results("SELECT tm.term_id,tm.meta_key,tm.meta_value FROM {$wpdb->termmeta} tm JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=tm.term_id AND tt.taxonomy='product_cat' ORDER BY tm.term_id,tm.meta_key", ARRAY_A);
$users = get_users(['number'=>1000,'fields'=>['ID','user_login','display_name']]);
$vendors=[];
foreach($users as $u){
  $roles = get_userdata($u->ID)->roles;
  $store = get_user_meta($u->ID,'dokan_profile_settings',true);
  $selling = get_user_meta($u->ID,'dokan_enable_selling',true);
  if ($store || $selling || array_intersect($roles,['seller','vendor'])) {
    $row=['id'=>$u->ID,'login'=>$u->user_login,'display'=>$u->display_name,'roles'=>$roles,'selling'=>$selling,'store_settings'=>$store];
    if (function_exists('dokan_get_store_url')) $row['store_url']=dokan_get_store_url($u->ID);
    if (function_exists('dokan_get_store_info')) $row['store_info']=dokan_get_store_info($u->ID);
    $row['product_count']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$u->ID));
    $vendors[]=$row;
  }
}
$out['vendors']=$vendors;
$out['sample_products']=$wpdb->get_results("SELECT ID,post_title,post_name,post_author,post_status FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID DESC LIMIT 20",ARRAY_A);
$rules=get_option('rewrite_rules',[]);
$out['rewrite_rules']=[];
foreach($rules as $k=>$v){ if(preg_match('/store|seller|vendor|product-category|product_cat|producto|categor/i',$k.' '.$v)) $out['rewrite_rules'][$k]=$v; }
if (function_exists('dokan_get_option')) {
 $out['dokan_store_url']=dokan_get_option('custom_store_url','dokan_general','store');
 $out['dokan_store_tabs']=dokan_get_option('store_tabs','dokan_appearance',[]);
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
