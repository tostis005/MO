<?php
/** Production read-only stock probe for Isabel, including current remote stock. */
if(!defined('ABSPATH'))exit(1);
$h=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));if(!in_array($h,array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true))throw new RuntimeException('production only');
global $wpdb;
$s=MDO_Database::table('suppliers');$t=MDO_Database::table('source_products');$run=MDO_Database::table('sync_runs');
$v=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$s} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'),ARRAY_A);if(!$v)throw new RuntimeException('supplier absent');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,source_url,source_stock_status,status,wc_product_id,source_hash,source_payload,last_seen_at FROM {$t} WHERE supplier_id=%d ORDER BY id",(int)$v['id']),ARRAY_A);
$results=array('supplier_id'=>(int)$v['id'],'frequency'=>$v['sync_frequency'],'last_sync_at'=>$v['last_sync_at'],
 'total'=>count($rows),'statuses'=>array(),'wc_stock'=>array(),'turrones'=>array(),'mismatches'=>array(),'source_missing_data'=>array(),
 'last_runs'=>array(),'live_rechecks'=>array(),'isabel_scheduler_pending'=>null);
foreach((array)$rows as $r){
 $results['statuses'][(string)$r['source_stock_status']]=($results['statuses'][(string)$r['source_stock_status']]??0)+1;
 $pid=(int)$r['wc_product_id'];$p=$pid?wc_get_product($pid):false;
 if($p){
  $stock=$p->get_stock_status('edit');
  $results['wc_stock'][$stock]=($results['wc_stock'][$stock]??0)+1;
  if($stock!==$r['source_stock_status']&&$r['status']==='active')$results['mismatches'][]=array('id'=>$r['id'],'wc_id'=>$pid,'title'=>$p->get_name(),'source'=>$r['source_stock_status'],'wc'=>$stock);
 }
 if(preg_match('/turr[oó]n/ui',$r['title'])||str_contains($r['source_url'],'turron')){
  $results['turrones'][]=array('id'=>(int)$r['id'],'title'=>mb_substr($r['title'],0,92),'source'=>$r['source_stock_status'],'woo'=>$p?$p->get_stock_status('edit'):null,'url'=>$r['source_url'],'visible'=>$p?$p->get_catalog_visibility():null,'published'=>$p?$p->get_status():null);
 }
 if($r['source_stock_status']==='unknown')$results['source_missing_data'][]=array('id'=>$r['id'],'url'=>$r['source_url']);
}
$results['last_runs']=$wpdb->get_results($wpdb->prepare("SELECT id,status,products_found,products_new,products_updated,errors_count,started_at,finished_at,message FROM {$run} WHERE supplier_id=%d ORDER BY id DESC LIMIT 5",(int)$v['id']),ARRAY_A);
if(function_exists('as_get_scheduled_actions')){
 $pending=as_get_scheduled_actions(array('hook'=>'mdo_supplier_sync_scrape_product','status'=>ActionScheduler_Store::STATUS_PENDING,'per_page'=>150),'ids');
 $results['isabel_scheduler_pending']=count($pending);
}
$subset=array_slice(array_filter($rows,static fn($r)=>preg_match('/turr[oó]n/ui',$r['title'])),0,5);
$other=array_slice(array_filter($rows,static fn($r)=>$r['source_stock_status']==='instock'),0,2);
foreach(array_merge($subset,$other) as $r){
 $item=array('id'=>(int)$r['id'],'url'=>$r['source_url'],'source_saved'=>$r['source_stock_status']);
 try{
  $actual=MDO_Connector_Chocolates_Isabel::scrape_product($r['source_url']);
  $item['scraped']=$actual['stock_status']??null;
  $item['price']=$actual['price']??null;
 }catch(Throwable $e){$item['error']=mb_substr($e->getMessage(),0,220);}
 $results['live_rechecks'][]=$item;
}
echo 'ISABEL_STOCK_DIAGNOSTIC='.wp_json_encode($results,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
