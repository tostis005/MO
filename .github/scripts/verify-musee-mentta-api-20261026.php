<?php
/** Verify only Mentta's real custom product serializer; no store modifications. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WC_Mentta_Plugin')) {
 fwrite(STDERR,"MENTTA_API_PLUGIN_MISSING\n");exit(2);
}
$all=get_posts(['post_type'=>'product','post_status'=>'publish','author'=>4949,'posts_per_page'=>-1,
 'meta_key'=>'_emdo_mentta_only','meta_value'=>'yes','fields'=>'ids','suppress_filters'=>true]);
$found=[];
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
foreach($all as $id) {
 if(!get_post_meta($id,'_emdo_mentta_pack_key',true))continue;
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$id);
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
 $response=WC_Mentta_Plugin::get_products($req);
 if(!($response instanceof WP_REST_Response) || $response->get_status()!==200) {
  fwrite(STDERR,"MENTTA_RESPONSE_BAD ".$id."\n");exit(3);
 }
 $payload=$response->get_data();
 $data=is_object($payload)?($payload->data??[]):(is_array($payload)?($payload['data']??[]):[]);
 $product=is_array($data)?($data[0]??[]):[];
 if(!is_array($product) || (int)($product['Id']??0)!==(int)$id ||
    ($product['IsVisible']??null)!==true) {
  fwrite(STDERR,"MENTTA_PRODUCT_NOT_VISIBLE ".$id." ".$_SERVER['REQUEST_URI']."\n");exit(4);
 }
 $cats=array_map('intval',(array)($product['Categories']??[]));
 $root=get_term_by('slug','mentta','product_cat');
 $child=get_term_by('slug','mentta-carnes','product_cat');
 if(!$root||!$child||!in_array((int)$root->term_id,$cats,true) ||
    !in_array((int)$child->term_id,$cats,true)) {
  fwrite(STDERR,"MENTTA_EXPORT_CATEGORY_WRONG ".$id."\n");exit(5);
 }
 $sourceLines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 $p=wc_get_product($id);
 if(!is_array($sourceLines) || !$sourceLines || !$p || !$p->get_image_id())exit(6);
 $found[]=['id'=>$id,'name'=>$p->get_name(),'price'=>$p->get_price('edit'),
  'component_lines'=>count($sourceLines),'category_ids'=>$cats,'IsVisible'=>true];
}
if(count($found)!==7){fwrite(STDERR,"MENTTA_EXPECTED_7_FOUND_".count($found)."\n");exit(7);}
echo "MENTTA_SEVEN_PRODUCTS_EXPORT_VERIFIED ".wp_json_encode($found,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
