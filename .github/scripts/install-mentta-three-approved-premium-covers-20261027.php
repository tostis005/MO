<?php
/**
 * WP-CLI only. Final three Mentta-hidden Musée packs: replace existing featured
 * covers with approved scenic headers + TRUE ORIGINAL supplier attachment photos.
 * No generative editing of any food photographs. All pack records remain drafts.
 * Requires three header-image JPEG files from the approved ChatGPT images.
 *
 * Usage: MDO_MUSEE_PREMIUM_HDR_DIR=/tmp/musee-approved-headers \
 *        MDO_MUSEE_PREMIUM_APPLY=1 wp eval-file SCRIPT.php
 */
if (!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')) {
 fwrite(STDERR,"WPCLI_WOOCOMMERCE_REQUIRED\n");exit(2);
}
if (!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),
 ['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
if (!function_exists('emdo_musee_review_ids') ||
    !function_exists('emdo_mentta_exclusive_ids') ||
    !get_option('emdo_musee_mentta_review_hold')) {
 fwrite(STDERR,"MENTTA_HOLD_NOT_PRESENT\n");exit(4);
}
$dir=rtrim((string)getenv('MDO_MUSEE_PREMIUM_HDR_DIR'),'/');
if(!$dir||!is_dir($dir)){fwrite(STDERR,"APPROVED_HEADERS_MISSING\n");exit(5);}
$packs=[
 21814=>['key'=>'pasaporte-salvaje','banner'=>'pasaporte','lines'=>9,'cols'=>3,'rows'=>3,'pvp'=>'153.49',
  'refs'=>[
   ['zebra_filet','cebra'],['bison_filet','bisonte'],['elk_cadera','alce'],
   ['boar_tender','jabalí'],['deer_loin','venado'],['ostrich_filet','avestruz'],
   ['kangaroo_200','canguro'],['croc_filet','cocodrilo'],['buffalo_loin','búfalo']]],
 21818=>['key'=>'safari-africano','banner'=>'safari','lines'=>8,'cols'=>4,'rows'=>2,'pvp'=>'154.99',
  'refs'=>[['zebra_filet','cebra'],['zebra_loin','cebra'],['zebra_burgers','cebra'],
   ['ostrich_filet','avestruz'],['ostrich_tender','avestruz'],['ostrich_burgers','avestruz'],
   ['croc_tender','cocodrilo'],['croc_burgers','cocodrilo']]],
 21820=>['key'=>'vuelta-al-mundo','banner'=>'mundo','lines'=>10,'cols'=>5,'rows'=>2,'pvp'=>'158.99',
  'refs'=>[['ostrich_filet','avestruz'],['japan_wagyu','wagyu'],['black_angus','angus'],
  ['zebra_filet','cebra'],['bison_filet','bisonte'],['deer_loin','venado'],
  ['kangaroo_200','canguro'],['buffalo_tender','búfalo'],['elk_loin','alce'],['boar_loin','jabalí']]]
];
$onlyKeys=trim((string)getenv('MDO_MUSEE_PREMIUM_KEYS'));
if($onlyKeys!==''){
 $whitelist=array_values(array_filter(explode(',',$onlyKeys)));
 $packs=array_filter($packs,static fn($c)=>in_array($c['key'],$whitelist,true));
 if(count($packs)!==count($whitelist)) {fwrite(STDERR,"INVALID_PREVIEW_KEYS\n");exit(15);}
}
function emdo_mp_normalize($s) {
 return strtolower(remove_accents((string)$s));
}
function emdo_mp_photo($path) {
 $bytes=@file_get_contents($path);
 $im=$bytes?@imagecreatefromstring($bytes):false;
 if(!$im)throw new RuntimeException("Photo decode error: ".basename((string)$path));
 return $im;
}
function emdo_mp_fill($im,$x1,$y1,$x2,$y2,$color) {
 imagefilledrectangle($im,(int)$x1,(int)$y1,(int)$x2,(int)$y2,$color);
}
function emdo_mp_fontfile() {
 foreach(['/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf',
 '/usr/share/fonts/truetype/liberation2/LiberationSerif-Bold.ttf',
 '/usr/share/fonts/dejavu/DejaVuSerif-Bold.ttf'] as $file) {
  if(is_readable($file))return $file;
 }
 return '';
}
function emdo_mp_caption($im,$label,$cx,$baseline,$maxWidth,$maxSize,$color,$ttf) {
 $label=(string)$label;
 if($ttf){
  $size=$maxSize;
  while($size>13){
   $bb=imagettfbbox($size,0,$ttf,$label);
   if(($bb[2]-$bb[0])<=$maxWidth)break;
   $size--;
  }
  $bb=imagettfbbox($size,0,$ttf,$label);
  imagettftext($im,$size,0,(int)round($cx-($bb[2]-$bb[0])/2),
   (int)$baseline,$color,$ttf,$label);
 } else imagestring($im,4,(int)($cx-100),(int)$baseline-14,$label,$color);
}
$check=[];$allImageHashes=[];
foreach($packs as $id=>$conf){
 $p=wc_get_product($id);
 if(!$p || !$p->is_type('simple') || $p->get_status()!=='draft' ||
  get_post_meta($id,'_emdo_mentta_pack_key',true)!==$conf['key'] ||
  get_post_meta($id,'_emdo_musee_awaiting_admin_approval',true)!=='yes' ||
  !in_array($id,emdo_musee_review_ids(),true) ||
  !in_array($id,emdo_mentta_exclusive_ids(),true) ||
  abs((float)$p->get_regular_price('edit')-(float)$conf['pvp'])>.001) {
  fwrite(STDERR,"PACK_STATUS_OR_PRICE_MISMATCH ".$id."\n");exit(6);
 }
 $headerFile="$dir/{$conf['banner']}.jpg";
 $h=emdo_mp_photo($headerFile);
 if(imagesx($h)<500 || imagesy($h)<180 || abs((imagesx($h)/imagesy($h))-(1254/463))>.025) {
  fwrite(STDERR,"WRONG_APPROVED_HEADER_ASPECT_RATIO {$conf['banner']}\n");exit(7);
 }
 imagedestroy($h);
 $lines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!is_array($lines) || count($lines)!==$conf['lines']) {
  fwrite(STDERR,"COMPOSITION_COUNT_ERROR ".$id."\n");exit(8);
 }
 $rows=[];$files=[];
 foreach($lines as $i=>$line){
  $sourceId=(int)($line['id']??0);$imgId=(int)($line['image']??0);
  $source=$sourceId?wc_get_product($sourceId):false;
  if(!$source || !$source->is_type('simple') || !$imgId ||
      !wp_attachment_is_image($imgId) || (int)($line['qty']??0)<1 ||
      (string)($line['key']??'')!==$conf['refs'][$i][0] ||
      !str_contains(emdo_mp_normalize($source->get_name()),
         emdo_mp_normalize($conf['refs'][$i][1]))) {
   fwrite(STDERR,"PACK_SOURCE_NAME_OR_ID_ERROR ".$id." index=".$i."\n");exit(9);
  }
  $path=(string)get_attached_file($imgId);
  if(!$path || !is_readable($path) || !@getimagesize($path)) {
   fwrite(STDERR,"SOURCE_IMAGE_MISSING ".$id." ".$imgId."\n");exit(10);
  }
  $files[$i]=$path;
  $rows[]=['key'=>$line['key'],'product_id'=>$sourceId,'attachment'=>$imgId,
    'name'=>$source->get_name(),'original_hash'=>hash_file('sha256',$path),
    'label'=>(string)$line['label']];
 }
 $check[$id]=['product'=>$p,'conf'=>$conf,'header'=>$headerFile,'lines'=>$lines,
  'sources'=>$rows,'files'=>$files,'old_cover'=>$p->get_image_id()];
}
$audits=[];
foreach($check as $id=>$x){
 $audits[]=['id'=>$id,'pack'=>$x['conf']['key'],'price'=>$x['conf']['pvp'],
  'old_cover'=>$x['old_cover'],'header'=>basename($x['header']),
  'photos'=>array_map(static fn($r)=>[$r['key'],$r['product_id'],$r['attachment'],$r['name']],$x['sources']),
  'status'=>'draft','mentta'=>'blocked'];
}
echo "PREMIUM_COVER_PREFLIGHT ".wp_json_encode($audits,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(getenv('MDO_MUSEE_PREMIUM_APPLY')!=='1') {echo "DRY_RUN_NO_CHANGES\n";exit(0);}
if(!function_exists('imagejpeg') || !function_exists('imagettftext')) {
 fwrite(STDERR,"GD_FREETYPE_MISSING\n");exit(11);
}
require_once ABSPATH.'wp-admin/includes/image.php';
$u=wp_upload_dir();
$target=trailingslashit($u['basedir']).'mentta-musee-packs';
if(!is_dir($target)&&!wp_mkdir_p($target))throw new RuntimeException('Uploads cannot be created');
$out=[];
foreach($check as $id=>$x){
 $conf=$x['conf'];$cols=$conf['cols'];$rows=$conf['rows'];
 $canvas=imagecreatetruecolor(1254,1254);
 $white=imagecolorallocate($canvas,255,255,255);
 $cream=imagecolorallocate($canvas,250,248,243);
 $border=imagecolorallocate($canvas,230,225,217);
 $black=imagecolorallocate($canvas,28,23,17);
 emdo_mp_fill($canvas,0,0,1254,1254,$white);
 $approvedHeader=emdo_mp_photo($x['header']);
 imagecopyresampled($canvas,$approvedHeader,0,0,0,0,1254,463,
  imagesx($approvedHeader),imagesy($approvedHeader));
 imagedestroy($approvedHeader);
 $margin=16;$gap=12;
 $width=(1254-2*$margin-$gap*($cols-1))/$cols;
 $height=(1254-463-26-($rows-1)*10)/$rows;
 $font=emdo_mp_fontfile();
 foreach($x['sources'] as $i=>$source){
  $col=$i%$cols;$row=intdiv($i,$cols);
  $left=(int)round($margin+$col*($width+$gap));
  $top=(int)round(463+3+$row*($height+10));
  $w=(int)floor($width);$h=(int)floor($height);
  emdo_mp_fill($canvas,$left,$top,$left+$w,$top+$h,$cream);
  imagerectangle($canvas,$left,$top,$left+$w,$top+$h,$border);
  $imageTop=$top+7;
  $captionHeight=$cols===5?62:50;
  $photoH=$h-$captionHeight-12;
  $photoW=$w-16;
  $photo=emdo_mp_photo($x['files'][$i]);
  $sw=imagesx($photo);$sh=imagesy($photo);
  // Fit the COMPLETE authentic image. Never replace pixels, retouch meat, crop labels or origin.
  $factor=min($photoW/$sw,$photoH/$sh);
  $dw=max(1,(int)floor($sw*$factor));$dh=max(1,(int)floor($sh*$factor));
  $tx=$left+8+(int)floor(($photoW-$dw)/2);
  $ty=$imageTop+(int)floor(($photoH-$dh)/2);
  imagecopyresampled($canvas,$photo,$tx,$ty,0,0,$dw,$dh,$sw,$sh);
  imagedestroy($photo);
  $lab=trim(preg_replace('/[—–]\s*\d+.*$/u','',$source['label']));
  if(str_contains($lab,'—'))$lab=trim(explode('—',$lab,2)[0]);
  // For labels including a counted quantity, the number is not removed.
  $fontSize=$cols===5?17:($cols===4?19:21);
  emdo_mp_caption($canvas,$lab,$left+$w/2,
   $top+$h-($cols===5?24:17),$w-12,$fontSize,$black,$font);
 }
 $imageHash=hash_file('sha256',$x['header']);
 foreach($x['sources'] as $s)$imageHash.=substr($s['original_hash'],0,16);
 $imageHash=substr(hash('sha256',$imageHash),0,17);
 $path="$target/{$conf['key']}-portada-premium-fotos-reales-$imageHash-v1.jpg";
 if(!imagejpeg($canvas,$path,92))throw new RuntimeException('Could not write image');
 imagedestroy($canvas);
 $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>$x['product']->get_name().' – portada premium fotos reales',
   'post_status'=>'inherit','post_content'=>'Cabecera aprobada y fotografías originales de nueve, ocho o diez artículos del productor, sin retocar su contenido.'],
   $path,$id,true);
 if(is_wp_error($attachment))throw new RuntimeException($attachment->get_error_message());
 $metadata=wp_generate_attachment_metadata($attachment,$path);
 wp_update_attachment_metadata($attachment,$metadata);
 update_post_meta($attachment,'_wp_attachment_image_alt',
   $x['product']->get_name().' – contenido real del pack');
 $p=wc_get_product($id);
 $p->set_image_id((int)$attachment);
 $p->set_status('draft');
 $p->update_meta_data('_emdo_musee_featured_premium_real_source_verified','yes');
 $p->update_meta_data('_emdo_musee_original_attachment_ids',
  array_column($x['sources'],'attachment'));
 $p->save();
 clean_post_cache($id);wc_delete_product_transients($id);
 if(function_exists('rocket_clean_post'))rocket_clean_post($id);
 if(function_exists('mdo_product_early_cache_purge_product_20261002'))
    mdo_product_early_cache_purge_product_20261002($id);
 $new=wc_get_product($id);
 if(!$new || $new->get_status()!=='draft' || $new->get_image_id()!==(int)$attachment
  || abs((float)$new->get_regular_price('edit')-(float)$conf['pvp'])>.001
  || !get_option('emdo_musee_mentta_review_hold')
  || !in_array($id,emdo_mentta_exclusive_ids(),true)) {
  fwrite(STDERR,"PUBLISH_AND_MENTTA_GUARD_FAILED ".$id."\n");exit(12);
 }
 foreach($x['sources'] as $i=>$s) {
  if(hash_file('sha256',$x['files'][$i])!==$s['original_hash']) {
   fwrite(STDERR,"ORIGINAL_PRODUCT_PHOTO_CHANGED ".$id."\n");exit(13);
  }
 }
 $out[]=['id'=>$id,'name'=>$new->get_name(),'price'=>$new->get_regular_price('edit'),
  'attachment'=>$attachment,'image_url'=>wp_get_attachment_url($attachment),
  'photo_count'=>count($x['sources']),
  'status'=>'draft','mentta'=>'blocked'];
}
echo 'PREMIUM_THREE_PACK_COVERS_INSTALLED '.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
