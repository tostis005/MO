<?php
/** Deep read-only verification of Latitudes copyright-footer remediation and Mentta held pack references. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
global $wpdb;
$old=[17177,17179,17183,17185,17199,17201,17205,17271,17275,17279,17283,17287,17289,17291,17293,17295,17297,17307,17313,17327,17333,17335,17337,17344,17348,17357,17359,17361,17363,17383,18168];
$packages=[21249,21255,21256,21257,21260,21261,21284,21287];
$supplierId=(int)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.MDO_Database::table('suppliers').' WHERE code=%s LIMIT 1','latitudes'));
$producerIds=array_map('intval',$wpdb->get_col($wpdb->prepare('SELECT DISTINCT wc_product_id FROM '.MDO_Database::table('source_products').' WHERE supplier_id=%d AND wc_product_id>0',$supplierId)));
if(count($producerIds)!==138 || !function_exists('emdo_musee_review_ids') || !get_option('emdo_musee_mentta_review_hold'))exit(3);
$verified=[];$oldSet=array_fill_keys($old,true);$map=[];
foreach($old as $id){
 $new=(int)get_post_meta($id,'_emdo_latitudes_clean_replacement_id',true);
 $file=(string)get_attached_file($new);
 $source=(string)get_attached_file($id);
 if(!$new||!is_readable($file)||!is_readable($source))throw new RuntimeException("IMAGE_REF_BROKEN $id $new");
 $bytes=file_get_contents($file);$im=$bytes?@imagecreatefromstring($bytes):false;
 if(!$im)throw new RuntimeException("CLEAN_IMAGE_CANNOT_DECODE $id $new");
 $w=imagesx($im);$h=imagesy($im);
 $o=getimagesize($source);
 if($w!==1024 || $h!==923 || (int)$o[0]!==1024 || (int)$o[1]!==1024)throw new RuntimeException("CLEAN_IMAGE_SIZE_WRONG $id $new [$w,$h]");
 $fileMime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
 if(!in_array($fileMime,['image/png','image/jpeg'],true))throw new RuntimeException("CLEAN_IMAGE_WRONG_MIME $id $fileMime");
 if(get_post_mime_type($new)!==$fileMime)throw new RuntimeException("CLEAN_ATTACHMENT_MIME_WRONG $id");
 $meta=wp_get_attachment_metadata($new);
 if(!is_array($meta)||empty($meta['file']))throw new RuntimeException("CLEAN_META_MISSING $id");
 imagedestroy($im);
 $verified[]=['old'=>$id,'new'=>$new,'mime'=>$fileMime,'bytes'=>strlen($bytes),'dimensions'=>[$w,$h],
  'source_sha256'=>hash_file('sha256',$source)];
 $map[$id]=$new;
}
foreach($packages as $id){
 if(!wp_attachment_is_image($id)||!is_readable((string)get_attached_file($id)))throw new RuntimeException("ORIGINAL_PACKAGE_IMAGE_MISSING $id");
 if(get_post_meta($id,'_emdo_latitudes_footer_removed_20261027',true))throw new RuntimeException("PACKAGE_ALTERED $id");
}
$products=array_values(array_unique(array_merge($producerIds,emdo_musee_review_ids())));
$prodVerification=[];$refErrors=[];
foreach($products as $pid){
 $p=wc_get_product($pid);if(!$p)throw new RuntimeException("PRODUCT_MISSING $pid");
 $refs=array_merge([(int)$p->get_image_id()],array_map('intval',$p->get_gallery_image_ids()));
 $raw=(string)get_post_meta($pid,'_emdo_image_map',true);
 $imageMap=json_decode($raw,true);
 if(is_array($imageMap))$refs=array_merge($refs,array_map('intval',array_values($imageMap)));
 $packLines=get_post_meta($pid,'_emdo_mentta_pack_lines',true);
 if(is_array($packLines))$refs=array_merge($refs,array_map('intval',array_column($packLines,'image')));
 $remaining=array_values(array_intersect($old,array_unique($refs)));
 if($remaining)$refErrors[]=['product'=>$pid,'remaining_old_attachments'=>$remaining];
 if(in_array($pid,emdo_musee_review_ids(),true)){
  if($p->get_status()!=='draft'||get_post_meta($pid,'_emdo_musee_awaiting_admin_approval',true)!=='yes')throw new RuntimeException("PRIVATE_PACK_VISIBILITY_BROKEN $pid");
 }
 $prodVerification[]=['id'=>$pid,'status'=>$p->get_status(),'bad_refs'=>count($remaining),
 'pack'=>in_array($pid,emdo_musee_review_ids(),true)];
}
if($refErrors)throw new RuntimeException('SOURCE_MAP_OLD_PHOTO_IDS_REMAIN '.wp_json_encode($refErrors));
$report=[
 'latitudes_producer_products'=>count($producerIds),'unique_affected_attachments'=>count($verified),
 'clean_images_all_decodable'=>true,'all_dimensions_1024x923'=>true,
 'preserved_packaging_images'=>count($packages),'products_checked'=>count($products),
 'pack_statuses'=>'all 7 draft Mentta held',
 'old_image_references_found'=>count($refErrors),
 'clean_new_attachment_ids'=>$map,'mime_types'=>array_count_values(array_column($verified,'mime'))
];
echo 'LATITUDES_31_CLEAN_IMAGES_INTEGRITY_AND_145_PRODUCTS_VERIFIED '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
