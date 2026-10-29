<?php
/** Read-only audit of Mentta connector output for 1957 new campaign 26/27. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')){fwrite(STDERR,"ABORT_WP_WC_REQUIRED\n");exit(2);}
if(!class_exists('WC_Mentta_Plugin')){fwrite(STDERR,"ABORT_MENTTA_CONNECTOR_MISSING\n");exit(3);}
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)){fwrite(STDERR,"ABORT_HOST\n");exit(4);}
$v=get_user_by('login','1957');
if(!$v)$v=get_user_by('slug','1957');
if(!$v){fwrite(STDERR,"ABORT_1957_VENDOR\n");exit(5);}
$root=get_term_by('slug','mentta','product_cat');
$child=get_term_by('slug','mentta-aceites','product_cat');
if(!$root||!$child||(int)$child->parent!==(int)$root->term_id){fwrite(STDERR,"ABORT_MENTTA_TERMS\n");exit(6);}
$ids=get_posts(['post_type'=>'product','post_status'=>'publish','author'=>(int)$v->ID,'posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true]);
$targets=[];$benchmarks=[];
foreach($ids as $id){
 $p=wc_get_product($id);if(!$p)continue;
 $name=$p->get_name();
 if(preg_match('/COSECHA\\s*26\\/27/u',$name))$targets[]=$id;
 elseif(preg_match('/25\\/26/u',$name))$benchmarks[]=$id;
}
if(count($targets)!==3){fwrite(STDERR,"ABORT_NEW_CAMPAIGN_COUNT=".count($targets)."\n");exit(7);}
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
foreach(array_merge($targets,$benchmarks) as $id){
 $p=wc_get_product($id);
 $cats=array_map('intval',$p->get_category_ids());
 $r=new WP_REST_Request('GET','/mentta_marketplace/products');
 $r->set_param('product_id',$id);
 $old_uri=$_SERVER['REQUEST_URI']??'';
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
 $status=null;$returned=[];$error='';
 try{
  $out=WC_Mentta_Plugin::get_products($r);
  $status=($out instanceof WP_REST_Response)?$out->get_status():null;
  $body=($out instanceof WP_REST_Response)?$out->get_data():[];
  $data=is_object($body)?($body->data??[]):(is_array($body)?($body['data']??[]):[]);
  foreach((array)$data as $row){if(is_array($row)&&isset($row['Id']))$returned[]=(int)$row['Id'];}
 }catch(Throwable $e){$error=get_class($e).':'.substr($e->getMessage(),0,100);}
 $_SERVER['REQUEST_URI']=$old_uri;
 echo 'MENTTA_EXPORT_CHECK='.wp_json_encode([
  'id'=>(int)$id,'campaign'=>in_array($id,$targets,true)?'26/27':'25/26',
  'name'=>$p->get_name(),'categories_ok'=>in_array((int)$root->term_id,$cats,true)&&in_array((int)$child->term_id,$cats,true),
  'status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility(),
  'connector_response_status'=>$status,'returned_ids'=>$returned,
  'in_connector_response'=>in_array((int)$id,$returned,true),'error'=>$error
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
echo "MENTTA_1957_EXPORT_AUDIT_COMPLETE\n";
