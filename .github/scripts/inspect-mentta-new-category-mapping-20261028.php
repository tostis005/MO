<?php
// Read-only compare existing Mentta category mapping with new category IDs.
if (!defined('ABSPATH') || !class_exists('WC_Mentta_Plugin')) exit(2);
if (!defined('REST_REQUEST')) define('REST_REQUEST',true);
$slugs=['mentta','jamones-paletas','mentta-jamones-paletas','mentta-carnes','mentta-cafes'];
foreach ($slugs as $slug) {
  $term=get_term_by('slug',$slug,'product_cat');
  echo 'CAT_COMPARE '.wp_json_encode(['slug'=>$slug,'id'=>$term?(int)$term->term_id:null,
    'parent'=>$term?(int)$term->parent:null,'count'=>$term?(int)$term->count:null],JSON_UNESCAPED_UNICODE)."\n";
}
$methods=array_values(array_filter(get_class_methods('WC_Mentta_Plugin'),static fn($n)=>stripos($n,'categor')!==false));
echo 'MENTTA_CATEGORY_METHODS '.wp_json_encode($methods)."\n";
foreach (['get_categories','get_product_categories'] as $m) {
 if (!method_exists('WC_Mentta_Plugin',$m)) continue;
 try {
  $req=new WP_REST_Request('GET','/mentta_marketplace/categories');
  $resp=WC_Mentta_Plugin::$m($req);
  $raw=$resp instanceof WP_REST_Response?$resp->get_data():$resp;
  $data=is_object($raw)?($raw->data??[]):(is_array($raw)?($raw['data']??$raw):[]);
  $found=[];$count=0;
  foreach ((array)$data as $cat) {
   $c=(array)$cat;
   $id=(int)($c['Id']??$c['id']??0);
   $count++;
   if (in_array($id,[429,149,432,539,540],true))
      $found[]=['id'=>$id,'parent'=>$c['ParentId']??$c['Parent']??$c['parent']??null,'name'=>$c['Name']??$c['name']??''];
  }
  echo 'CATEGORY_FEED_RESULT '.wp_json_encode(['method'=>$m,'response_type'=>gettype($raw),'count'=>$count,'matched'=>$found],JSON_UNESCAPED_UNICODE)."\n";
 } catch(Throwable $e) { echo 'CATEGORY_FEED_ERROR '.get_class($e).' '.substr($e->getMessage(),0,130)."\n"; }
}
