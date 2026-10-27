<?php
/**
 * Read-only inventory of ALL original Latitudes/Musée product-linked photos.
 * Includes featured, gallery, variation and attached-to-product media.
 * Produces crops of the bottom 43% for website/footer/copyright auditing.
 * Never modifies catalog products, attachments, media or Mentta settings.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product'))exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))exit(3);
$dir='/tmp/musee-latitudes-photo-audit-strict-20261027';
if(!wp_mkdir_p($dir.'/bottom')) {fwrite(STDERR,"AUDIT_DIR_NOT_WRITABLE\n");exit(4);}
// CRITICAL: supplier product identity MUST come from the Latitudes supplier ID,
// never from author 4949 (the author also owns many other producers' products).
global $wpdb;
if(!class_exists('MDO_Database')){fwrite(STDERR,"SUPPLIER_SYNC_DATABASE_MISSING\n");exit(5);}
$supTable=MDO_Database::table('suppliers');
$sourceTable=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$supTable} WHERE code=%s LIMIT 1",'latitudes'),ARRAY_A);
if(!$supplier || empty($supplier['id'])){fwrite(STDERR,"LATITUDES_SUPPLIER_ID_MISSING\n");exit(6);}
$mappedIds=array_map('intval',$wpdb->get_col($wpdb->prepare(
 "SELECT DISTINCT wc_product_id FROM {$sourceTable} WHERE supplier_id=%d AND wc_product_id>0",
 (int)$supplier['id'])));
$products=array_values(array_unique(array_filter($mappedIds,static fn($id)=>(int)$id>0)));
// Also include catalog items owned by the dedicated Latitudes vendor, if it exists;
// never use an unrelated shared importer account as a filter.
$vendor=get_userdata((int)$supplier['vendor_user_id']);
if($vendor && strtolower($vendor->user_login)==='latitudes'){
 $own=get_posts(['post_type'=>'product','author'=>(int)$vendor->ID,
    'post_status'=>['publish','draft','private','pending','future'],'fields'=>'ids','posts_per_page'=>-1,'suppress_filters'=>true]);
 $products=array_values(array_unique(array_merge($products,array_map('intval',$own))));
}
sort($products,SORT_NUMERIC);
if(count($products)<15 || count($products)>500){fwrite(STDERR,"STRICT_SUPPLIER_PRODUCT_COUNT_IMPLAUSIBLE ".count($products)."\n");exit(7);}
$photoMap=[];$catalogIds=[];$excludedPacks=[];
$add=static function($attachmentId,$productId,$kind)use(&$photoMap) {
 $attachmentId=(int)$attachmentId;
 if(!$attachmentId||!wp_attachment_is_image($attachmentId))return;
 if(!isset($photoMap[$attachmentId]))$photoMap[$attachmentId]=['image_id'=>$attachmentId,'products'=>[],'roles'=>[]];
 $photoMap[$attachmentId]['products'][(int)$productId]=true;
 $photoMap[$attachmentId]['roles'][$kind]=true;
};
foreach($products as $productId){
 $product=wc_get_product($productId);if(!$product)continue;
 if(get_post_meta($productId,'_emdo_mentta_pack_key',true)){ $excludedPacks[]=$productId;continue; }
 $catalogIds[]=(int)$productId;
 $add($product->get_image_id(),$productId,'featured');
 foreach((array)$product->get_gallery_image_ids() as $att)$add($att,$productId,'gallery');
 foreach((array)get_children(['post_parent'=>$productId,'post_type'=>'attachment','post_mime_type'=>'image','numberposts'=>-1,'fields'=>'ids']) as $att)$add($att,$productId,'attached');
 foreach($product->get_children() as $childId){
  $child=wc_get_product($childId);if(!$child)continue;
  $add($child->get_image_id(),$productId,'variation');
  foreach((array)get_children(['post_parent'=>$childId,'post_type'=>'attachment','post_mime_type'=>'image','numberposts'=>-1,'fields'=>'ids']) as $att)$add($att,$productId,'variation_attachment');
 }
}
ksort($photoMap,SORT_NUMERIC);$audit=[];$errors=[];$missing=0;$exported=0;
foreach($photoMap as $attachmentId=>$row){
 $file=(string)get_attached_file($attachmentId);
 $url=(string)wp_get_attachment_url($attachmentId);
 $mime=(string)get_post_mime_type($attachmentId);
 $item=['image_id'=>$attachmentId,'products'=>array_map('intval',array_keys($row['products'])),
  'roles'=>array_keys($row['roles']),'basename'=>basename($file),'original_url'=>$url,
  'mime_type'=>$mime,'image_title'=>get_the_title($attachmentId),'alt'=>get_post_meta($attachmentId,'_wp_attachment_image_alt',true),
  'present'=>is_readable($file),'bottom_crop'=>null];
 if(!is_readable($file)){ $missing++;$audit[]=$item;continue; }
 $bytes=@file_get_contents($file);
 $im=$bytes?@imagecreatefromstring($bytes):false;
 if(!$im){$errors[]='UNREADABLE_SOURCE:'.$attachmentId;$audit[]=$item;continue;}
 $w=imagesx($im);$h=imagesy($im);
 $item['dimensions']=[$w,$h];
 $item['size_bytes']=filesize($file);
 $item['sha256']=hash_file('sha256',$file);
 if($w<50||$h<50){$errors[]='TINY:'.$attachmentId;imagedestroy($im);$audit[]=$item;continue;}
 // Lower section, starting 55% down; OCR sees both the footer and bottom origin labels.
 $top=(int)floor($h*.55);$cropH=$h-$top;
 $destW=(int)min(1200,max(1,$w));$ratio=$destW/$w;$destH=max(1,(int)round($cropH*$ratio));
 $scaled=imagecreatetruecolor($destW,$destH);
 $white=imagecolorallocate($scaled,255,255,255);
 imagefill($scaled,0,0,$white);
 imagecopyresampled($scaled,$im,0,0,0,$top,$destW,$destH,$w,$cropH);
 $name=sprintf('%06d',$attachmentId).'.jpg';
 if(!imagejpeg($scaled,$dir.'/bottom/'.$name,87))$errors[]='CROP_WRITE_FAIL:'.$attachmentId;
 else{$item['bottom_crop']='bottom/'.$name;$exported++;}
 imagedestroy($scaled);imagedestroy($im);
 $audit[]=$item;
}
file_put_contents($dir.'/manifest.json',wp_json_encode([
 'generated_utc'=>gmdate('c'),'supplier_code'=>'latitudes','supplier_id'=>(int)$supplier['id'],'vendor_user_id'=>(int)$supplier['vendor_user_id'],'source_product_mapping_count'=>count($mappedIds),
 'scope'=>'ONLY products mapped to supplier code latitudes by MDO_Database source_products; plus dedicated Latitudes vendor products if vendor login is latitudes',
 'supplier_product_count'=>count($catalogIds),'excluded_exclusive_pack_ids'=>array_map('intval',$excludedPacks),
 'unique_media_count'=>count($audit),'crop_count'=>$exported,'missing_files'=>$missing,
 'errors'=>$errors,'photos'=>$audit
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
echo 'LATITUDES_MEDIA_AUDIT '.wp_json_encode([
 'source_product_mappings'=>count($mappedIds),'producer_products'=>count($catalogIds),'unique_linked_images'=>count($audit),
 'bottom_crops'=>$exported,'missing_files'=>$missing,
 'errors'=>count($errors),'excluded_mentta_packs'=>count($excludedPacks)
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
