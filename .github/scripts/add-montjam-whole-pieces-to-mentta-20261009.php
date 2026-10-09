<?php
/**
 * Add six already repriced Montjam whole hams/shoulders to the Mentta parent and
 * Mentta -> Jamones y paletas child taxonomy, matching Hidalgo's working setup.
 * Leave storefront visibility, prices and original category untouched.
 * Explicitly exclude non-DOP black-label whole ham #14264 and Mentta duplicate #17129.
 */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"ABORT: WooCommerce WP-CLI required\n");exit(2);
}
$ids=array(14271,14275,14287,14294,14301,14305);
$excluded=array(14264,17129);
$root=get_term_by('slug','mentta','product_cat');
$child=get_term_by('slug','mentta-jamones-paletas','product_cat');
$normal=get_term_by('slug','jamones-paletas','product_cat');
$vendor=get_user_by('login','montjam');
if(!$root || !$child || !$normal || !$vendor
    || (int)$child->parent!==(int)$root->term_id) {
 fwrite(STDERR,"ABORT: Mentta category structure/vendor missing\n");exit(3);
}
$before=array();$excluded_before=array();
foreach($excluded as $id) {
 $p=wc_get_product($id);
 if(!$p || !$p->is_type('variable')){fwrite(STDERR,"ABORT: expected protected product #$id\n");exit(4);}
 $excluded_before[$id]=array('cats'=>$p->get_category_ids(),
  'visibility'=>$p->get_catalog_visibility(),'regular'=>$p->get_regular_price('edit'),
  'price'=>$p->get_price('edit'),'variations'=>array());
 foreach($p->get_children() as $vid) {
  $v=wc_get_product($vid);
  if($v)$excluded_before[$id]['variations'][$vid]=(string)$v->get_regular_price('edit');
 }
}
foreach($ids as $id) {
 $p=wc_get_product($id);
 $producer=wp_get_object_terms($id,'pa_productor',array('fields'=>'slugs'));
 if(!$p || !$p->is_type('variable') || $p->get_status()!=='publish'
    || $p->get_catalog_visibility()!=='visible'
    || (int)get_post_field('post_author',$id)!==(int)$vendor->ID
    || is_wp_error($producer) || !in_array('montjam',$producer,true)
    || !in_array((int)$normal->term_id,array_map('intval',$p->get_category_ids()),true)) {
  fwrite(STDERR,"ABORT: unexpected product #$id\n");exit(5);
 }
 $variation_prices=array();
 foreach($p->get_children() as $vid) {
  $v=wc_get_product($vid);
  if($v)$variation_prices[$vid]=(string)$v->get_regular_price('edit');
 }
 if(!$variation_prices) {fwrite(STDERR,"ABORT: missing variations #$id\n");exit(6);}
 $before[$id]=array('categories'=>$p->get_category_ids(),
  'price'=>$p->get_price('edit'),'visibility'=>$p->get_catalog_visibility(),
  'status'=>$p->get_status(),'variations'=>$variation_prices);
}
$modified=array();
try {
 foreach($ids as $id) {
  $p=wc_get_product($id);
  $cats=array_map('intval',$p->get_category_ids());
  $missing=array_values(array_diff(array((int)$root->term_id,(int)$child->term_id),$cats));
  if($missing) {
   $result=wp_set_object_terms($id,$missing,'product_cat',true);
   if(is_wp_error($result))throw new RuntimeException("Failed category assignment #$id");
   $modified[]=$id;
  }
 }
 $report=array();
 foreach($ids as $id) {
  $p=wc_get_product($id);
  $actual=array_map('intval',$p->get_category_ids());
  $expected=array_map('intval',array_unique(array_merge($before[$id]['categories'],array((int)$root->term_id,(int)$child->term_id))));
  sort($actual);sort($expected);
  if($actual!==$expected || $p->get_catalog_visibility()!==$before[$id]['visibility']
    || $p->get_status()!==$before[$id]['status']
    || $p->get_price('edit')!==$before[$id]['price']) {
   throw new RuntimeException("Product verification failed #$id");
  }
  foreach($before[$id]['variations'] as $vid=>$price) {
   $v=wc_get_product($vid);
   if(!$v || $v->get_regular_price('edit')!==$price) throw new RuntimeException("Variation price changed #$vid");
  }
  $report[]=array('id'=>$id,'name'=>$p->get_name(),'category_ids'=>$actual,'price'=>$p->get_price('edit'),
   'visibility'=>$p->get_catalog_visibility());
 }
 foreach($excluded_before as $id=>$item) {
  $p=wc_get_product($id);$actual=$p->get_category_ids();$exp=$item['cats'];sort($actual);sort($exp);
  if($actual!==$exp || $p->get_catalog_visibility()!==$item['visibility']
      || $p->get_regular_price('edit')!==$item['regular']
      || $p->get_price('edit')!==$item['price']) throw new RuntimeException("Excluded product changed #$id");
  foreach($item['variations'] as $vid=>$price) {
   $v=wc_get_product($vid);
   if(!$v || $v->get_regular_price('edit')!==$price) throw new RuntimeException("Excluded weight changed #$vid");
  }
 }
 foreach($ids as $id) {
  wc_delete_product_transients($id);clean_post_cache($id);
  if(function_exists('rocket_clean_post'))rocket_clean_post($id);
 }
 if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
 if(function_exists('rocket_clean_domain'))rocket_clean_domain();
 echo 'MONTJAM_WHOLE_MENTTA_OK '.wp_json_encode(array(
  'products'=>count($report),'changed'=>count($modified),'root_id'=>(int)$root->term_id,
  'child_id'=>(int)$child->term_id,'details'=>$report,'excluded_untouched'=>$excluded
 ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch(Throwable $error) {
 fwrite(STDERR,'MONTJAM_WHOLE_MENTTA_FAILED '.$error->getMessage()."\n");
 foreach($modified as $id) {
  $res=wp_set_object_terms($id,array_map('intval',$before[$id]['categories']),'product_cat',false);
  if(is_wp_error($res))fwrite(STDERR,"ROLLBACK_FAILED #$id ".$res->get_error_message()."\n");
  wc_delete_product_transients($id);
 }
 exit(20);
}
