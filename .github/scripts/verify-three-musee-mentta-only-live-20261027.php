<?php
/**
 * Read-only production post-release proof, without changing any product:
 * three approved / IsVisible true in Mentta, four drafts blocked,
 * all seven excluded from public shop and Woo Store API.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
if(!class_exists('WC_Mentta_Plugin')||!function_exists('emdo_mentta_exclusive_ids') ||
 !function_exists('emdo_musee_review_ids')||!function_exists('emdo_musee_approved_mentta_ids') ||
 !get_option('emdo_musee_mentta_review_hold'))exit(3);
$approved=[21814=>'153.49',21818=>'154.99',21820=>'158.99'];$pending=[21816,21822,21824,21826];
if(emdo_musee_approved_mentta_ids()!==array_keys($approved)||emdo_musee_review_ids()!==$pending)exit(4);
$root=get_term_by('slug','mentta','product_cat');$child=get_term_by('slug','mentta-carnes','product_cat');
if(!$root||!$child||(int)$child->parent!==(int)$root->term_id)exit(5);
$exclusive=emdo_mentta_exclusive_ids();
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
$report=[];
foreach($approved as $id=>$expectedPrice){
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'||
    !in_array($id,$exclusive,true)||$p->get_regular_price('edit')!==$expectedPrice||
    get_post_meta($id,'_emdo_musee_awaiting_admin_approval',true)!=='no'||!$p->get_image_id()){
  fwrite(STDERR,"APPROVED_PACK_BAD_STATE $id\n");exit(6);
 }
 // In WooCommerce "Visible en la tienda" must be explicitly VISIBLE, not hidden,
 // catalog-only, or search-only. Woo persists this in the product_visibility taxonomy.
 $display=$p->get_catalog_visibility('edit');
 $allVisTerms=wp_get_object_terms($id,'product_visibility',['fields'=>'slugs']);
 if(is_wp_error($allVisTerms)){fwrite(STDERR,"VISIBILITY_TERMS_UNREADABLE $id\n");exit(14);}
 $exclusions=array_values(array_intersect((array)$allVisTerms,['exclude-from-catalog','exclude-from-search']));
 if($display!=='visible' || $exclusions){
  fwrite(STDERR,'WOO_VISIBILITY_NOT_FULLY_VISIBLE '.wp_json_encode([
   'id'=>$id,'get_catalog_visibility'=>$display,'visibility_terms'=>$allVisTerms,
   'excluded_taxonomy_terms'=>$exclusions],JSON_UNESCAPED_UNICODE)."\n");
  exit(15);
 }
 $cats=array_map('intval',$p->get_category_ids());
 if(!in_array((int)$root->term_id,$cats,true)||!in_array((int)$child->term_id,$cats,true)){
  fwrite(STDERR,"CATEGORIES_MISSING $id\n");exit(7);
 }
 $store=new WP_REST_Request('GET','/wc/store/v1/products/'.$id);
 $blocked=apply_filters('rest_pre_dispatch',null,rest_get_server(),$store);
 if(!is_wp_error($blocked)||$blocked->get_error_code()!=='rest_product_invalid_id'){
  fwrite(STDERR,"PUBLIC_STORE_API_NOT_BLOCKED $id\n");exit(8);
 }
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');$req->set_param('product_id',$id);
 if(is_wp_error(apply_filters('rest_pre_dispatch',null,rest_get_server(),$req))){
  fwrite(STDERR,"MENTTA_REST_PRE_BLOCKED $id\n");exit(9);
 }
 $resp=WC_Mentta_Plugin::get_products($req);
 if(!($resp instanceof WP_REST_Response)||$resp->get_status()!==200)exit(10);
 $resp=apply_filters('rest_post_dispatch',$resp,rest_get_server(),$req);
 $payload=$resp->get_data();$data=is_object($payload)?($payload->data??[]):($payload['data']??[]);
 $e=is_array($data)?($data[0]??[]):[];
 if(!is_array($e)||(int)($e['Id']??0)!==$id||($e['IsVisible']??null)!==true||
    !in_array((int)$child->term_id,array_map('intval',$e['Categories']??[]),true) ||
    !in_array((int)$root->term_id,array_map('intval',$e['Categories']??[]),true)){
  fwrite(STDERR,"MENTTA_EXPORT_INCOMPLETE $id\n");exit(11);
 }
 $report[]=['id'=>$id,'title'=>$p->get_name(),'price'=>$p->get_regular_price('edit'),
  'woocommerce_catalog_visibility'=>$display,
  'woocommerce_visibility_exclusions'=>$exclusions,
  'mentta_visible'=>true,'mentta_categories'=>array_map('intval',$e['Categories']),
  'public_store_api'=>'blocked_404','public_shop_exclusion_option'=>true,
  'gallery_images'=>count($p->get_gallery_image_ids()),'cover_attachment_id'=>$p->get_image_id()];
}
foreach($pending as $id){
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='draft'||!in_array($id,$exclusive,true))exit(12);
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');$req->set_param('product_id',$id);
 $blocked=apply_filters('rest_pre_dispatch',null,rest_get_server(),$req);
 if(!is_wp_error($blocked)||$blocked->get_error_code()!=='emdo_musee_review_pending')exit(13);
}
echo "MENTTA_3_RELEASED_FEED_VISIBLE_4_BLOCKED_AND_PUBLIC_STORE_404 ".wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
