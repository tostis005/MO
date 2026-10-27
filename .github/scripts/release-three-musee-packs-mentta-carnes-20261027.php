<?php
/**
 * Release only the three owner-approved Musée Gourmet packs to Mentta > Carnes.
 * All seven remain in the separate Mentta-only public exclusion list.
 * Other four remain draft + REST-blocked and are never touched.
 *
 * WP CLI: MDO_MUSEE_RELEASE_MODE=preflight|apply|verify wp eval-file THIS_SCRIPT
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"WORDPRESS_CLI_WOO_REQUIRED\n"); exit(2);
}
$mode=(string)(getenv('MDO_MUSEE_RELEASE_MODE')?:'preflight');
if(!in_array($mode,['preflight','apply','verify'],true))exit(3);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),
 ['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(4);
if(!function_exists('emdo_musee_review_ids') || !function_exists('emdo_musee_approved_mentta_ids')
 || !function_exists('emdo_mentta_exclusive_ids') || !function_exists('emdo_musee_pack_stock_ok')
 || !class_exists('WC_Mentta_Plugin') || !get_option('emdo_musee_mentta_review_hold')) {
 fwrite(STDERR,"GUARDS_OR_EXPORTER_NOT_READY\n");exit(5);
}
$approved=[
 21814=>['key'=>'pasaporte-salvaje','price'=>'153.49','components'=>9,'weight'=>'1.8'],
 21818=>['key'=>'safari-africano','price'=>'154.99','components'=>8,'weight'=>'1.9'],
 21820=>['key'=>'vuelta-al-mundo','price'=>'158.99','components'=>10,'weight'=>'1.95'],
];
$pending=[21816,21822,21824,21826];
if(array_keys($approved)!==emdo_musee_approved_mentta_ids() ||
 $pending!==emdo_musee_review_ids()) {
 fwrite(STDERR,"APPROVED_AND_PENDING_GUARD_IDS_NOT_SEPARATED\n");exit(6);
}
$root=get_term_by('slug','mentta','product_cat');
$child=get_term_by('slug','mentta-carnes','product_cat');
if(!$root || !$child || (int)$child->parent!==(int)$root->term_id)exit(7);
$exclusive=emdo_mentta_exclusive_ids();
foreach(array_merge(array_keys($approved),$pending) as $id)
 if(!in_array($id,$exclusive,true)) {
 fwrite(STDERR,"PUBLIC_EXCLUSION_MISSING $id\n");exit(8);
 }
$before=[];
foreach($approved as $id=>$cfg) {
 $p=wc_get_product($id);
 if(!$p || !$p->is_type('simple') ||
  $p->get_status()!==($mode==='verify'?'publish':'draft') ||
  (string)get_post_meta($id,'_emdo_mentta_pack_key',true)!==$cfg['key'] ||
  (string)get_post_meta($id,'_emdo_mentta_only',true)!=='yes' ||
  (string)get_post_meta($id,'_emdo_musee_awaiting_admin_approval',true)!==($mode==='verify'?'no':'yes') ||
  (int)get_post_field('post_author',$id)!==4949 ||
  abs((float)$p->get_regular_price('edit')-(float)$cfg['price'])>.001 ||
  abs((float)$p->get_weight('edit')-(float)$cfg['weight'])>.01 ||
  !$p->get_image_id() || !is_readable((string)get_attached_file($p->get_image_id())) ||
  count($p->get_gallery_image_ids())!==$cfg['components']) {
   fwrite(STDERR,"APPROVED_PACK_PREFLIGHT_MISMATCH $id\n");exit(9);
 }
 $lines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!is_array($lines)||count($lines)!==$cfg['components'])exit(10);
 foreach($lines as $index=>$line){
  $src=(int)($line['id']??0);$imgId=(int)($line['image']??0);
  $component=$src?wc_get_product($src):false;
  if(!$component || !$component->is_type('simple') || (int)($line['qty']??0)<1 ||
     !$imgId || !is_readable((string)get_attached_file($imgId))) {
    fwrite(STDERR,"COMPONENT_OR_SOURCE_PHOTO_INVALID $id index=$index\n");exit(11);
  }
 }
 $before[$id]=[
  'product'=>$p,'status'=>$p->get_status(),'price'=>$p->get_regular_price('edit'),
  'weight'=>$p->get_weight('edit'),'hero'=>$p->get_image_id(),
  'gallery'=>$p->get_gallery_image_ids(),'description'=>$p->get_description('edit'),
  'short'=>$p->get_short_description('edit'),'sku'=>$p->get_sku(),
  'stock'=>$p->get_stock_status('edit'),'lines'=>$lines,
  'category_ids'=>$p->get_category_ids(),
  'available_from_components'=>emdo_musee_pack_stock_ok($id)
 ];
}
foreach($pending as $id){
 $p=wc_get_product($id);
 if(!$p || $p->get_status()!=='draft' ||
   get_post_meta($id,'_emdo_musee_awaiting_admin_approval',true)!=='yes' ||
   (string)get_post_meta($id,'_emdo_mentta_only',true)!=='yes') {
  fwrite(STDERR,"UNAPPROVED_PACK_LOST_DRAFT_PROTECTION $id\n");exit(12);
 }
}
if($mode==='preflight'){
 echo 'THREE_MENTTA_PACKS_RELEASE_PREFLIGHT '.wp_json_encode([
  'category_root'=>['id'=>(int)$root->term_id,'name'=>$root->name],
  'category_child'=>['id'=>(int)$child->term_id,'name'=>$child->name,'parent'=>$child->parent],
  'three_approved'=>array_map(static function($r) {
    $p=$r['product'];
    return ['id'=>$p->get_id(),'name'=>$p->get_name(),'price'=>$r['price'],
      'photos'=>count($r['gallery']),'cover'=>$r['hero'],
      'all_component_stock_available'=>$r['available_from_components']];
   },array_values($before)),
  'four_remaining_blocked'=>$pending,'own_store_hidden_by_exclusive_ids'=>true
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 exit(0);
}
if($mode==='apply') {
 $done=[];
 try {
  foreach($approved as $id=>$cfg) {
   $old=$before[$id];
   $p=wc_get_product($id);
   // Set only two export-required Woo fields plus Mentta category, leaving
   // approved names, customer-facing copy, premiums covers and piece galleries intact.
   $p->set_category_ids([(int)$root->term_id,(int)$child->term_id]);
   $p->set_catalog_visibility('visible'); // Mentta's IsVisible; public guard still 404
   $p->set_status('publish');
   // Truthful stock: packs dynamically require every real source component to be stocked.
   $p->set_stock_status($old['available_from_components']?'instock':'outofstock');
   $p->set_manage_stock(false);
   $p->update_meta_data('_emdo_musee_awaiting_admin_approval','no');
   $p->update_meta_data('_emdo_musee_approved_for_mentta_20261027','yes');
   $p->save();
   $done[]=$id;
   clean_post_cache($id); wc_delete_product_transients($id);
   if(function_exists('rocket_clean_post'))rocket_clean_post($id);
  }
  if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
  clean_term_cache([(int)$root->term_id,(int)$child->term_id],'product_cat');
 } catch(Throwable $e){
  foreach($done as $id) {
   $p=wc_get_product($id);
   if(!$p)continue;
   $p->set_status('draft');
   $p->set_category_ids($before[$id]['category_ids']);
   $p->update_meta_data('_emdo_musee_awaiting_admin_approval','yes');
   $p->delete_meta_data('_emdo_musee_approved_for_mentta_20261027');
   $p->save();
  }
  fwrite(STDERR,"MENTTA_RELEASE_ROLLED_BACK ".$e->getMessage()."\n");exit(14);
 }
 // For the API check below, also set the actual custom Mentta route request context.
}
// Mimic the authenticated Mentta custom feed serializer, check IsVisible and Categories.
if(!defined('REST_REQUEST')) define('REST_REQUEST',true);
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products';
$verified=[];
foreach($approved as $id=>$cfg) {
 $p=wc_get_product($id);$old=$before[$id];
 $cats=array_map('intval',$p->get_category_ids());
 if(!$p || $p->get_status()!=='publish' ||
  $p->get_catalog_visibility()!=='visible' ||
  !in_array((int)$root->term_id,$cats,true) || !in_array((int)$child->term_id,$cats,true) ||
  !in_array($id,emdo_mentta_exclusive_ids(),true) ||
  $p->get_regular_price('edit')!==$old['price'] ||
  $p->get_weight('edit')!==$old['weight'] ||
  $p->get_image_id()!==$old['hero'] ||
  $p->get_gallery_image_ids()!==$old['gallery'] ||
  $p->get_description('edit')!==$old['description'] ||
  $p->get_short_description('edit')!==$old['short'] ||
  $p->get_sku()!==$old['sku'] ||
  get_post_meta($id,'_emdo_mentta_pack_lines',true)!==$old['lines'] ||
  !get_option('emdo_musee_mentta_review_hold')) {
  fwrite(STDERR,"RELEASED_PACK_VERIFY_FAILED $id\n");exit(15);
 }
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$id);
 $pre=apply_filters('rest_pre_dispatch',null,rest_get_server(),$req);
 if(is_wp_error($pre)){
  fwrite(STDERR,"STILL_BLOCKED_FROM_MENTTA $id ".$pre->get_error_code()."\n");exit(16);
 }
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
 $response=WC_Mentta_Plugin::get_products($req);
 if(!($response instanceof WP_REST_Response)||$response->get_status()!==200) {
  fwrite(STDERR,"MENTTA_SERIALIZER_NOT_SUCCESS $id\n");exit(17);
 }
 $response=apply_filters('rest_post_dispatch',$response,rest_get_server(),$req);
 $payload=$response->get_data();
 $data=is_object($payload)?($payload->data??[]):(is_array($payload)?($payload['data']??[]):[]);
 $entry=is_array($data)?($data[0]??null):null;
 if(!is_array($entry) || (int)($entry['Id']??0)!==$id ||
  ($entry['IsVisible']??null)!==true ||
  !in_array((int)$root->term_id,array_map('intval',$entry['Categories']??[]),true) ||
  !in_array((int)$child->term_id,array_map('intval',$entry['Categories']??[]),true)) {
  fwrite(STDERR,"MENTTA_FEED_INVISIBLE_OR_CATEGORY_WRONG $id ".wp_json_encode($entry,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");exit(18);
 }
 $verified[]=['id'=>$id,'name'=>$p->get_name(),'sku'=>$p->get_sku(),
  'price'=>$p->get_regular_price('edit'),'stock_available_from_components'=>emdo_musee_pack_stock_ok($id),
  'Mentta_IsVisible'=>$entry['IsVisible'],'Mentta_Categories'=>$entry['Categories'],
  'featured_image'=>$p->get_image_id(),'gallery_photos'=>count($p->get_gallery_image_ids()),
  'status'=>'publish','exclusive_public_store'=>true];
}
foreach($pending as $id) {
 $p=wc_get_product($id);
 if($p->get_status()!=='draft') {fwrite(STDERR,"OTHER_PACK_WAS_PUBLISHED $id\n");exit(19);}
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$id);
 $v=apply_filters('rest_pre_dispatch',null,rest_get_server(),$req);
 if(!is_wp_error($v)||$v->get_error_code()!=='emdo_musee_review_pending'){
  fwrite(STDERR,"OTHER_PACK_NOT_BLOCKED $id\n");exit(20);
 }
}
echo 'THREE_MUSEE_PACKS_MENTTA_FEED_VISIBLE_FOUR_STILL_BLOCKED '.wp_json_encode($verified,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
