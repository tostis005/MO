<?php
/**
 * Force refresh only the three Musée Gourmet ADMIN-ONLY draft pack cover images.
 * Always gives the browser a NEW filename/attachment URL for the current exact
 * picture bytes, so browser/CDN immutable caches cannot serve an old cover.
 *
 * Run via WP CLI: MDO_MUSEE_CACHE_REFRESH_APPLY=1 wp eval-file THIS_SCRIPT.php
 * No changes to image pixels, descriptions, component lines or Mentta visibility.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"REQUIRES_WPCLI_WOOCOMMERCE\n");exit(2);
}
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)){
 fwrite(STDERR,"INCORRECT_SITE\n");exit(3);
}
if(!function_exists('emdo_musee_review_ids') || !function_exists('emdo_mentta_exclusive_ids')
 || !get_option('emdo_musee_mentta_review_hold')) {
 fwrite(STDERR,"MENTTA_REVIEW_HOLD_NOT_ENABLED\n");exit(4);
}
$packIds=[
 21814=>'pasaporte-salvaje',
 21818=>'safari-africano',
 21820=>'vuelta-al-mundo'
];
$expectedImages=[
 21814=>21841,
 21818=>21842,
 21820=>21843
];
$all=[];$sourceHashes=[];
foreach($packIds as $pid=>$slug) {
 $product=wc_get_product($pid);$picture=$product?(int)$product->get_image_id():0;
 if(!$product || $product->get_status()!=='draft' ||
   (string)get_post_meta($pid,'_emdo_mentta_pack_key',true)!==$slug ||
   !in_array($pid,emdo_musee_review_ids(),true) ||
   !in_array($pid,emdo_mentta_exclusive_ids(),true) ||
   (int)$picture!==$expectedImages[$pid] ||
   (string)get_post_meta($pid,'_emdo_musee_awaiting_admin_approval',true)!=='yes') {
  fwrite(STDERR,"WRONG_DRAFT_OR_IMAGE_ID ".$pid."\n");exit(5);
 }
 $file=(string)get_attached_file($picture);
 $url=(string)wp_get_attachment_url($picture);
 $mimetype=get_post_mime_type($picture);
 $dim=$file&&is_file($file)?@getimagesize($file):false;
 if(!$file || !is_readable($file)|| !$url || !$dim ||
  $dim[0]!==1400 || $dim[1]<1150 || $dim[1]>1800 ||
  $mimetype!=='image/jpeg') {
  fwrite(STDERR,"IMAGE_FILE_NOT_USABLE ".$pid."\n");exit(6);
 }
 $hash=hash_file('sha256',$file);
 if(isset($sourceHashes[$hash])){fwrite(STDERR,"PACK_COVERS_NOT_DISTINCT ".$pid."\n");exit(7);}
 $sourceHashes[$hash]=true;
 $all[$pid]=['product'=>$product,'file'=>$file,'cover_id'=>$picture,
   'previous_url'=>$url,'sha256'=>$hash,'slug'=>$slug,'image_dims'=>[$dim[0],$dim[1]]];
}
echo 'THREE_COVERS_BEFORE_CACHE_REFRESH '.wp_json_encode(
 array_map(static fn($r)=>['cover_id'=>$r['cover_id'],'url'=>$r['previous_url'],
   'sha256'=>$r['sha256'],'dimensions'=>$r['image_dims']],$all),
 JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(getenv('MDO_MUSEE_CACHE_REFRESH_APPLY')!=='1'){echo "INSPECT_ONLY_NO_CHANGES\n";exit(0);}
require_once ABSPATH.'wp-admin/includes/image.php';
$upload=wp_upload_dir();
if(!empty($upload['error']))throw new RuntimeException('WP uploads directory error');
$targetDir=trailingslashit($upload['basedir']).'mentta-musee-packs';
if(!is_dir($targetDir) && !wp_mkdir_p($targetDir))throw new RuntimeException('Cannot create media folder');
$beforeProps=[];$prepared=[];
foreach($all as $pid=>$r) {
 $p=$r['product'];
 $beforeProps[$pid]=[
  'price'=>$p->get_regular_price('edit'),
  'status'=>$p->get_status(),
  'weight'=>$p->get_weight('edit'),
  'gallery'=>$p->get_gallery_image_ids(),
  'lines'=>get_post_meta($pid,'_emdo_mentta_pack_lines',true)
 ];
 // Distinct, never-before-used URL invalidates both browser and edge caches.
 $name='musee-'.$r['slug'].'-portada-actualizada-20261026-'.substr($r['sha256'],0,14).'-v2.jpg';
 $path=$targetDir.'/'.$name;
 if(file_exists($path)) {
  // Avoid overwriting existing public media files (never serve wrong contents).
  if(hash_file('sha256',$path)!==$r['sha256'])throw new RuntimeException('Unexpected existing cache-bust file');
 } else {
  if(!copy($r['file'],$path))throw new RuntimeException('Cannot clone original image bytes');
 }
 if(hash_file('sha256',$path)!==$r['sha256'])throw new RuntimeException('Image content altered');
 $attach=wp_insert_attachment([
   'post_mime_type'=>'image/jpeg',
   'post_title'=>$p->get_name().' · portada actualizada y caché renovada',
   'post_status'=>'inherit',
   'post_content'=>'Fotografía compuesta con los archivos reales de cada producto. Esta copia conserva exactamente los mismos píxeles que la portada aprobada y usa otra URL para evitar caché antigua.',
 ],$path,$pid,true);
 if(is_wp_error($attach)||!$attach)throw new RuntimeException('Failed creating attachment');
 $metadata=wp_generate_attachment_metadata($attach,$path);
 if(!is_array($metadata) || empty($metadata['file']))throw new RuntimeException('Could not regenerate image thumbnails');
 wp_update_attachment_metadata($attach,$metadata);
 update_post_meta($attach,'_wp_attachment_image_alt',$p->get_name().' – portada actualizada con productos reales');
 $prepared[$pid]=['id'=>$attach,'file'=>$path,'url'=>wp_get_attachment_url($attach),
   'sizes'=>array_keys($metadata['sizes']??[])];
}
foreach($prepared as $pid=>$img){
 $p=wc_get_product($pid);
 $p->set_image_id((int)$img['id']);
 $p->set_status('draft');
 $p->update_meta_data('_emdo_musee_cover_cache_refreshed_20261026','yes');
 $p->update_meta_data('_emdo_musee_previous_cover_id',$all[$pid]['cover_id']);
 $p->save();
 clean_post_cache($pid);
 wc_delete_product_transients($pid);
 wp_cache_delete($pid,'posts');
 wp_cache_delete($pid,'post_meta');
 wp_cache_delete((int)$img['id'],'posts');
 wp_cache_delete((int)$img['id'],'post_meta');
 if(function_exists('mdo_product_early_cache_purge_product_20261002')) {
  mdo_product_early_cache_purge_product_20261002($pid);
 }
 // Explicit cleanup for both /producto and /en/product static cache files,
 // even if the MU invalidator has not been deployed to this server.
 $keys=[
  '/producto/pack-musee-mentta-'.$all[$pid]['slug'].'/',
  '/en/product/pack-musee-mentta-'.$all[$pid]['slug'].'/'
 ];
 foreach($keys as $key) {
  $oldPath=WP_CONTENT_DIR.'/uploads/elmercado-product-static-v1/'.hash('sha256',$key).'.html';
  if(is_file($oldPath))@unlink($oldPath);
 }
 // Rocket targeted page purge; no global homepage/catalogue cache clear.
 if(function_exists('rocket_clean_post')) rocket_clean_post($pid);
 if(function_exists('litespeed_purge_post')) litespeed_purge_post($pid);
 if(function_exists('w3tc_flush_post')) w3tc_flush_post($pid);
}
$out=[];
foreach($prepared as $pid=>$im){
 $p=wc_get_product($pid);$before=$beforeProps[$pid];$path=(string)get_attached_file((int)$im['id']);
 if(!$p || $p->get_status()!=='draft' ||
   (int)$p->get_image_id()!==(int)$im['id'] ||
   $p->get_regular_price('edit')!==$before['price'] ||
   $p->get_weight('edit')!==$before['weight'] ||
   $p->get_gallery_image_ids()!==$before['gallery'] ||
   get_post_meta($pid,'_emdo_mentta_pack_lines',true)!==$before['lines'] ||
   !is_readable($path) || hash_file('sha256',$path)!==$all[$pid]['sha256'] ||
   (string)wp_get_attachment_url((int)$im['id'])===$all[$pid]['previous_url'] ||
   !in_array($pid,emdo_mentta_exclusive_ids(),true) ||
   !get_option('emdo_musee_mentta_review_hold')) {
  fwrite(STDERR,"CACHE_REFRESH_VERIFICATION_FAILED ".$pid."\n");exit(14);
 }
 $out[]=['product_id'=>$pid,'name'=>$p->get_name(),'old_featured_attachment'=>$all[$pid]['cover_id'],
  'new_featured_attachment'=>$im['id'],'old_url'=>$all[$pid]['previous_url'],
  'new_url'=>$im['url'],'content_sha256'=>$all[$pid]['sha256'],'generated_thumbnail_sizes'=>$im['sizes'],
  'draft'=>true,'mentta_paused'=>true];
}
echo 'THREE_COVERS_UNIQUE_FRESH_URL_AND_TARGETED_CACHE_PURGED '.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
