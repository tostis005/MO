<?php
/**
 * Read-only audit: actual Mentta discovery source + requested ID/full listing difference.
 * No passwords, customer data, access tokens, private endpoints or full descriptions output.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WC_Mentta_Plugin'))exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$file=WP_PLUGIN_DIR.'/mentta-marketplace/mentta-marketplace.php';
if(!is_readable($file))exit(4);
$lines=file($file,FILE_IGNORE_NEW_LINES);
foreach([[385,525],[620,700]] as $win){
 echo "MENTTA_PLUGIN_WINDOW {$win[0]}-{$win[1]}\n";
 for($i=$win[0];$i<=$win[1]&&$i<=count($lines);$i++){
  $line=trim((string)$lines[$i-1]);
  if(preg_match('/secret|token|pass|authorization|consumer|cookie|api[_-]?key|license|private[_-]?key|password|nonce|customer|https?:\/\//i',$line)){
   echo $i.": [filtered]\n";continue;
  }
  echo $i.": ".substr(preg_replace('/\s+/',' ',$line),0,230)."\n";
 }
}
$ids=[21904,21906,21908,21814,21818,21820,17129,14287];
$root=get_term_by('slug','mentta','product_cat');
$cats=[];
if($root){
 $terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>$root->term_id]);
 foreach((array)$terms as $term)if($term instanceof WP_Term && in_array($term->slug,['mentta-carnes','mentta-cafes'],true))$cats[$term->slug]=(int)$term->term_id;
}
$out=[];
foreach($ids as $id){
 $p=wc_get_product($id);
 if(!$p){$out[]=['id'=>$id,'exists'=>false];continue;}
 $excluded=function_exists('emdo_mentta_exclusive_ids')&&in_array($id,emdo_mentta_exclusive_ids(),true);
 $cat_ids=array_map('intval',$p->get_category_ids());
 $excluded_terms=wp_get_object_terms($id,'product_visibility',['fields'=>'slugs']);
 $out[]=['id'=>$id,'type'=>$p->get_type(),'published'=>$p->get_status()==='publish',
  'visibility'=>$p->get_catalog_visibility(),'is_visible_in_cli'=>$p->is_visible(),
  'is_in_stock'=>$p->is_in_stock(),'price'=>(float)wc_get_price_including_tax($p),
  'sku_present'=>(bool)$p->get_sku(),'categories'=>$cat_ids,
  'explicit_mentta_root'=>$root?in_array($root->term_id,$cat_ids,true):false,
  'explicit_child'=>array_values(array_intersect($cat_ids,array_values($cats))),
  'public_excluded'=>$excluded,'visibility_terms'=>is_wp_error($excluded_terms)?[]:$excluded_terms,
  'date_modified_gmt'=>get_post_modified_time('Y-m-d H:i:s',true,$id),
  'variation_count'=>$p->is_type('variable')?count($p->get_children()):0];
}
echo "PACK_VS_EXISTING_CATALOG ".wp_json_encode(['categories'=>$cats,'records'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products';
foreach([['sample_page10',['page'=>10]],['sample_page11',['page'=>11]],['sample_page12',['page'=>12]],['sample_page13',['page'=>13]],['sample_page_size',['page'=>1,'page_size'=>100]]] as $case){
 [$name,$params]=$case;
 $request=new WP_REST_Request('GET','/mentta_marketplace/products');
 foreach($params as $param=>$val)$request->set_param($param,$val);
 try{
  $response=WC_Mentta_Plugin::get_products($request);
  if(!($response instanceof WP_REST_Response)){echo "MENTTA_LIST_CHECK ".wp_json_encode(['case'=>$name,'type'=>gettype($response)])."\n";continue;}
  $body=$response->get_data();
  $data=is_object($body)?($body->data??[]):(is_array($body)?($body['data']??[]):[]);
  $items=is_array($data)?$data:[];
  $resultIds=[];foreach($items as $item)if(is_array($item)&&isset($item['Id']))$resultIds[]=(int)$item['Id'];
  $hits=array_values(array_intersect($ids,$resultIds));
  $meta=is_object($body)?(array)$body:(is_array($body)?$body:[]);
  unset($meta['data']);
  $safeMeta=[];foreach($meta as $key=>$v)if(preg_match('/count|total|page|perpage|pages|status/i',(string)$key))
    $safeMeta[$key]=is_scalar($v)?$v:gettype($v);
  echo 'MENTTA_LIST_CHECK '.wp_json_encode(['case'=>$name,'status'=>$response->get_status(),
   'response_meta'=>$safeMeta,'returned_count'=>count($resultIds),
   'id_min'=>$resultIds?min($resultIds):null,'id_max'=>$resultIds?max($resultIds):null,
   'tested_ids_found'=>$hits,'sample_ids'=>array_slice($resultIds,0,15)],
   JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }catch(Throwable $e){echo 'MENTTA_LIST_ERROR '.wp_json_encode(['case'=>$name,'type'=>get_class($e),'message'=>substr($e->getMessage(),0,180)])."\n";}
}
