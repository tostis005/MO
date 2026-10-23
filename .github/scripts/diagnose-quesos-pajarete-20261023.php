<?php
if (!defined('ABSPATH')) { throw new RuntimeException('WordPress required'); }
$host = strtolower((string)wp_parse_url((string)get_option('siteurl'), PHP_URL_HOST));
if (!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)) { throw new RuntimeException('Wrong site'); }
$checks=array(
  'apex_product'=>'https://quesospajarete.com/producto/semicurado-de-cabra/',
  'www_product'=>'https://www.quesospajarete.com/producto/semicurado-de-cabra/',
  'http_product'=>'http://quesospajarete.com/producto/semicurado-de-cabra/',
  'store_api'=>'https://quesospajarete.com/wp-json/wc/store/v1/products?per_page=1',
  'rest_route'=>'https://quesospajarete.com/?rest_route=/wc/store/v1/products&per_page=1',
  'image'=>'https://quesospajarete.com/wp-content/uploads/2024/06/CURADO-MEDIO-1.png',
  'apex_home'=>'https://quesospajarete.com/'
);
foreach ($checks as $label=>$url) {
  $result=wp_remote_get($url,array(
    'timeout'=>10,'redirection'=>0,'sslverify'=>true,
    'headers'=>array('Accept'=>'text/html,application/json,image/avif,image/webp,*/*'),
    'user-agent'=>'Mozilla/5.0 (compatible; EMDO-Catalog-Audit/1.0)'
  ));
  if(is_wp_error($result)){
    $out=array('label'=>$label,'error'=>$result->get_error_message());
  } else {
    $out=array(
      'label'=>$label,
      'status'=>(int)wp_remote_retrieve_response_code($result),
      'content_type'=>wp_remote_retrieve_header($result,'content-type'),
      'location'=>wp_remote_retrieve_header($result,'location'),
      'bytes'=>strlen((string)wp_remote_retrieve_body($result)),
      'body_first'=>mb_substr(trim(wp_strip_all_tags((string)wp_remote_retrieve_body($result))),0,180)
    );
  }
  echo 'PAJARETE_ROUTE='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
