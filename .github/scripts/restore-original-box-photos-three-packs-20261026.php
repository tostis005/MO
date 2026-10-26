<?php
/**
 * 2026-10-26. Restore only vendor-original pixels to the lower photo grid of
 * the three Musée Gourmet Mentta REVIEW drafts. No image generation.
 * WP CLI: MDO_REAL_BOX_APPLY=1 wp eval-file this-script.php
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"REQUIRES_WORDPRESS_WPCLI_WOOCOMMERCE\n");exit(2);
}
if (!function_exists('emdo_musee_review_ids') || !function_exists('emdo_mentta_exclusive_ids')
 || !get_option('emdo_musee_mentta_review_hold')) {fwrite(STDERR,"MENTTA_REVIEW_HOLD_MISSING\n");exit(3);}
$expected=[
 21814=>['key'=>'pasaporte-salvaje','count'=>9,'cols'=>3,'rows'=>3,'rowH'=>325],
 21818=>['key'=>'safari-africano','count'=>8,'cols'=>4,'rows'=>2,'rowH'=>332],
 21820=>['key'=>'vuelta-al-mundo','count'=>10,'cols'=>5,'rows'=>2,'rowH'=>315],
];
$rows=[];
$images=[];
foreach($expected as $packId=>$conf) {
 $pack=wc_get_product($packId);
 if(!$pack || $pack->get_status()!=='draft'
   || get_post_meta($packId,'_emdo_mentta_pack_key',true)!==$conf['key']
   || !in_array($packId,emdo_musee_review_ids(),true)
   || !in_array($packId,emdo_mentta_exclusive_ids(),true)) {fwrite(STDERR,"PACK_NOT_HELD ".$packId."\n");exit(4);}
 $components=get_post_meta($packId,'_emdo_mentta_pack_lines',true);
 if(!is_array($components)||count($components)!==$conf['count']) {fwrite(STDERR,"COMPONENT_COUNT_MISMATCH ".$packId."\n");exit(5);}
 $imgId=(int)$pack->get_image_id();$current=(string)get_attached_file($imgId);
 if(!$imgId || !$current || !is_readable($current)) {fwrite(STDERR,"HERO_MISSING ".$packId."\n");exit(6);}
 $dims=@getimagesize($current);
 $targetH=478+85+$conf['rows']*$conf['rowH']+40;
 echo "CURRENT_PACK_HERO_DIAGNOSTIC ".wp_json_encode([
   'id'=>$packId,'name'=>$pack->get_name(),'image_id'=>$imgId,
   'file'=>basename($current),'dimensions'=>[(int)$dims[0],(int)$dims[1]],'expected_height'=>$targetH,
   'component_keys'=>array_map(static fn($l)=>$l['key']??'',get_post_meta($packId,'_emdo_mentta_pack_lines',true)??[])
 ],JSON_UNESCAPED_UNICODE)."\n";
 if(!$dims || (int)$dims[0]!==1400 || (int)$dims[1]!==$targetH) {
  if(getenv('MDO_REAL_BOX_APPLY')!=='1'){ echo "INSPECT_GEOMETRY_MISMATCH_NOT_MODIFIED ".$packId."\n";continue; }
  fwrite(STDERR,"HERO_GEOMETRY_UNEXPECTED ".$packId." ".json_encode($dims)."\n");exit(7);
 }
 $tiles=[];
 foreach($components as $i=>$line) {
  $sourceId=(int)($line['id']??0);$imageId=(int)($line['image']??0);
  $source=$sourceId?wc_get_product($sourceId):false;
  if(!$source || (int)($line['qty']??0)<1 || !$imageId || !$source->is_type('simple')) {
   fwrite(STDERR,"INVALID_COMPONENT ".$packId." ".$i."\n");exit(8);
  }
  $name=(string)$source->get_name();
  $preferBurgerName=($packId===21818 && in_array((string)($line['key']??''),['zebra_burgers','croc_burgers'],true));
  $candidateIds=array_values(array_unique(array_filter(array_merge([$imageId,(int)$source->get_image_id()],$source->get_gallery_image_ids()))));
  $chosen=$imageId;
  if($preferBurgerName){
   $species=$line['key']==='zebra_burgers'?'cebra':'cocodrilo';
   // Refuse a generic loose-burger photo; prefer the supplier's genuine boxed
   // 2 x 150 g attachment (e.g. CEBRA-150.jpg / COCODRILO-150.jpg).
   $found=0;
   foreach($candidateIds as $id) {
    $file=(string)get_attached_file($id);
    $filename=strtolower(remove_accents(pathinfo($file,PATHINFO_FILENAME)));
    if(str_contains($filename,$species) && preg_match('/(?:^|[-_ ])150(?:$|[-_ ])/', $filename)) {$found=$id;break;}
   }
   if(!$found) {
    // Look for original same-named catalogue media in WP uploads, not an AI replacement.
    global $wpdb;
    $like=$wpdb->esc_like($species).'%150%';
    $attachmentMatches=$wpdb->get_col($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND LOWER(meta_value) LIKE %s ORDER BY post_id DESC LIMIT 30",'%'.$like
    ));
    foreach($attachmentMatches as $id){
     $file=(string)get_attached_file((int)$id);
     $stem=strtolower(remove_accents(pathinfo($file,PATHINFO_FILENAME)));
     if(str_contains($stem,$species) && str_contains($stem,'150') && is_readable($file)) {$found=(int)$id;break;}
    }
   }
   if(!$found){fwrite(STDERR,"ORIGINAL_BURGER_BOX_NOT_FOUND ".$species." pack=".$packId."\n");exit(9);}
   $chosen=$found;
  }
  $src=(string)get_attached_file($chosen);
  if(!is_readable($src) || !wp_attachment_is_image($chosen)) {fwrite(STDERR,"REAL_SOURCE_IMAGE_MISSING ".$sourceId."\n");exit(10);}
  $meta=@getimagesize($src);
  if(!$meta || $meta[0]<130 || $meta[1]<130) {fwrite(STDERR,"REAL_SOURCE_TOO_SMALL ".$chosen."\n");exit(11);}
  $tiles[]=[
   'index'=>$i,'source_id'=>$sourceId,'image_id'=>$chosen,
   'actual_name'=>$name,'filename'=>basename($src),'path'=>$src,
   'original_sha256'=>hash_file('sha256',$src),
   'key'=>(string)($line['key']??''),
  ];
 }
 $rows[$packId]=['product'=>$pack,'hero_id'=>$imgId,'hero_path'=>$current,
  'conf'=>$conf,'tiles'=>$tiles,'original_price'=>$pack->get_regular_price('edit')];
}
$inspect=array_map(static function($r){
 return ['pack_key'=>$r['conf']['key'],'header_preserved'=>true,
  'photo_sources'=>array_map(static fn($t)=>[
   'item'=>$t['key'],'attachment_id'=>$t['image_id'],'source_product'=>$t['source_id'],
   'filename'=>$t['filename']],$r['tiles'])];
},$rows);
echo "ORIGINAL_MEDIA_AUDIT ".wp_json_encode($inspect,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(getenv('MDO_REAL_BOX_APPLY')!=='1'){echo "INSPECT_ONLY_NOTHING_CHANGED\n";exit(0);}
function mdo_real_img($path) {
 $bytes=@file_get_contents($path);
 $im=$bytes?@imagecreatefromstring($bytes):false;
 if(!$im)throw new RuntimeException("Original source image decode failed: ".basename($path));
 return $im;
}
$updates=[];
foreach($rows as $productId=>$r) {
 $im=mdo_real_img($r['hero_path']);$conf=$r['conf'];
 $cream=imagecolorallocate($im,252,249,241);
 $left=18;$gap=13;$tileW=(int)floor((1400-36-$gap*($conf['cols']-1))/$conf['cols']);
 foreach($r['tiles'] as $t) {
  $i=$t['index'];$col=$i%$conf['cols'];$row=intdiv($i,$conf['cols']);
  $x=$left+$col*($tileW+$gap);$y=478+88+$row*$conf['rowH'];
  // Modify only the picture rectangle. Every pixel from the title, animals,
  // header, "Incluye" and the product labels is unchanged.
  $ix=$x+12;$iy=$y+12;
  $iw=$tileW-24;$ih=$conf['rowH']-115;
  imagefilledrectangle($im,$ix,$iy,$ix+$iw,$iy+$ih,$cream);
  $photo=mdo_real_img($t['path']);
  $sw=imagesx($photo);$sh=imagesy($photo);
  $scale=min($iw/$sw,$ih/$sh);
  $dw=max(1,(int)floor($sw*$scale));$dh=max(1,(int)floor($sh*$scale));
  imagecopyresampled($im,$photo,$ix+(int)(($iw-$dw)/2),$iy+(int)(($ih-$dh)/2),0,0,$dw,$dh,$sw,$sh);
  imagedestroy($photo);
 }
 $uploads=wp_upload_dir();if(!empty($uploads['error']))throw new RuntimeException('Upload folder unavailable');
 $dir=trailingslashit($uploads['basedir']).'mentta-musee-packs';
 if(!is_dir($dir)&&!wp_mkdir_p($dir))throw new RuntimeException('Cannot make upload folder');
 $file=$dir.'/mentta-musee-'.$conf['key'].'-solo-fotos-originales-cajas-20261026.jpg';
 if(!imagejpeg($im,$file,93)){imagedestroy($im);throw new RuntimeException('Cannot save corrected image');}
 imagedestroy($im);
 $data=file_get_contents($file);$newIm=@imagecreatefromstring($data);
 if(!$newIm || imagesx($newIm)!==1400 || imagesy($newIm)!==478+85+$conf['rows']*$conf['rowH']+40) {
  throw new RuntimeException("Output image validation failed: ".$conf['key']);
 }
 imagedestroy($newIm);
 // Prove neither the original product photographs nor their color data changed.
 foreach($r['tiles'] as $t)
  if(hash_file('sha256',$t['path'])!==$t['original_sha256'])throw new RuntimeException('Original media changed unexpectedly');
 require_once ABSPATH.'wp-admin/includes/image.php';
 $att=wp_insert_attachment([
   'post_mime_type'=>'image/jpeg','post_title'=>$r['product']->get_name().' - fotografías reales originales',
   'post_status'=>'inherit','post_content'=>'Fotomontaje determinista de fotografías reales de los envases originales. Sin generación ni alteración del contenido visible en la ventana de carne.'
 ],$file,$productId,true);
 if(is_wp_error($att)||!$att)throw new RuntimeException("WP image attachment creation failure");
 wp_update_attachment_metadata($att,wp_generate_attachment_metadata($att,$file));
 update_post_meta($att,'_wp_attachment_image_alt',$r['product']->get_name().' - cajas originales Latitudes');
 $product=wc_get_product($productId);
 $product->set_image_id((int)$att);
 $product->set_status('draft');
 // Prior instruction: 151,99 + 1,50 for Pasaporte after replacing buffalo entrecot.
 if($productId===21814) {
  $old=(float)$product->get_regular_price('edit');
  if(abs($old-151.99)<.001)$product->set_regular_price('153.49');
  elseif(abs($old-153.49)>.001)throw new RuntimeException('Unexpected Pasaporte price, refusing overwrite');
 }
 $product->update_meta_data('_emdo_musee_100_percent_authentic_tiles','yes');
 $product->update_meta_data('_emdo_musee_source_image_ids',array_column($r['tiles'],'image_id'));
 $product->save();wc_delete_product_transients($productId);
 $live=wc_get_product($productId);
 if(!$live || $live->get_status()!=='draft' || (int)$live->get_image_id()!==(int)$att
  || !in_array($productId,emdo_mentta_exclusive_ids(),true)
  || !get_option('emdo_musee_mentta_review_hold'))throw new RuntimeException('Output pack cannot be safely verified');
 $updates[]=['id'=>$productId,'pack'=>$conf['key'],'new_image_id'=>$att,
  'original_photos'=>count($r['tiles']),'price'=>$live->get_regular_price('edit'),
  'draft'=>true,'mentta_on_hold'=>true];
}
echo "THREE_PACKS_REAL_ORIGINAL_BOX_IMAGES_APPLIED ".wp_json_encode($updates,JSON_UNESCAPED_UNICODE)."\n";
