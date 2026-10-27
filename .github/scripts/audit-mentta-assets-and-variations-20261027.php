<?php
/** Read-only diagnostic for Mentta product images and per-variation serializer fields. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WC_Mentta_Plugin'))exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products';
$ids=[21814,21818,21820,21904,21906,21908,17129,1350,4160];
$out=[];
foreach($ids as $id){
 $p=wc_get_product($id);if(!$p)continue;
 $r=new WP_REST_Request('GET','/mentta_marketplace/products');$r->set_param('product_id',$id);
 $ans=WC_Mentta_Plugin::get_products($r);
 $b=$ans instanceof WP_REST_Response?$ans->get_data():[];
 $d=is_object($b)?($b->data??[]):(is_array($b)?($b['data']??[]):[]);
 $item=is_array($d)?($d[0]??[]):[];
 $vars=[];
 foreach((array)($item['Variations']??[]) as $v){
  $vars[]=['id'=>$v['Id']??0,'purchasable'=>$v['IsPurchasable']??null,
   'visible'=>$v['IsVisible']??null,'stock'=>$v['IsInStock']??null,
   'price'=>$v['PriceIncludingTax']??null,'image_present'=>(bool)($v['Image']??null),
   'attributes'=>$v['Attributes']??[]];
 }
 $image=$item['Image']??'';
 $s=($p->get_image_id()?(string)wp_get_attachment_url($p->get_image_id()):'');
 $url=is_string($image)&&filter_var($image,FILTER_VALIDATE_URL)?$image:$s;
 $http_status='not_checked';$type='';$len=0;
 if($url){
  $resp=wp_remote_head($url,['timeout'=>8,'redirection'=>2,'sslverify'=>true]);
  if(is_wp_error($resp))$http_status='error:'.$resp->get_error_code();
  else{$http_status=(int)wp_remote_retrieve_response_code($resp);$type=(string)wp_remote_retrieve_header($resp,'content-type');$len=(int)wp_remote_retrieve_header($resp,'content-length');}
 }
 $out[]=['id'=>$id,'feed_image_format'=>gettype($image),'wp_img_url_present'=>(bool)$s,
  'url_image_matches_wordpress'=>$url===$s,
  'image_head_status'=>$http_status,'mime'=>$type,'length'=>$len,
  'feed_gallery_count'=>count((array)($item['GalleryImages']??[])),
  'taxonomical_producer_terms'=>is_wp_error($x=wp_get_object_terms($id,'pa_productor',['fields'=>'names']))?[]:$x,
  'variation_count'=>count($vars),'variation_summary'=>array_slice($vars,0,8)];
}
echo "MENTTA_ASSET_AND_VARIATIONS_AUDIT ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
// Only file names and counts; no raw log content is ever output.
$dirs=[WP_CONTENT_DIR.'/uploads/wc-logs',WP_CONTENT_DIR.'/uploads/woocommerce-logs',WP_CONTENT_DIR.'/logs'];
$logs=[];
foreach($dirs as $dir){
 if(!is_dir($dir))continue;
 foreach((array)glob($dir.'/*') as $file){
  if(!is_file($file)||!is_readable($file))continue;
  if(!preg_match('/mentta|import/i',basename($file)))continue;
  $size=(int)filesize($file);
  $fh=fopen($file,'rb');if(!$fh)continue;
  fseek($fh,max(0,$size-512000));$content=stream_get_contents($fh);fclose($fh);
  $c=['page_count'=>0,'error_count'=>0,'sku_mentions'=>0,'six_ids_mentions'=>0,'max_page_number'=>null];
  foreach(explode("\n",(string)$content) as $line){
   if(preg_match('/page.*\b(\d{1,3})\b/i',$line,$m)){
    $c['page_count']++;$num=(int)$m[1];
    $c['max_page_number']=max($c['max_page_number']??0,$num);
   }
   if(preg_match('/error|rejected|skipp?ed|fail|exception|invalid|discard/i',$line))$c['error_count']++;
   if(str_contains($line,'MENTTA-PACK-'))$c['sku_mentions']++;
   if(preg_match('/\b(?:21814|21818|21820|21904|21906|21908)\b/',$line))$c['six_ids_mentions']++;
  }
  $logs[]=['file_basename'=>basename($file),'bytes'=>$size,'scanned_bytes'=>strlen((string)$content),'summary'=>$c];
 }
}
echo 'MENTTA_POSSIBLE_LOCAL_LOGS '.wp_json_encode($logs,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
