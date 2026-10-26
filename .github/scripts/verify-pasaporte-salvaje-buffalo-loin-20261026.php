<?php
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) exit(2);
$id=21814;
$p=wc_get_product($id);
if(!$p || $p->get_status()!=='draft' || $p->get_regular_price('edit')!=='151.99'
 || abs((float)$p->get_weight('edit')-1.8)>.001 || !get_option('emdo_musee_mentta_review_hold')
 || !function_exists('emdo_musee_review_ids') || !in_array($id,emdo_musee_review_ids(),true)
 || !function_exists('emdo_mentta_exclusive_ids') || !in_array($id,emdo_mentta_exclusive_ids(),true)) {
 fwrite(STDERR,"ERROR: PACK_NOT_PRIVATE_OR_PRICE_WRONG\n");exit(3);
}
$buf=wc_get_product(17280);
if(!$buf || $buf->get_name()!=='Lomo de Búfalo (Ración de 200 Gr. Apróx.)'
 || !$buf->is_in_stock() || (int)$buf->get_image_id()!==17281) {
 fwrite(STDERR,"ERROR: NOT_THE_ORIGINAL_BUFFALO_LOIN\n");exit(4);
}
$items=get_post_meta($id,'_emdo_mentta_pack_lines',true);
if(!is_array($items) || count($items)!==9)exit(5);
$matches=array_values(array_filter($items,static function($row){
 return isset($row['key'],$row['id'],$row['image'],$row['qty'],$row['label'])
  && $row['key']==='buffalo_loin' && (int)$row['id']===17280
  && (int)$row['image']===17281 && (int)$row['qty']===1
  && str_contains($row['label'],'Lomo de búfalo');
}));
if(count($matches)!==1 || count($p->get_gallery_image_ids())!==9
 || !in_array(17281,$p->get_gallery_image_ids(),true)
 || !get_attached_file($p->get_image_id())
 || !str_contains($p->get_description('edit'),'Lomo de búfalo')
 || str_contains($p->get_description('edit'),'Entrecot de búfalo')
 || get_post_meta($id,'_emdo_mentta_photos_100_percent_real',true)!=='yes') {
 fwrite(STDERR,"ERROR: BAD_ITEMS_OR_IMAGE_OR_DESCRIPTION\n");exit(6);
}
$request=new WP_REST_Request('GET','/mentta_marketplace/products');
$request->set_param('product_id',$id);
$blocked=apply_filters('rest_pre_dispatch',null,rest_get_server(),$request);
if(!is_wp_error($blocked) || $blocked->get_error_code()!=='emdo_musee_review_pending') {
 fwrite(STDERR,"ERROR: MENTTA_CAN_SEE_PACK\n");exit(7);
}
foreach([21816,21818,21820,21822,21824,21826] as $other) {
 $otherP=wc_get_product($other);
 if(!$otherP || $otherP->get_status()!=='draft') {
  fwrite(STDERR,"ERROR: UNRELATED_PACK_CHANGED_STATUS\n");exit(8);
 }
}
echo "PASAPORTE_BUFFALO_LOIN_REAL_PHOTO_VERIFIED ".wp_json_encode([
 'id'=>$id,'buffalo_source_id'=>17280,'buffalo_original_image_id'=>17281,
 'cover_image_id'=>$p->get_image_id(),
 'gallery_real_images'=>count($p->get_gallery_image_ids()),
 'components'=>count($items),'price'=>$p->get_regular_price('edit'),
 'weight_kg'=>$p->get_weight('edit'),'status'=>'draft',
 'mentta'=>'blocked','public'=>'hidden'],JSON_UNESCAPED_UNICODE)."\n";
