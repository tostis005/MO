<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$out=[];
$out['roles']=[];
foreach (wp_roles()->roles as $k=>$v) { if (stripos($k,'vendor')!==false || stripos($k,'seller')!==false || stripos($v['name'],'vendor')!==false || stripos($v['name'],'seller')!==false) $out['roles'][$k]=$v['name']; }
$out['wcfm_options']=$wpdb->get_results("SELECT option_name,LEFT(option_value,1000) option_value FROM {$wpdb->options} WHERE option_name LIKE '%wcfm%' OR option_name LIKE '%wcfmmp%' ORDER BY option_name",ARRAY_A);
$out['translation_postmeta_keys']=$wpdb->get_results("SELECT meta_key,COUNT(*) c FROM {$wpdb->postmeta} WHERE meta_key LIKE '_en_US_%' GROUP BY meta_key ORDER BY meta_key",ARRAY_A);
$out['relevant_pages']=$wpdb->get_results("SELECT p.ID,p.post_title,p.post_name,p.post_status,MAX(CASE WHEN pm.meta_key='_en_US_post_title' THEN pm.meta_value END) en_title,MAX(CASE WHEN pm.meta_key='_en_US_post_name' THEN pm.meta_value END) en_slug,MAX(CASE WHEN pm.meta_key='_en_US_published' THEN pm.meta_value END) en_published FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key IN ('_en_US_post_title','_en_US_post_name','_en_US_published') WHERE p.post_type='page' AND p.post_status='publish' GROUP BY p.ID ORDER BY p.ID",ARRAY_A);
$users=get_users(['number'=>1000]);
$out['vendors']=[];
foreach($users as $u){
 $roles=(array)$u->roles;
 if (!array_filter($roles,fn($r)=>stripos($r,'vendor')!==false||stripos($r,'seller')!==false)) continue;
 $row=['id'=>$u->ID,'login'=>$u->user_login,'display'=>$u->display_name,'roles'=>$roles,'products'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$u->ID))];
 foreach(['wcfmmp_profile_settings','wcfm_store_name','wcfmmp_store_name','dokan_profile_settings'] as $mk){$v=get_user_meta($u->ID,$mk,true);if($v)$row[$mk]=$v;}
 if(function_exists('wcfmmp_get_store_url')) $row['store_url']=wcfmmp_get_store_url($u->ID);
 $out['vendors'][]=$row;
}
$out['term_roots']=$wpdb->get_results("SELECT t.term_id,t.name,t.slug,MAX(CASE WHEN tm.meta_key='_en_US_name' THEN tm.meta_value END) en_name,MAX(CASE WHEN tm.meta_key='_en_US_slug' THEN tm.meta_value END) en_slug,MAX(CASE WHEN tm.meta_key='_en_US_published' THEN tm.meta_value END) en_published FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id AND tt.taxonomy='product_cat' LEFT JOIN {$wpdb->termmeta} tm ON tm.term_id=t.term_id AND tm.meta_key IN('_en_US_name','_en_US_slug','_en_US_published') WHERE tt.parent=0 GROUP BY t.term_id ORDER BY t.term_id",ARRAY_A);
$out['sample_products']=$wpdb->get_results("SELECT p.ID,p.post_title,p.post_name,p.post_author,MAX(CASE WHEN pm.meta_key='_en_US_post_title' THEN pm.meta_value END) en_title,MAX(CASE WHEN pm.meta_key='_en_US_post_name' THEN pm.meta_value END) en_slug,MAX(CASE WHEN pm.meta_key='_en_US_published' THEN pm.meta_value END) en_published FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key IN('_en_US_post_title','_en_US_post_name','_en_US_published') WHERE p.post_type='product' AND p.post_status='publish' GROUP BY p.ID ORDER BY p.ID DESC LIMIT 30",ARRAY_A);
$rules=get_option('rewrite_rules',[]);$out['wcfm_rules']=[];foreach($rules as $k=>$v){if(preg_match('/tienda|store|acerca|about|policy|policies|vendor|wcfm/i',$k.' '.$v))$out['wcfm_rules'][$k]=$v;}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
