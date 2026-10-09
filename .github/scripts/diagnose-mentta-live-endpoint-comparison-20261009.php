<?php
/**
 * Side-effect-free comparison of Mentta plugin product endpoint for the hidden
 * Montjam duplicate and two published, imported Montjam products.
 * No credentials, customer information, descriptions, prices or private payloads are output.
 */
if (!defined('ABSPATH') || !class_exists('WC_Mentta_Plugin') || !function_exists('wc_get_product')) {
 echo "MENTTA_ENDPOINT_AUDIT_UNAVAILABLE\n";exit(3);
}
function mendo_structure($v,$depth=0) {
 if ($v instanceof WP_REST_Response) {
  return array('class'=>'WP_REST_Response','status'=>$v->get_status(),'body'=>mendo_structure($v->get_data(),$depth+1));
 }
 if (is_wp_error($v)) return array('error'=>$v->get_error_code());
 if (is_object($v)) {
  return array('class'=>get_class($v),'data'=>mendo_structure((array)$v,$depth+1));
 }
 if (!is_array($v)) {
  if(is_scalar($v) && is_numeric($v)) return array('numeric'=>$v);
  return array('type'=>gettype($v));
 }
 if ($depth>5) return array('count'=>count($v),'structure'=>'depth limit');
 $keys=array_keys($v);
 $list=!$v || $keys===range(0,count($v)-1);
 if($list) return array('list_count'=>count($v),'sample'=>array_map(
  static function($x)use($depth){return mendo_structure($x,$depth+1);},
  array_slice($v,0,2)
 ));
 $out=array('keys'=>array_slice(array_map('strval',$keys),0,55));
 foreach($v as $key=>$child) {
  if(preg_match('/^(?:id|product_?id|parent_?id|status|visibility|catalog_?visibility|is_?visible|published|in_?stock|per_?page|page|total|total_pages|categories|variations|products|items|results|data)$/i',(string)$key)){
   if(is_array($child) || is_object($child))$out['field_'.$key]=mendo_structure($child,$depth+1);
   elseif(is_scalar($child))$out['field_'.$key]=$child;
  }
 }
 return $out;
}
$results=array();
foreach(array(14630,14287,17129) as $id){
 $product=wc_get_product($id);
 $q=new WC_Product_Query(array('limit'=>50,'orderby'=>'id','order'=>'ASC',
  'status'=>'publish','page'=>1,'paginate'=>true,'include'=>array($id)));
 $res=$q->get_products();
 $found=array();
 foreach($res->products as $p)$found[]=(int)$p->get_id();
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$id);
 try {
  $api=WC_Mentta_Plugin::get_products($req);
  $resp=mendo_structure($api);
 } catch(Throwable $error){
  $resp=array('exception_class'=>get_class($error),'error_type'=>'plugin product endpoint invocation failed');
 }
 $results[]=array('id'=>$id,'catalog_visibility'=>$product->get_catalog_visibility(),
  'published'=>$product->get_status()==='publish','woo_query_ids'=>$found,
  'woo_query_total'=>$res->total,'endpoint_summary'=>$resp);
}
echo "MENTTA_ENDPOINT_COMPARISON ".wp_json_encode($results,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
