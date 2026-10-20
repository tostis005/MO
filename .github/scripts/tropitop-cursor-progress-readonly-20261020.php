<?php
/** Read-only production reconciliation of Tropitop's importer cursor and Woo publication. */
if(!defined('ABSPATH') || !function_exists('wc_get_product') || !class_exists('MDO_Database')){
    throw new RuntimeException('WooCommerce or EMDO missing');
}
foreach(array('siteurl','home') as $opt){
    $host=strtolower((string)wp_parse_url((string)get_option($opt), PHP_URL_HOST));
    if(!in_array($host,array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true)){
        throw new RuntimeException('Not El Mercado de Origen production');
    }
}
global $wpdb;
$sup=MDO_Database::table('suppliers');
$src=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,active,vendor_user_id FROM {$sup} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$supplier){throw new RuntimeException('Tropitop supplier not found');}
$source_id=(int)$supplier['id'];
$vendor_id=(int)$supplier['vendor_user_id'];
$state=get_option('mdo_tropitop_full_catalog_20261020',array());
$state=is_array($state)?$state:array();
$urls=(array)($state['urls']??array());
$total=count($urls);
$cursor=(int)($state['cursor']??0);
$done=max(0,min($total,$cursor));
$imported=(array)($state['imported']??array());
$failed=(array)($state['failed']??array());
$source_count=$wpdb->get_results($wpdb->prepare(
    "SELECT status,COUNT(*) AS cnt, SUM(CASE WHEN wc_product_id>0 THEN 1 ELSE 0 END) AS linked
     FROM {$src} WHERE supplier_id=%d GROUP BY status",$source_id),ARRAY_A);
$by_status=array();
$linked=0;
foreach((array)$source_count as $r){$by_status[(string)$r['status']]=(int)$r['cnt'];$linked+=(int)$r['linked'];}
$existing_ids=$wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT wc_product_id FROM {$src} WHERE supplier_id=%d AND wc_product_id>0",$source_id));
$woo_stats=array('publish'=>0,'draft'=>0,'pending'=>0,'private'=>0,'other'=>0,'missing'=>0,'with_cover'=>0,'with_gallery'=>0);
$last_woo=null;
foreach((array)$existing_ids as $id){
    $id=(int)$id;$p=wc_get_product($id);
    if(!$p){$woo_stats['missing']++;continue;}
    $s=$p->get_status();$woo_stats[isset($woo_stats[$s])?$s:'other']++;
    if($p->get_image_id())$woo_stats['with_cover']++;
    if(count($p->get_gallery_image_ids())>0)$woo_stats['with_gallery']++;
    if(!$last_woo || $id>$last_woo['product_id']){
        $last_woo=array('product_id'=>$id,'name'=>$p->get_name(),
            'status'=>$s,'cover'=>(bool)$p->get_image_id(),'gallery_count'=>count($p->get_gallery_image_ids()));
    }
}
$last_url=$done>0?(string)($urls[$done-1]??''):'';
$next_url=$done<$total?(string)($urls[$done]??''):'';
$out=array(
  'checked_at'=>gmdate('c'),
  'supplier_active'=>(bool)$supplier['active'],
  'seller_disabled'=>$vendor_id?(bool)get_user_meta($vendor_id,'_disable_vendor',true):null,
  'shipping_verified'=>class_exists('MDO_Tropitop_Shipping')?MDO_Tropitop_Shipping::is_ready():null,
  'total_source_urls'=>$total, 'cursor'=>$done,'remaining_first_pass'=>max(0,$total-$done),
  'completed_first_pass_percent'=>$total?round(100*$done/$total,1):0,
  'imported_approved_in_cursor'=>count($imported),
  'failed_recorded'=>count($failed),
  'failed_sample'=>array_slice($failed,0,5,true),
  'last_processed_source_url'=>$last_url,'next_source_url'=>$next_url,
  'wp_linked_products'=>$linked,'source_status_counts'=>$by_status,
  'woo_status_counts'=>$woo_stats,'last_created_woo_product'=>$last_woo,
  'finished_at'=>$state['finished_at']??null,
);
echo 'TROPITOP_CURSOR_STATUS='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
