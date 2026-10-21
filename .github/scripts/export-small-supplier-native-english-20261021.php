<?php
/**
 * Read-only export for 13 (or fewer) buyable, source-linked Isabel + Latitudes
 * products missing complete native EN metadata. Never writes production.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||
   !class_exists('WooCommerce')||!class_exists('MDO_Database')) throw new RuntimeException('WP CLI WooCommerce expected');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$result=['mode'=>'read-only','generated'=>gmdate('c'),'missing'=>[],'by_supplier'=>[]];
foreach(['chocolates-artesanos-isabel','latitudes'] as $code){
  $vendor=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s",$code),ARRAY_A);
  if(!$vendor)throw new RuntimeException('Supplier not found: '.$code);
  $rows=$wpdb->get_results($wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,source_payload FROM {$sp} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",
    (int)$vendor['id']),ARRAY_A);
  $found=0;$seen=[];
  foreach($rows as $r){
    $id=(int)$r['wc_product_id'];if(isset($seen[$id]))continue;$seen[$id]=true;
    $p=wc_get_product($id);
    if(!$p || $p->get_status()!=='publish'||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock()||
       (int)get_post_field('post_author',$id)!==(int)$vendor['vendor_user_id'])continue;
    $english=[
     'published'=>(string)get_post_meta($id,'_en_US_published',true),
     'title'=>(string)get_post_meta($id,'_en_US_post_title',true),
     'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
     'short'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),
     'long'=>(string)get_post_meta($id,'_en_US_post_content',true)];
    $plain=static fn($x)=>trim(wp_strip_all_tags((string)$x));
    $missing= $english['published']!=='1'||!$plain($english['title'])||!$english['slug']||
      ($plain($p->get_short_description())!==''&&!$plain($english['short']))||
      ($plain($p->get_description())!==''&&!$plain($english['long']));
    if(!$missing)continue;
    $found++;
    $payload=json_decode((string)$r['source_payload'],true);
    $payload=is_array($payload)?$payload:[];
    $entry=[
      'supplier'=>$code,'id'=>$id,'source_id'=>(int)$r['id'],
      'sku'=>(string)$p->get_sku(),'title'=>(string)$p->get_name(),
      'url'=>(string)get_permalink($id),'es_short'=>(string)$p->get_short_description(),
      'es_full'=>(string)$p->get_description(),
      'source_title'=>(string)$r['title'],'source_url'=>(string)$r['source_url'],
      'source_composition'=>(string)($payload['ingredients']??''),
      'variations'=>$p->is_type('variable')?count($p->get_children()):0,
      'source_english'=>$english
    ];
    $result['missing'][]=$entry;
    // Bounded output per product avoids huge GitHub Actions log truncation.
    $log=$entry;
    $log['es_full']=mb_substr((string)wp_strip_all_tags($entry['es_full']),0,5500);
    $log['es_short']=mb_substr((string)wp_strip_all_tags($entry['es_short']),0,1400);
    $log['source_english']['long']=mb_substr((string)wp_strip_all_tags($english['long']),0,600);
    echo 'EMDO_EN_HAND_REVIEW='.wp_json_encode($log,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
  }
  $result['by_supplier'][$code]=$found;
}
echo 'EMDO_EN_HAND_REVIEW_SUMMARY='.wp_json_encode(['total'=>count($result['missing']),'suppliers'=>$result['by_supplier']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
