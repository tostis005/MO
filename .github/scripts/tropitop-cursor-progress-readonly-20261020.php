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

$retry_attempts=(array)($state['retry_attempts']??array());
$retry_attempted=0;$retry_exhausted=0;$retry_remaining=0;$retry_total=0;
$retry_recovered=0;$failure_types=array();
$failure_exact_reasons=array();
foreach($retry_attempts as $id=>$attempt){
    $n=(int)$attempt;$retry_total+=$n;
    if($n>0){$retry_attempted++;}
    if(!isset($failed[$id]) && $n>0){$retry_recovered++;}
}
foreach($failed as $id=>$entry){
    $n=(int)($retry_attempts[$id]??0);
    if($n>=2){$retry_exhausted++;}
    elseif(in_array((string)($entry['url']??''),$urls,true)){$retry_remaining++;}
    $exact=mb_substr(trim((string)($entry['reason']??'')),0,140);
    $failure_exact_reasons[$exact]=($failure_exact_reasons[$exact]??0)+1;
    $error=mb_strtolower((string)($entry['reason']??''),'UTF-8');
    $kind='other';
    if(str_contains($error,'timeout')||str_contains($error,'timed out')||str_contains($error,'cURL error 28')){$kind='timeout';}
    elseif(str_contains($error,'categoría')||str_contains($error,'category')){$kind='category_variant_url';}
    elseif(str_contains($error,'matriz')||str_contains($error,'variaci')||str_contains($error,'variante')){$kind='price_or_variants';}
    elseif(str_contains($error,'título')||str_contains($error,'página vacía')||str_contains($error,'http ')){$kind='page_unavailable';}
    elseif(str_contains($error,'peso')||str_contains($error,'shipping')||str_contains($error,'unidades')){$kind='unverified_shipping_weight';}
    elseif(str_contains($error,'qa woo')||str_contains($error,'galería')||str_contains($error,'foto')){$kind='woo_image_or_qa';}
    $failure_types[$kind]=($failure_types[$kind]??0)+1;
}
ksort($failure_types);
arsort($failure_exact_reasons,SORT_NUMERIC);

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
  'failed_reason_counts'=>$failure_types,
  'exact_failure_reason_counts'=>$failure_exact_reasons,
  'retry_attempted_distinct_products'=>$retry_attempted,
  'retry_attempts_total'=>$retry_total,
  'retry_recovered'=>$retry_recovered,
  'retry_remaining'=>$retry_remaining,
  'retry_exhausted'=>$retry_exhausted,
  'failed_sample'=>array_slice($failed,0,5,true),
  'last_processed_source_url'=>$last_url,'next_source_url'=>$next_url,
  'wp_linked_products'=>$linked,'source_status_counts'=>$by_status,
  'woo_status_counts'=>$woo_stats,'last_created_woo_product'=>$last_woo,
  'finished_at'=>$state['finished_at']??null,
);
echo 'TROPITOP_CURSOR_STATUS='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
