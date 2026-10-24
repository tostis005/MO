<?php
if(!defined('ABSPATH')||!class_exists('MDO_Database'))throw new RuntimeException('WordPress/EMDO required');
$h=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($h,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong production');
global $wpdb;
$st=MDO_Database::table('source_products');$sup=MDO_Database::table('suppliers');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Wrong supplier');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,source_url,source_payload,status,wc_product_id FROM {$st} WHERE supplier_id=%d AND status='pending' ORDER BY id",(int)$s['id']),ARRAY_A);
$report=[];
foreach($rows as $r){
  $path=trim((string)wp_parse_url((string)$r['source_url'],PHP_URL_PATH),'/');
  if(!preg_match('~^producto/([a-z0-9-]+)$~',$path,$match))throw new RuntimeException('Unexpected URL format '.$path);
  $url='https://quesospajarete.com/wp-json/wc/store/v1/products?slug='.rawurlencode($match[1]);
  $res=wp_remote_get($url,['timeout'=>40,'headers'=>['Accept'=>'application/json']]);
  if(is_wp_error($res)||200!==(int)wp_remote_retrieve_response_code($res)){
    $report[]=['source_id'=>$r['id'],'error'=>is_wp_error($res)?$res->get_error_message():wp_remote_retrieve_response_code($res)];continue;
  }
  $api=json_decode((string)wp_remote_retrieve_body($res),true);
  if(!is_array($api)||count($api)!==1||!is_array($api[0])){
    $report[]=['source_id'=>$r['id'],'error'=>'ambiguous_slug'];continue;
  }
  $item=$api[0];$payload=json_decode((string)$r['source_payload'],true);$payload=is_array($payload)?$payload:[];
  $norm=static fn($html)=>trim((string)preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$html),ENT_QUOTES|ENT_HTML5,'UTF-8')));
  $long=$norm((string)($item['description']??''));$src_long=$norm((string)($payload['description']??''));
  $a_images=array_map(static fn($v)=>is_array($v)?($v['src']??''):'',(array)($item['images']??[]));
  $short=$norm((string)($item['short_description']??''));
  $src_short=$norm((string)($payload['short_description']??''));
  $report[]=[
    'source_id'=>(int)$r['id'],'name'=>$r['title'],'source_status'=>$r['status'],'linked'=>(int)$r['wc_product_id'],
    'api_product_id'=>$item['id']??null,'api_name'=>$item['name']??null,'api_type'=>$item['type']??null,
    'api_is_purchasable'=>$item['is_purchasable']??null,'api_is_in_stock'=>$item['is_in_stock']??null,
    'api_prices'=>$item['prices']??null,'scraped_price'=>$payload['price']??null,
    'api_short_chars'=>mb_strlen($short),'scraped_short_chars'=>mb_strlen($src_short),
    'api_long_chars'=>mb_strlen($long),'scraped_long_chars'=>mb_strlen($src_long),
    'long_same'=>$long===$src_long,'short_same'=>$short===$src_short,
    'api_images'=>count(array_filter($a_images)), 'scraped_images'=>count((array)($payload['images']??[])),
    'variant_count'=>count((array)($payload['variations']??[])),
    'source_categories'=>$item['categories']??[],
    'scraped_long_start'=>mb_substr($src_long,0,180),
    'api_long_start'=>mb_substr($long,0,180)
  ];
}
echo 'PAJARETE_PENDING_COMPARE='.wp_json_encode(['count'=>count($report),'products'=>$report],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
