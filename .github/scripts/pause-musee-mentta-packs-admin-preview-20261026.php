<?php
/** WP-CLI emergency pause: seven Musée Gourmet packs must not reach Mentta until the owner approves. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')) {
 fwrite(STDERR,"WP_CLI_WOO_REQUIRED\n");exit(2);
}
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
if(!function_exists('emdo_musee_review_ids') || !function_exists('emdo_musee_review_preview_allowed') ||
 !function_exists('emdo_mentta_exclusive_ids')) {
 fwrite(STDERR,"REVIEW_GUARDS_MISSING\n");exit(3);
}
$expected=[
 21814=>'pasaporte-salvaje',21816=>'reserva-de-caza',21818=>'safari-africano',
 21820=>'vuelta-al-mundo',21822=>'parrilla-sin-fronteras-xl',21824=>'universo-wagyu',
 21826=>'leyendas-del-vacuno'
];
$adminIds=get_users(['role'=>'administrator','fields'=>'ID','number'=>1]);
if(!$adminIds) {fwrite(STDERR,"ADMIN_NOT_FOUND\n");exit(4);}
if(array_keys($expected)!==emdo_musee_review_ids())exit(5);
$before=[];
foreach($expected as $id=>$key) {
 $product=wc_get_product($id);
 if(!$product || !$product->is_type('simple')
  || (string)get_post_meta($id,'_emdo_mentta_pack_key',true)!==$key
  || (string)get_post_meta($id,'_emdo_mentta_only',true)!=='yes'
  || (int)get_post_field('post_author',$id)!==4949
  || !in_array($id,emdo_mentta_exclusive_ids(),true)
  || !$product->get_image_id()
  || !in_array($product->get_status(),['publish','draft'],true)) {
  fwrite(STDERR,"PREFLIGHT_ID_MISMATCH ".$id." ".$key."\n");exit(6);
 }
 $before[$id]=['status'=>$product->get_status(),'price'=>$product->get_regular_price('edit'),
 'image'=>$product->get_image_id(),'gallery'=>$product->get_gallery_image_ids(),
 'sku'=>$product->get_sku(),'weight'=>$product->get_weight('edit')];
}
update_option('emdo_musee_mentta_review_hold',true,false);
foreach($expected as $id=>$key) {
 $product=wc_get_product($id);
 if($product->get_status()!=='draft') {
  $product->set_status('draft');
  $product->save();
 }
 update_post_meta($id,'_emdo_musee_awaiting_admin_approval','yes');
 wc_delete_product_transients($id);
 clean_post_cache($id);
}
if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
if(function_exists('rocket_clean_domain'))rocket_clean_domain();
wp_set_current_user((int)$adminIds[0]);
$report=[];
foreach($expected as $id=>$key) {
 $product=wc_get_product($id);
 $prior=$before[$id];
 if(!$product || $product->get_status()!=='draft'
 || $product->get_regular_price('edit')!==$prior['price']
 || (int)$product->get_image_id()!==(int)$prior['image']
 || $product->get_gallery_image_ids()!==$prior['gallery']
 || $product->get_sku()!==$prior['sku']
 || $product->get_weight('edit')!==$prior['weight']
 || !in_array($id,emdo_mentta_exclusive_ids(),true)
 || get_post_meta($id,'_emdo_musee_awaiting_admin_approval',true)!=='yes') {
  fwrite(STDERR,"AFTER_PAUSE_VALIDATION_FAILED ".$id."\n");exit(7);
 }
 $_GET=['preview'=>'true','p'=>(string)$id,'post_type'=>'product'];
 if(!emdo_musee_review_preview_allowed()) {
  fwrite(STDERR,"ADMIN_PREVIEW_PERMISSION_FAILED ".$id."\n");exit(8);
 }
 $test=new WP_REST_Request('GET','/mentta_marketplace/products');
 $test->set_param('product_id',$id);
 $result=apply_filters('rest_pre_dispatch',null,rest_get_server(),$test);
 if(!is_wp_error($result) || $result->get_error_code()!=='emdo_musee_review_pending') {
  fwrite(STDERR,"MENTTA_REST_STILL_EXPOSED ".$id."\n");exit(9);
 }
 $report[]=['id'=>$id,'key'=>$key,'status'=>'draft','Mentta'=>'blocked',
  'preview'=>add_query_arg(['post_type'=>'product','p'=>$id,'preview'=>'true'],home_url('/')),
  'editor'=>admin_url('post.php?post='.$id.'&action=edit')];
}
echo 'MUSEE_MENTTA_PAUSED_ADMIN_PREVIEW_READY '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
