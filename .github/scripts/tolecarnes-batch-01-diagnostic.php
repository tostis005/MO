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
 }
}
$table=$wpdb->prefix.'icl_translations';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table){echo "==== WPML TABLE PRESENT ====\n";} else {echo "==== WPML TABLE ABSENT ====\n";}
echo "==== ACTIVE TRANSLATION-RELATED PLUGINS ====\n";
$active=(array)get_option('active_plugins',[]);
foreach($active as $plugin){if(preg_match('/translate|translat|wpml|sitepress|polylang|weglot|language|lang|multilingual|lingotek/i',$plugin)){echo $plugin."\n";}}
if(is_multisite()){$network=(array)get_site_option('active_sitewide_plugins',[]);foreach(array_keys($network) as $plugin){if(preg_match('/translate|translat|wpml|sitepress|polylang|weglot|language|lang|multilingual|lingotek/i',$plugin)){echo "NETWORK ".$plugin."\n";}}}
echo "==== TRANSLATION-LIKE DB TABLES ====\n";
$tables=$wpdb->get_col("SHOW TABLES");foreach($tables as $t){if(preg_match('/trp_|translate|translation|weglot|pll_|icl_/i',$t)){echo $t."\n";}}
echo "DIAGNOSTIC_DONE\n";
