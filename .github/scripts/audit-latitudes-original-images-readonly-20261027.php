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
$dir='/tmp/musee-latitudes-photo-audit-20261027';
if(!wp_mkdir_p($dir.'/bottom')) {fwrite(STDERR,"AUDIT_DIR_NOT_WRITABLE\n");exit(4);}
$products=get_posts(['post_type'=>'product','author'=>4949,'post_status'=>['publish','draft','private','pending','future'],
  'fields'=>'ids','posts_per_page'=>-1,'suppress_filters'=>true]);
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
 'generated_utc'=>gmdate('c'),'supplier_wp_author_id'=>4949,
 'scope'=>'non-pack producer products: featured, gallery, variation and media attached to products',
 'supplier_product_count'=>count($catalogIds),'excluded_exclusive_pack_ids'=>array_map('intval',$excludedPacks),
 'unique_media_count'=>count($audit),'crop_count'=>$exported,'missing_files'=>$missing,
 'errors'=>$errors,'photos'=>$audit
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
echo 'LATITUDES_MEDIA_AUDIT '.wp_json_encode([
 'producer_products'=>count($catalogIds),'unique_linked_images'=>count($audit),
 'bottom_crops'=>$exported,'missing_files'=>$missing,
 'errors'=>count($errors),'excluded_mentta_packs'=>count($excludedPacks)
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
