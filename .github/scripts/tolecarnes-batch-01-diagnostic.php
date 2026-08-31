<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
$queries=['carne_picada'=>['Carne picada'],'burger_mixta'=>['Burger','gluten'],'filetes_primera'=>['Filetes','primera'],'ragu'=>['Rag'],'entrana'=>['Entraña']];
function mo_lang_diag($id){$d=apply_filters('wpml_post_language_details',null,(int)$id);return is_array($d)&&!empty($d['language_code'])?$d['language_code']:'';}
foreach($queries as $key=>$terms){
 echo "==== {$key} ====\n";
 $where=["post_type='product'","post_status NOT IN ('trash','auto-draft')"];$params=[];
 foreach($terms as $t){$where[]='post_title LIKE %s';$params[]='%'.$wpdb->esc_like($t).'%';}
 $sql="SELECT ID,post_title,post_name,post_status,post_author FROM {$wpdb->posts} WHERE ".implode(' AND ',$where)." ORDER BY ID";
 $rows=$wpdb->get_results($wpdb->prepare($sql,...$params));
 foreach($rows as $p){
  $lang=mo_lang_diag($p->ID);$sku=get_post_meta($p->ID,'_sku',true);$price=get_post_meta($p->ID,'_price',true);$stock=get_post_meta($p->ID,'_stock_status',true);
  $tt=wp_get_post_terms($p->ID,'product_type',['fields'=>'names']);$type=is_wp_error($tt)?'':implode(',',$tt);$u=get_userdata((int)$p->post_author);$author=$u?$u->display_name:'';
  $en=(int)apply_filters('wpml_object_id',(int)$p->ID,'product',false,'en');$es=(int)apply_filters('wpml_object_id',(int)$p->ID,'product',false,'es');
  echo "ID={$p->ID} lang={$lang} status={$p->post_status} title={$p->post_title} slug={$p->post_name} author={$author} sku={$sku} type={$type} price={$price} stock={$stock} es={$es} en={$en}\n";
  if($en&&$en!==(int)$p->ID){$ep=get_post($en);echo "  EN_LINK ID={$en} title=".($ep?$ep->post_title:'?')." slug=".($ep?$ep->post_name:'?')." status=".($ep?$ep->post_status:'?')."\n";}
 }
}
$table=$wpdb->prefix.'icl_translations';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table){
 echo "==== WPML rows ====\n";$ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN ('trash','auto-draft') AND (post_title LIKE '%Carne picada%' OR post_title LIKE '%Burger%' OR post_title LIKE '%Filetes%' OR post_title LIKE '%Rag%' OR post_title LIKE '%Entraña%')");
 if($ids){$ph=implode(',',array_map('intval',$ids));$trs=$wpdb->get_results("SELECT element_id,trid,language_code,source_language_code FROM {$table} WHERE element_type='post_product' AND element_id IN ({$ph}) ORDER BY trid,language_code");foreach($trs as $tr){echo "element={$tr->element_id} trid={$tr->trid} lang={$tr->language_code} source={$tr->source_language_code}\n";}}
}
echo "DIAGNOSTIC_DONE\n";
