<?php
/**
 * Clean 31 verified external-footer photos in only the Latitudes supplier catalog.
 *
 * - Original attachments NEVER modified or deleted.
 * - Prefer SAME-PHOTO clean duplicate only on very high similarity.
 * - Else losslessly crop exclusively the final 9.8% footer of a vendor JPEG,
 *   in PNG to preserve all remaining decoded original pixels.
 * - Replace featured/gallery/map references for ALL WooCommerce products,
 *   including the seven private Musée bundles and their stock component photo lines.
 * - Keep 8 package-imprinted museegourmet.com photos unchanged.
 * - Deploy through WP CLI with EMDO_LATITUDES_FOOTER_APPLY=1.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')){fwrite(STDERR,"WC_CLI_REQUIRED\n");exit(2);}
global $wpdb;
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
if(!class_exists('MDO_Database')||!get_option('emdo_musee_mentta_review_hold')||!function_exists('emdo_musee_review_ids')){fwrite(STDERR,"SUPPLIER_OR_MENTTA_SAFETY_MISSING\n");exit(4);}
$footerIds=[17177,17179,17183,17185,17199,17201,17205,17271,17275,17279,17283,17287,17289,17291,17293,17295,17297,17307,17313,17327,17333,17335,17337,17344,17348,17357,17359,17361,17363,17383,18168];
$packageIds=[21249,21255,21256,21257,21260,21261,21284,21287];
$batchValue=(string)(getenv('EMDO_LATITUDES_FOOTER_BATCH')?:'all');
if(!($batchValue==='all'||$batchValue==='final'||in_array($batchValue,['0','1','2','3','4'],true)))throw new RuntimeException('BAD_BATCH');
$batchIds=in_array($batchValue,['0','1','2','3','4'],true)?array_slice($footerIds,((int)$batchValue)*7,7):$footerIds;
$sup=MDO_Database::table('suppliers');$sources=MDO_Database::table('source_products');
$supId=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $sup WHERE code=%s LIMIT 1",'latitudes'));
if(!$supId)exit(5);
$supplierProductIds=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT DISTINCT wc_product_id FROM $sources WHERE supplier_id=%d AND wc_product_id>0",$supId)));
if(count($supplierProductIds)!==138){fwrite(STDERR,"SUPPLIER_COUNT_CHANGED ".count($supplierProductIds)."\n");exit(6);}
$packIds=emdo_musee_review_ids();
foreach($packIds as $pid){
 $p=wc_get_product($pid);
 if(!$p || $p->get_status()!=='draft' || get_post_meta($pid,'_emdo_musee_awaiting_admin_approval',true)!=='yes'){
  fwrite(STDERR,"PACK_NOT_HELD $pid\n");exit(7);
 }
}
$footerLookup=array_fill_keys($footerIds,true);
$decode=function($attachmentId){
 $file=(string)get_attached_file($attachmentId);
 if(!$file||!is_readable($file))throw new RuntimeException("ORIGINAL_FILE_UNREADABLE $attachmentId");
 $bytes=file_get_contents($file);$im=$bytes?@imagecreatefromstring($bytes):false;
 if(!$im)throw new RuntimeException("DECODE_FAILED $attachmentId");
 return [$file,$im,imagesx($im),imagesy($im)];
};
$rows=$wpdb->get_results("SELECT pm.post_id,pm.meta_key,pm.meta_value FROM {$wpdb->postmeta} pm
 JOIN {$wpdb->posts} p ON p.ID=pm.post_id
 WHERE p.post_type IN ('product','product_variation') AND pm.meta_key IN ('_thumbnail_id','_product_image_gallery','_emdo_image_map')",ARRAY_A);
$affectedProductIds=[];$originalImageLinks=[];
foreach($rows as $row){
 $id=(int)$row['post_id'];$v=(string)$row['meta_value'];$refs=[];
 if($row['meta_key']==='_thumbnail_id')$refs=[(int)$v];
 elseif($row['meta_key']==='_product_image_gallery')$refs=array_map('intval',array_filter(explode(',',$v)));
 else{ $map=json_decode($v,true);if(is_array($map))$refs=array_map('intval',array_values($map)); }
 foreach($refs as $imageId)if(isset($footerLookup[$imageId])){
  $affectedProductIds[$id]=true;$originalImageLinks[$imageId][$id]=true;
 }
}
foreach($packIds as $pid){
 $lines=get_post_meta($pid,'_emdo_mentta_pack_lines',true);
 if(is_array($lines))foreach($lines as $row){
  $imgId=(int)($row['image']??0);
  if(isset($footerLookup[$imgId])){$originalImageLinks[$imgId][$pid]=true;$affectedProductIds[$pid]=true;}
 }
}
$sourceIntegrity=[];$bad=[];
foreach($footerIds as $id){
 [$file,$im,$w,$h]=$decode($id);
 if($w<320||$h<320)throw new RuntimeException("LOW_RES_ORIGINAL $id");
 // Footer signature: white-left notice and dark lower-right web banner.
 // 31 IDs were verified independently by image review / OCR.
 $dark=0;$total=0;
 for($yy=(int)($h*.925);$yy<(int)($h*.975);$yy+=max(1,(int)($h*.01))){
  for($xx=(int)($w*.78);$xx<(int)($w*.97);$xx+=max(1,(int)($w*.03))){
   $c=imagecolorat($im,$xx,$yy);$r=($c>>16)&255;$g=($c>>8)&255;$b=$c&255;
   if(($r+$g+$b)/3<100)$dark++;
   $total++;
  }
 }
 imagedestroy($im);
 $ratio=$total?$dark/$total:0;
 // Warn rather than destroy if barcode/black bar appears a few pixels higher.
 if($ratio<.27)$bad[]=['id'=>$id,'dark'=>$ratio,'dimensions'=>[$w,$h]];
 $sourceIntegrity[$id]=['path'=>$file,'sha256'=>hash_file('sha256',$file),'w'=>$w,'h'=>$h,'footer_black_score'=>round($ratio,3)];
}
if($bad){echo 'FOOTER_SIGNATURE_REVIEW_REQUIRED '.wp_json_encode($bad)."\n";exit(8);}
echo 'LATITUDES_FOOTER_PREFLIGHT '.wp_json_encode([
 'supplier_products'=>count($supplierProductIds),'footer_photos'=>count($footerIds),
 'affected_visible_references'=>count($affectedProductIds),
 'source_reused_in_pack_components'=>array_values(array_filter($packIds,fn($pid)=>isset($affectedProductIds[$pid]))),
 'source_footer_integrity'=>$sourceIntegrity
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(getenv('EMDO_LATITUDES_FOOTER_APPLY')!=='1'){echo "INSPECT_ONLY_NO_MEDIA_CHANGED\n";exit(0);}
if(!function_exists('wp_insert_attachment'))exit(9);
require_once ABSPATH.'wp-admin/includes/image.php';
$upload=wp_upload_dir();
if(!empty($upload['error']))throw new RuntimeException("UPLOAD_ERROR");
$directory=trailingslashit($upload['basedir']).'latitudes-sin-pie-20261027';
if(!is_dir($directory)&&!wp_mkdir_p($directory))throw new RuntimeException("CANNOT_CREATE_OUTPUT_DIR");
function emdo_latitudes_foot_clean_similarity($original,$candidate) {
 $a=(string)get_attached_file($original);$b=(string)get_attached_file($candidate);
 if(!$a||!$b||!is_readable($a)||!is_readable($b))return 999;
 $ia=@imagecreatefromstring(file_get_contents($a));$ib=@imagecreatefromstring(file_get_contents($b));
 if(!$ia||!$ib){if($ia)imagedestroy($ia);if($ib)imagedestroy($ib);return 999;}
 $w1=imagesx($ia);$h1=imagesy($ia);$w2=imagesx($ib);$h2=imagesy($ib);
 $mae=0;$count=0;
 // Compare TOP 80% only, excluding footer, to avoid mistaking different cuts/angles.
 for($yy=0;$yy<16;$yy++)for($xx=0;$xx<16;$xx++){
  $sx=(int)(($xx+.5)/16*$w1);$sy=(int)(($yy+.5)/16*$h1*.8);
  $tx=(int)(($xx+.5)/16*$w2);$ty=(int)(($yy+.5)/16*$h2*.8);
  $ca=imagecolorat($ia,min($w1-1,$sx),min($h1-1,$sy));
  $cb=imagecolorat($ib,min($w2-1,$tx),min($h2-1,$ty));
  foreach([16,8,0] as $shift){$mae+=abs((($ca>>$shift)&255)-(($cb>>$shift)&255));$count++;}
 }
 imagedestroy($ia);imagedestroy($ib);
 return $count?$mae/$count:999;
}
$replacements=[];$report=[];
foreach($footerIds as $origId){
 if(!in_array($origId,$batchIds,true))continue;
 $src=$sourceIntegrity[$origId];
 // Resumable: a previous SSH break must never generate duplicate clean media.
 $existingClean=(int)get_post_meta($origId,'_emdo_latitudes_clean_replacement_id',true);
 if($existingClean && get_post_type($existingClean)==='attachment' && is_readable((string)get_attached_file($existingClean))){
  $replacements[$origId]=$existingClean;
  $report[]=['original'=>$origId,'new'=>$existingClean,'method'=>'RESUMED_PREVIOUS_CLEAN_MEDIA'];
  echo "RESUME_EXISTING_CLEAN_MEDIA $origId->$existingClean\n";
  continue;
 }
 if($batchValue==='final')throw new RuntimeException("MISSING_CLEAN_IMAGE_IN_FINAL_BATCH $origId");
 echo "START_PHOTO $origId\n";
 // A clean alternative is valid ONLY if it depicts the SAME PHOTO, not merely the same meat cut.
 $candidateIds=[];
 foreach(array_keys($originalImageLinks[$origId]??[]) as $pid){
  $prod=wc_get_product($pid);
  if(!$prod)continue;
  foreach(array_merge([$prod->get_image_id()],$prod->get_gallery_image_ids()) as $imgId) {
   $imgId=(int)$imgId;
   if($imgId && $imgId!==$origId && !isset($footerLookup[$imgId]) && !in_array($imgId,$packageIds,true))$candidateIds[$imgId]=true;
  }
 }
 $matched=0;$bestScore=999;
 foreach(array_keys($candidateIds) as $candidateId){
  $score=emdo_latitudes_foot_clean_similarity($origId,$candidateId);
  if($score<$bestScore){$bestScore=$score;$matched=$candidateId;}
 }
 // Very conservative threshold: different packaging angle, portion or cuts must never be substituted.
 if($bestScore<=5.5 && $matched && wp_attachment_is_image($matched)){
  $replacements[$origId]=$matched;
  $report[]=['original'=>$origId,'new'=>$matched,'method'=>'EXISTING_CLEAN_EXACT_MATCH','mae'=>round($bestScore,2)];
  continue;
 }
 [$file,$im,$w,$h]=$decode($origId);
 // Keep 90.2% of source photo 100% unchanged. Only the bottom external URL/copyright
 // strip is trimmed; package origin text / piece remains above the crop boundary.
 $newH=(int)floor($h*.902);
 $dst=imagecreatetruecolor($w,$newH);
 imagealphablending($dst,false);imagesavealpha($dst,true);
 imagecopy($dst,$im,0,0,0,0,$w,$newH);
 imagedestroy($im);
 $out=$directory.'/latitudes-'.$origId.'-sin-pie-'.substr($src['sha256'],0,12).'.png';
 if(!is_file($out)){
  if(!imagepng($dst,$out,7)){imagedestroy($dst);throw new RuntimeException("WRITE_FAILED $origId");}
 }
 imagedestroy($dst);
 $dims=getimagesize($out);
 if(!$dims||$dims[0]!==$w||$dims[1]!==$newH)throw new RuntimeException("CROPPED_IMAGE_INVALID $origId");
 $productId=(int)array_key_first($originalImageLinks[$origId]??[]);
 if(!in_array($productId,$supplierProductIds,true)) {
  // Find actual supplier product as attachment parent rather than packed product.
  $productId=(int)get_post_field('post_parent',$origId);
 }
 $attachment=wp_insert_attachment([
  'post_mime_type'=>'image/png','post_title'=>get_the_title($origId).' - sin pie web externo',
  'post_status'=>'inherit','post_content'=>'Versión de fotografía original de producto sin la franja inferior de URL externa; archivo original conservado para auditoría.'
 ],$out,$productId,true);
 if(is_wp_error($attachment)||!$attachment)throw new RuntimeException("WP_MEDIA_CREATE_FAILED $origId");
 wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$out));
 update_post_meta($attachment,'_wp_attachment_image_alt',get_post_meta($origId,'_wp_attachment_image_alt',true));
 update_post_meta($attachment,'_emdo_latitudes_cleaned_from_image_id',$origId);
 update_post_meta($attachment,'_emdo_latitudes_original_sha256',$src['sha256']);
 update_post_meta($attachment,'_emdo_latitudes_footer_removed_20261027','yes');
 update_post_meta($origId,'_emdo_latitudes_clean_replacement_id',(int)$attachment);
 // Keep source image tracking across future syncs with map ID remapping.
 $sourceUrl=get_post_meta($origId,'_source_url',true);
 if($sourceUrl)update_post_meta($attachment,'_source_url',$sourceUrl);
 $replacements[$origId]=(int)$attachment;
 echo "PHOTO_CLEANED $origId=>$attachment\n";
 $report[]=['original'=>$origId,'new'=>(int)$attachment,'method'=>'LOSSLESS_9_8_PERCENT_FOOTER_CROP','old_dimensions'=>[$w,$h],'new_dimensions'=>[$w,$newH],
  'original_media_sha256'=>$src['sha256'],'sanitized_file'=>basename($out)];
}
if(in_array($batchValue,['0','1','2','3','4'],true)){
 echo 'LATITUDES_BATCH_COMPLETED '.wp_json_encode(['batch'=>$batchValue,'photos_done'=>count($replacements),'originals_remain_unmodified'=>true])."\n";
 exit(0);
}
if(count($replacements)!==31)throw new RuntimeException('INCOMPLETE_REPLACEMENT_SET');
$changeProducts=[];
$targetProductIds=array_unique(array_merge(array_keys($affectedProductIds),$supplierProductIds,$packIds));
foreach($targetProductIds as $pid){
 $p=wc_get_product($pid);
 if(!$p)continue;
 $oldCover=(int)$p->get_image_id();$oldGallery=array_map('intval',$p->get_gallery_image_ids());
 $price=$p->get_regular_price('edit');$status=$p->get_status();$stock=$p->get_stock_status();
 $newCover=$replacements[$oldCover]??$oldCover;
 $newGallery=[];foreach($oldGallery as $imgId){$newId=$replacements[$imgId]??$imgId;if($newId!==$newCover&&!in_array($newId,$newGallery,true))$newGallery[]=$newId;}
 $changed=($newCover!==$oldCover||$oldGallery!==$newGallery);
 if($changed){
  $p->set_image_id($newCover);$p->set_gallery_image_ids($newGallery);$p->save();
 }
 $mapRaw=(string)get_post_meta($pid,'_emdo_image_map',true);$imageMap=json_decode($mapRaw,true);$mapChanged=0;
 if(is_array($imageMap)){
  foreach($imageMap as $url=>$img){$id=(int)$img;if(isset($replacements[$id])){$imageMap[$url]=$replacements[$id];$mapChanged++;}}
  if($mapChanged)update_post_meta($pid,'_emdo_image_map',wp_json_encode($imageMap,JSON_UNESCAPED_SLASHES));
 }
 $packLines=get_post_meta($pid,'_emdo_mentta_pack_lines',true);$lineCount=0;
 if(is_array($packLines)){
  foreach($packLines as &$line){
   $id=(int)($line['image']??0);
   if(isset($replacements[$id])){$line['image']=$replacements[$id];$lineCount++;}
  }unset($line);
  if($lineCount)update_post_meta($pid,'_emdo_mentta_pack_lines',$packLines);
 }
 if($changed||$mapChanged||$lineCount){
  wc_delete_product_transients($pid);clean_post_cache($pid);
  if(function_exists('rocket_clean_post'))rocket_clean_post($pid);
  $check=wc_get_product($pid);
  if(!$check || $check->get_status()!==$status || $check->get_regular_price('edit')!==$price ||
    $check->get_stock_status()!==$stock || (int)$check->get_image_id()!==$newCover ||
    $check->get_gallery_image_ids()!==$newGallery)throw new RuntimeException("PRODUCT_VERIFICATION_FAILED $pid");
  $changeProducts[]=['id'=>$pid,'images'=>($changed?1:0),'import_map'=>$mapChanged,'pack_component_images'=>$lineCount,
    'pack'=>in_array((int)$pid,$packIds,true)];
 }
}
foreach($packIds as $pid){
 if(wc_get_product($pid)->get_status()!=='draft')throw new RuntimeException("MENTTA_DRAFT_BROKEN $pid");
 if(!get_option('emdo_musee_mentta_review_hold'))throw new RuntimeException("MENTTA_BLOCK_REMOVED");
}
foreach($targetProductIds as $pid){
 $p=wc_get_product($pid);if(!$p)continue;
 if(isset($footerLookup[(int)$p->get_image_id()]))throw new RuntimeException("OLD_FEATURED_REMAINS $pid");
 foreach($p->get_gallery_image_ids() as $aid)if(isset($footerLookup[(int)$aid]))throw new RuntimeException("OLD_GALLERY_REMAINS $pid");
 $m=json_decode((string)get_post_meta($pid,'_emdo_image_map',true),true);
 if(is_array($m))foreach($m as $a)if(isset($footerLookup[(int)$a]))throw new RuntimeException("OLD_IMPORT_MAP_REMAINS $pid");
 $lines=get_post_meta($pid,'_emdo_mentta_pack_lines',true);
 if(is_array($lines))foreach($lines as $line)if(isset($footerLookup[(int)($line['image']??0)]))throw new RuntimeException("OLD_PACK_COMPONENT_REMAINS $pid");
}
echo "LATITUDES_31_IMAGES_AND_REUSED_PACK_GALLERIES_CORRECTED ".wp_json_encode([
 'photos_corrected'=>count($replacements),'exact_clean_duplicates'=>count(array_filter($report,fn($r)=>$r['method']==='EXISTING_CLEAN_EXACT_MATCH')),
 'new_clean_photos'=>count(array_filter($report,fn($r)=>$r['method']==='LOSSLESS_9_8_PERCENT_FOOTER_CROP')),
 'affected_woo_products'=>count($changeProducts),
 'packs_with_updated_image_references'=>array_values(array_map(fn($r)=>$r['id'],array_filter($changeProducts,fn($r)=>$r['pack']))),
 'photos'=>$report,'product_changes'=>$changeProducts,
 'packs_still_draft_and_mentta_held'=>true
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
