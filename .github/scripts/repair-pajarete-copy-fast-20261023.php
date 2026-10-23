<?php
/** Apply verified Pajarete official descriptions without waiting for slow image sideloads. */
if(!defined('ABSPATH')||!class_exists('MDO_Database')||!class_exists('WooCommerce')){throw new RuntimeException('WP required');}
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)){throw new RuntimeException('Production only');}
global $wpdb;
$suppliers=MDO_Database::table('suppliers');
$products=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974||!get_user_meta(4974,'_disable_vendor',true)){throw new RuntimeException('Vendor disabled guard failed');}
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_url,source_payload FROM {$products} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)!==130){throw new RuntimeException('Expected exactly 130 products.');}
$valid=[];
foreach($rows as $r){
  $id=(int)$r['wc_product_id'];$p=wc_get_product($id);
  if(!$p||(int)get_post_field('post_author',$id)!==4974||$p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden'){throw new RuntimeException('Unsafe Woo item '.$id);}
  if(strtolower((string)wp_parse_url((string)$r['source_url'],PHP_URL_HOST))!=='quesospajarete.com'){throw new RuntimeException('Wrong source origin '.$id);}
  $data=json_decode((string)$r['source_payload'],true);
  $short=wp_kses_post((string)($data['short_description']??''));
  $long=wp_kses_post((string)($data['description']??''));
  if(mb_strlen(trim(wp_strip_all_tags($short)))<8||mb_strlen(trim(wp_strip_all_tags($long)))<30||
     empty($data['source_api_product_id'])||empty($data['images'])){
    throw new RuntimeException('Unverified Store API payload '.$id);
  }
  $valid[$id]=array('short'=>$short,'long'=>$long);
}
$updated=0;
foreach($valid as $id=>$v){
  $post=get_post($id);
  if((string)$post->post_excerpt!==$v['short']||(string)$post->post_content!==$v['long']){
    $changed=$wpdb->update($wpdb->posts,array('post_excerpt'=>$v['short'],'post_content'=>$v['long']),
      array('ID'=>$id),array('%s','%s'),array('%d'));
    if(false===$changed){throw new RuntimeException('Could not update copy for '.$id);}
    $updated++;
    clean_post_cache($id);
    if(function_exists('wc_delete_product_transients'))wc_delete_product_transients($id);
  }
}
$check=array('total'=>130,'short'=>0,'long'=>0,'published'=>0,'hidden_draft'=>0);
foreach($valid as $id=>$v){
  $p=wc_get_product($id);
  if(trim(wp_strip_all_tags($p->get_short_description())))$check['short']++;
  if(trim(wp_strip_all_tags($p->get_description())))$check['long']++;
  if($p->get_status()==='publish')$check['published']++;
  if($p->get_status()==='draft'&&$p->get_catalog_visibility()==='hidden')$check['hidden_draft']++;
}
echo 'PAJARETE_COPY_REPAIR='.wp_json_encode(array('updated'=>$updated,'counts'=>$check),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($check['short']!==130||$check['long']!==130||$check['published']!==0||$check['hidden_draft']!==130){
  throw new RuntimeException('Copy repair validation failed.');
}
