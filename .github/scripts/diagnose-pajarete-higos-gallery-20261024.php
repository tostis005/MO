<?php
if(!defined('ABSPATH')||!class_exists('MDO_Database'))throw new RuntimeException('Need WP');
$h=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($h,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))throw new RuntimeException('Wrong site');
global $wpdb;$table=MDO_Database::table('source_products');
$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1",1083),ARRAY_A);
if(!$r||!$r['wc_product_id']||(string)$r['status']!=='active')throw new RuntimeException('Source missing');
$p=wc_get_product((int)$r['wc_product_id']);if(!$p||(int)get_post_field('post_author',$p->get_id())!==4974)throw new RuntimeException('Wrong owner');
$payload=json_decode($r['source_payload'],true);
$res=wp_remote_get('https://quesospajarete.com/wp-json/wc/store/v1/products?slug=mermelada-de-higos-negros-con-nueces-de-macadamia-y-pimiento-de-jamaica-can-bech',['timeout'=>40]);
if(is_wp_error($res)||200!==(int)wp_remote_retrieve_response_code($res))throw new RuntimeException('Source unavailable');
$api=json_decode((string)wp_remote_retrieve_body($res),true);
if(!is_array($api)||count($api)!==1)throw new RuntimeException('Unexpected API response');
$images=(array)($api[0]['images']??[]);
$map=json_decode((string)get_post_meta($p->get_id(),'_emdo_image_map',true),true);
$report=[];
foreach($images as $i=>$v){
 $u=(string)($v['src']??'');
 $aid=(int)($map[$u]??0);
 $report[]=['position'=>$i,'id'=>$v['id']??null,'name'=>$v['name']??null,'alt'=>$v['alt']??null,
  'src'=>$u,'file_path'=>(string)wp_parse_url($u,PHP_URL_PATH),
  'attachment_id'=>$aid,'url_used_by_payload'=>in_array($u,(array)($payload['images']??[]),true),
  'duplicate_url'=>array_count_values(array_column($images,'src'))[$u]??null];
}
echo 'PAJARETE_HIGOS_IMAGE_DIAG='.wp_json_encode([
 'woo_id'=>$p->get_id(),'gallery_size'=>count($p->get_gallery_image_ids()),
 'official_count'=>count($images),'verified_payload_image_urls'=>$payload['images']??[],
 'official'=>$report
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
