<?php
if (!defined('ABSPATH')) exit(1);
global $wpdb;
$supplier_id=5;
$table=MDO_Database::table('source_products');
$rows=$wpdb->get_results($wpdb->prepare(
 "SELECT id,wc_product_id,title,status,source_url,source_stock_status,last_error FROM {$table} WHERE supplier_id=%d ORDER BY id ASC",
 $supplier_id
),ARRAY_A);
$out=[];
$counts=[];
foreach($rows as $r){
 $wc=(int)($r['wc_product_id']??0);
 $post_status=$wc?get_post_status($wc):null;
 $cats=[];
 if($wc){
  $ts=wp_get_post_terms($wc,'product_cat');
  if(!is_wp_error($ts)) foreach($ts as $t) $cats[]=$t->slug;
 }
 $st=(string)$r['status']; $counts[$st]=($counts[$st]??0)+1;
 $out[]=array(
  'source_id'=>(int)$r['id'],'wc_product_id'=>$wc,'source_status'=>$st,'post_status'=>$post_status,
  'title'=>$r['title'],'source_url'=>$r['source_url'],'source_stock_status'=>$r['source_stock_status'],
  'categories'=>$cats,'last_error'=>$r['last_error']
 );
}
ksort($counts);
$supplier=MDO_Supplier_Repository::find($supplier_id);
echo wp_json_encode(array(
 'batch'=>'20261001-selectos-source-status-audit',
 'supplier_id'=>$supplier_id,
 'source_counts'=>$counts,
 'supplier_exclusion_url_fragments'=>$supplier['exclusion_url_fragments']??null,
 'rows'=>$out
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
