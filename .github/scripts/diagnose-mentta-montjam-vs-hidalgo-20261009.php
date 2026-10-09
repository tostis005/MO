<?php
// Read-only production audit: Mentta taxonomy memberships, approved product query paths, and product fields.
if (!defined('ABSPATH') || !function_exists('wc_get_product')) {fwrite(STDERR,"WP/Woo not loaded\n"); exit(2);}
global $wpdb;
$parent=get_term_by('slug','mentta','product_cat');
if (!$parent) {fwrite(STDERR,"Mentta root missing\n");exit(3);}
$term_id=(int)$parent->term_id;
$child_ids=get_term_children($term_id,'product_cat');
$term_ids=array_merge(array($term_id),is_wp_error($child_ids)?array():array_map('intval',$child_ids));
$category_data=array();
foreach ($term_ids as $id) {
  $term=get_term($id,'product_cat');
  if (!$term || is_wp_error($term)) continue;
  $direct=$wpdb->get_col($wpdb->prepare(
    "SELECT tr.object_id FROM {$wpdb->term_relationships} tr
     INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
     INNER JOIN {$wpdb->posts} p ON p.ID=tr.object_id
     WHERE tt.taxonomy='product_cat' AND tt.term_id=%d AND p.post_type='product'
     ORDER BY tr.object_id", $id
  ));
  $category_data[]=array('id'=>$id,'slug'=>$term->slug,'name'=>$term->name,'parent'=>$term->parent,'count'=>$term->count,'direct_count'=>count($direct),'direct_ids'=>array_map('intval',$direct));
}
$montjam_ids=array(14630,14632,14634,14636,16908,16910,16912,16914,17129);
$hidalgo_ids=array(1350,1356,1363,1370,1375,1380,1382,1586,1693,1695);
$sample_ids=array_merge($montjam_ids,$hidalgo_ids);
$out=array();
foreach($sample_ids as $id){
  $p=wc_get_product($id);
  if (!$p) continue;
  $cat_ids=$p->get_category_ids();
  $terms=wp_get_object_terms($id,'product_cat',array('fields'=>'all'));
  $pa_prod=wp_get_object_terms($id,'pa_productor',array('fields'=>'slugs'));
  $post=get_post($id);
  $row=array(
   'id'=>$id,'name'=>$p->get_name(),'post_type'=>$post->post_type,'post_status'=>$post->post_status,
   'post_author'=>$post->post_author,'date_gmt'=>$post->post_date_gmt,'modified_gmt'=>$post->post_modified_gmt,
   'type'=>$p->get_type(),'status'=>$p->get_status(),'catalog_visibility'=>$p->get_catalog_visibility(),
   'is_visible'=>$p->is_visible(),'purchasable'=>$p->is_purchasable(),
   'stock_status'=>$p->get_stock_status(),'manage_stock'=>$p->get_manage_stock(),'sku'=>$p->get_sku(),
   'regular_price'=>$p->get_regular_price(),'price'=>$p->get_price(),
   'image_id'=>$p->get_image_id(),'has_description'=>(bool)$p->get_description(),
   'has_short_description'=>(bool)$p->get_short_description(),
   'category_ids'=>$cat_ids,
   'categories'=>is_wp_error($terms)?'ERROR':array_map(static function($term){return array('slug'=>$term->slug,'id'=>$term->term_id,'parent'=>$term->parent);},$terms),
   'producer'=>is_wp_error($pa_prod)?array():$pa_prod,
   'meta_lookup'=>$wpdb->get_row($wpdb->prepare("SELECT sku,stock_status,min_price,max_price FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id=%d",$id),ARRAY_A),
   'link'=>get_permalink($id),
   'wc_visibility_terms'=>wp_get_object_terms($id,'product_visibility',array('fields'=>'slugs'))
  );
  $out[]=$row;
}
$root_wpq=new WP_Query(array('post_type'=>'product','post_status'=>'publish','posts_per_page'=>200,'fields'=>'ids',
 'tax_query'=>array(array('taxonomy'=>'product_cat','terms'=>array($term_id),'field'=>'term_id','include_children'=>false)), 'suppress_filters'=>true));
$full_wpq=new WP_Query(array('post_type'=>'product','post_status'=>'publish','posts_per_page'=>200,'fields'=>'ids',
 'tax_query'=>array(array('taxonomy'=>'product_cat','terms'=>array($term_id),'field'=>'term_id','include_children'=>true)), 'suppress_filters'=>true));
$wcq=new WC_Product_Query(array('category'=>array('mentta'),'status'=>'publish','limit'=>150,'return'=>'ids'));
$plugins=array();
if (!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
foreach ((array)get_option('active_plugins',array()) as $plugin) {
 if(preg_match('/mentta|feed|export|sync|woocommerce|marketplace|wcfm|cache|rocket|merchant|product/i',$plugin)) {$plugins[]=$plugin;}
}
echo 'MENTTA_DIAG '.wp_json_encode(array(
 'category_terms'=>$category_data,'root_only_count'=>$root_wpq->found_posts,
 'root_only_ids'=>array_map('intval',$root_wpq->posts),
 'including_children_count'=>$full_wpq->found_posts,
 'including_children_ids'=>array_map('intval',$full_wpq->posts),
 'wc_category_ids'=>array_map('intval',$wcq),'sample'=>$out,
 'active_related_plugins'=>$plugins,
 'home_url'=>home_url(),'site_url'=>site_url(),
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
