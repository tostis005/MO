<?php
/** One-shot, audited Latitudes SKU weights and WCFM zone 10 free shipping. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
foreach (['home','siteurl'] as $opt) {
    $h=strtolower((string)wp_parse_url(get_option($opt),PHP_URL_HOST));
    if(!in_array($h,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) WP_CLI::error('Wrong WordPress host');
}
if (!class_exists('MDO_Latitudes_Logistics') || !class_exists('WCFMmp_Shipping_Zone') || !class_exists('MDO_Database') || !function_exists('wc_get_product')) WP_CLI::error('New EMDO logistics code not yet deployed');
global $wpdb;
$sup=MDO_Database::table('suppliers');
$sid=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM $sup WHERE code=%s",'latitudes'));
if ($sid !== 4949) WP_CLI::error('Unexpected seller id for Latitudes');
$vendor=get_userdata($sid);
if(!$vendor || !in_array('wcfm_vendor',(array)$vendor->roles,true)) WP_CLI::error('Latitudes not active vendor');
$units=get_option('woocommerce_weight_unit');
if($units!=='kg') WP_CLI::error('WooCommerce weight unit is not kg: '.$units);
$shipping=get_user_meta($sid,'_wcfmmp_shipping',true);
if(!is_array($shipping) || ($shipping['_wcfmmp_user_shipping_type']??'')!=='by_zone' || ($shipping['_wcfmmp_user_shipping_enable']??'')!=='yes') WP_CLI::error('WCFM not using per-vendor zone shipping');
$zone=new WC_Shipping_Zone(10);
if($zone->get_zone_name()!=='Baleares') WP_CLI::error('Zone 10 is not Baleares');
$gateways=array_map(static fn($v)=>$v->id,$zone->get_shipping_methods(true));
if(!in_array('wcfmmp_product_shipping_by_zone',$gateways,true)) WP_CLI::error('WCFM global gateway absent in Baleares');
$methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$ids=get_posts(['post_type'=>'product','post_status'=>'any','author'=>$sid,'fields'=>'ids','numberposts'=>-1,'orderby'=>'ID']);
if(count($ids)!==138) WP_CLI::error('Unexpected Latitudes catalog count '.count($ids).', expected 138');
$prepared=[];$basis=[];$unresolved=[];
foreach($ids as $id){
    $p=wc_get_product($id);
    if(!$p || !$p->is_type('simple')) { $unresolved[]=['id'=>$id,'reason'=>'missing_or_not_simple'];continue; }
    $parsed=MDO_Latitudes_Logistics::weight_from_title($p->get_name());
    if(!$parsed || ($parsed['kg']??0)<=0 || $parsed['kg']>15) { $unresolved[]=['id'=>$id,'name'=>$p->get_name()];continue; }
    $basis[$parsed['basis']]=($basis[$parsed['basis']]??0)+1;
    $prepared[(int)$id]=['kg'=>(float)$parsed['kg'],'basis'=>$parsed['basis'],'previous'=>(string)$p->get_weight('edit'),'name'=>$p->get_name()];
}
WP_CLI::log('LWS_PREFLIGHT='.wp_json_encode(['expected'=>138,'ready'=>count($prepared),'unresolved'=>$unresolved,'basis'=>$basis,'min_kg'=>min(array_column($prepared,'kg')),'max_kg'=>max(array_column($prepared,'kg'))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
if(count($prepared)!==138 || count($unresolved)) WP_CLI::error('SKU weights incomplete; stopped before any production change');
$existing=$wpdb->get_results($wpdb->prepare("SELECT * FROM $methods WHERE vendor_id=%d AND zone_id=%d",$sid,10),ARRAY_A);
$flat_count=0;$free_count=0;$free_row=null;
foreach($existing as $row) {
    if($row['method_id']==='flat_rate') {
        $flat_count++;
        $settings=maybe_unserialize($row['settings']);
        if((int)$row['is_enabled']!==1 || abs((float)($settings['cost']??0)-29.0)>0.001) WP_CLI::error('Baleares base rate not 29 EUR');
    } else if($row['method_id']==='free_shipping') {$free_count++;$free_row=$row;}
    else WP_CLI::error('Unexpected shipping method in Baleares: '.$row['method_id']);
}
if($flat_count!==1 || $free_count>1) WP_CLI::error('Unexpected native WCFM methods in Baleares');
$mode=getenv('LATITUDES_WEIGHT_MODE')?:'dry-run';
if($mode==='dry-run') { WP_CLI::success('Read-only preflight completed');exit; }
if($mode!=='apply') WP_CLI::error('Unsupported mode');
// Backup original state before changing any records, retaining for rollback.
$backup=['timestamp'=>gmdate('c'),'weights'=>$prepared,'baleares_methods'=>$existing,'old_option'=>get_option('mdo_latitudes_weight_shipping_enabled','not_set')];
if(!add_option('mdo_latitudes_shipping_backup_20261019',$backup,'','no') && !get_option('mdo_latitudes_shipping_backup_20261019')) WP_CLI::error('Backup could not be recorded');
$wpdb->query('START TRANSACTION');
try {
    foreach($prepared as $id=>$info){
        $p=wc_get_product($id);
        $p->set_weight((string)$info['kg']);
        $p->save();
        update_post_meta($id,'_mdo_latitudes_weight_basis',$info['basis']);
    }
    $free_settings=[
      'title'=>'Envío gratuito Seur Frío','description'=>'','cost'=>'0',
      'tax_status'=>'none','min_amount'=>'200'
    ];
    if($free_row) {
        $ok=$wpdb->update($methods,['is_enabled'=>1,'settings'=>maybe_serialize($free_settings)],
          ['instance_id'=>(int)$free_row['instance_id'],'vendor_id'=>$sid,'zone_id'=>10,'method_id'=>'free_shipping'],
          ['%d','%s'],['%d','%d','%d','%s']);
    } else {
        $ok=$wpdb->insert($methods,[
          'method_id'=>'free_shipping','zone_id'=>10,'vendor_id'=>$sid,
          'is_enabled'=>1,'settings'=>maybe_serialize($free_settings)
        ],['%s','%d','%d','%d','%s']);
    }
    if($ok===false) throw new RuntimeException('Could not save WCFM Baleares free shipping: '.$wpdb->last_error);
    update_option('mdo_latitudes_weight_shipping_enabled','1',false);
    foreach($prepared as $id=>$info) {
        $w=(float)wc_get_product($id)->get_weight('edit');
        if(abs($w-$info['kg'])>0.000001) throw new RuntimeException('Weight not persisted: '.$id);
    }
    $native=WCFMmp_Shipping_Zone::get_shipping_methods(10,$sid);
    $free=array_values(array_filter($native,static fn($x)=>($x['id']??'')==='free_shipping' && ($x['enabled']??'')==='yes'));
    if(count($free)!==1 || abs((float)($free[0]['settings']['min_amount']??0)-200)>0.001) throw new RuntimeException('Native WCFM Baleares 200 EUR free rate not persisted');
    $wpdb->query('COMMIT');
} catch(Throwable $e) {
    $wpdb->query('ROLLBACK');
    wp_cache_flush();
    throw $e;
}
if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('shipping',true);
wp_cache_flush();
do_action('mdo_shipping_destinations_invalidate');
$any_id=(int)array_key_first($prepared);
$cases=[
    ['region'=>'peninsula','postal'=>'28001','weight'=>0.3,'gross'=>199,'expected'=>13.45],
    ['region'=>'peninsula','postal'=>'28001','weight'=>4,'gross'=>199,'expected'=>13.45],
    ['region'=>'peninsula','postal'=>'28001','weight'=>4.01,'gross'=>199,'expected'=>16.45],
    ['region'=>'peninsula','postal'=>'28001','weight'=>7,'gross'=>199,'expected'=>16.45],
    ['region'=>'peninsula','postal'=>'28001','weight'=>7.01,'gross'=>199,'expected'=>21.45],
    ['region'=>'peninsula','postal'=>'28001','weight'=>8,'gross'=>200,'expected'=>0.0],
    ['region'=>'baleares','postal'=>'07001','weight'=>0.3,'gross'=>199,'expected'=>29.0],
    ['region'=>'baleares','postal'=>'07001','weight'=>8,'gross'=>199,'expected'=>29.0],
    ['region'=>'baleares','postal'=>'07001','weight'=>8,'gross'=>200,'expected'=>0.0],
];
$verified=[];
foreach($cases as $case) {
    $fake=new WC_Product_Simple();
    $fake->set_weight((string)$case['weight']);
    $package=['contents'=>[
        ['product_id'=>$any_id,'data'=>$fake,'quantity'=>1,'line_total'=>$case['gross'],'line_tax'=>0]
    ],'destination'=>['country'=>'ES','postcode'=>$case['postal'],'state'=>$case['region']==='baleares'?'PM':'M']];
    $rate=new WC_Shipping_Rate('wcfmmp_product_shipping_by_zone:test','Seur Frío',13.45,[],'wcfmmp_product_shipping_by_zone',1);
    $result=MDO_Latitudes_Logistics::calculate_vendor_shipping_rates(['native'=>$rate],$package);
    $actual=$result ? (float)reset($result)->get_cost() : null;
    if($actual===null || abs($actual-$case['expected'])>0.001) throw new RuntimeException('Checkout rate smoke-test mismatch: '.wp_json_encode(['case'=>$case,'actual'=>$actual]));
    $verified[]=['region'=>$case['region'],'weight'=>$case['weight'],'subtotal'=>$case['gross'],'cost'=>$actual];
}
$actual_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $methods WHERE vendor_id=%d AND zone_id=%d AND method_id=%s AND is_enabled=1",$sid,10,'free_shipping'));
if($actual_count!==1) WP_CLI::error('Unexpected Baleares free-rate count');
WP_CLI::success('LWS_APPLIED='.wp_json_encode([
    'products_updated'=>count($prepared),'basis'=>$basis,'estimated_eggs'=>$basis['provisional_egg']??0,
    'weight_unit'=>$units,'baleares_free_from'=>200,'baleares_flat'=>29,
    'mainland_brackets'=>['up_to_4kg'=>13.45,'up_to_7kg'=>16.45,'over_7kg'=>21.45],
    'smoke_tests'=>$verified,'other_vendor_methods_touched'=>0,'backup_option'=>'mdo_latitudes_shipping_backup_20261019'
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
