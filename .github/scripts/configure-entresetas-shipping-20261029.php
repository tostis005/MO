<?php
/** Apply EntreSetas shipping quotes to WCFM's vendor-specific methods only.
 * EN_SHIPPING_MODE=audit|apply|verify
 * Verified anonymous original cart samples (29 Oct 2026):
 * ES mainland (Madrid 28001, Palencia 34001): 9.95 EUR
 * ES Balearics (Palma 07001): 9.95 EUR
 * PT mainland (Lisboa 1000-001): 30.00 EUR
 * Never change global Woo zones or another vendor's shipping rows.
 */
if(!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database') || !class_exists('WCFMmp_Shipping_Zone'))throw new RuntimeException('Need WooCommerce/WCFM/EMDO');
foreach(['home','siteurl'] as $k) {
 $host=strtolower((string)wp_parse_url((string)get_option($k),PHP_URL_HOST));
 if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
}
global $wpdb;
$vendor=4982;
$st=MDO_Database::table('suppliers');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE code=%s LIMIT 1",'entresetas'),ARRAY_A);
$u=get_user_by('id',$vendor);
if(!$s||(int)$s['vendor_user_id']!==$vendor||!$u||$u->user_login!=='entresetas'||!get_user_meta($vendor,'_disable_vendor',true))throw new RuntimeException('Unexpected producer');
if(get_option('mdo_entresetas_commercial_ready',false))throw new RuntimeException('This shipping setup must not activate sales');
$methodtbl=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$loctbl=$wpdb->prefix.'wcfm_marketplace_shipping_zone_locations';
$zonetbl=$wpdb->prefix.'woocommerce_shipping_zones';
$coremethod=$wpdb->prefix.'woocommerce_shipping_zone_methods';
$map=[1=>['name'=>'España (península)','cost'=>'9.95'],
      10=>['name'=>'Baleares','cost'=>'9.95'],
      19=>['name'=>'Francia','cost'=>'30.00'],
      20=>['name'=>'Portugal','cost'=>'30.00']];
foreach([$methodtbl,$loctbl,$zonetbl,$coremethod] as $t){
 if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t))!==$t)throw new RuntimeException('Shipping table absent: '.$t);
}
foreach($map as $zone=>$cfg){
 $actual=(string)$wpdb->get_var($wpdb->prepare("SELECT zone_name FROM {$zonetbl} WHERE zone_id=%d",$zone));
 if($actual!==$cfg['name'])throw new RuntimeException('Wrong global zone: '.$zone.' '.$actual);
 $gateway=(int)$wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM {$coremethod} WHERE zone_id=%d AND method_id=%s AND is_enabled=1",$zone,'wcfmmp_product_shipping_by_zone'));
 if($gateway===0)throw new RuntimeException('WCFM global shipping bridge absent at '.$zone);
}
$mode=getenv('EN_SHIPPING_MODE')?:'audit';
if(!in_array($mode,['audit','apply','verify'],true))throw new RuntimeException('Invalid mode');
$before=$wpdb->get_results($wpdb->prepare(
 "SELECT * FROM {$methodtbl} WHERE vendor_id=%d ORDER BY zone_id,method_id,instance_id",$vendor),ARRAY_A);
$locs=$wpdb->get_results($wpdb->prepare(
 "SELECT * FROM {$loctbl} WHERE vendor_id=%d ORDER BY zone_id",$vendor),ARRAY_A);
$previous=get_user_meta($vendor,'_wcfmmp_shipping',true);
if($mode==='audit'){
 echo 'ENTRESETAS_SHIPPING_AUDIT='.wp_json_encode([
 'vendor_id'=>$vendor,'previous_method_count'=>count($before),'previous_location_count'=>count($locs),
 'zone_map'=>$map,'seller_shipping_meta'=>$previous,'commercial_ready'=>false,
 'source'=>'original_cart_quotes_2026-10-29'
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 exit;
}
$others_before=$wpdb->get_results($wpdb->prepare(
 "SELECT * FROM {$methodtbl} WHERE vendor_id<>%d ORDER BY vendor_id,zone_id,method_id,instance_id",$vendor),ARRAY_A);
$otherhash=hash('sha256',serialize($others_before));
if($mode==='apply'){
 if($before && count($before)!==4)throw new RuntimeException('Unexpected prior Entresetas shipping: manual review');
 if($locs)throw new RuntimeException('Unexpected existing shipping location overrides');
 if(!metadata_exists('user',$vendor,'_emdo_entresetas_shipping_backup_20261029')){
  update_user_meta($vendor,'_emdo_entresetas_shipping_backup_20261029',[
   'methods'=>$before,'locations'=>$locs,'shipping_meta'=>$previous,'source'=>'original_cart_quotes_2026-10-29']);
 }
 $wpdb->query('START TRANSACTION');
 try{
  $wpdb->delete($methodtbl,['vendor_id'=>$vendor],['%d']);
  foreach($map as $zone=>$cfg){
   $settings=['title'=>'Envío a domicilio','description'=>'','cost'=>$cfg['cost'],
      'tax_status'=>'none','class_cost_no_class_cost'=>'','calculation_type'=>''];
   if(!$wpdb->insert($methodtbl,['method_id'=>'flat_rate','zone_id'=>$zone,'vendor_id'=>$vendor,
       'is_enabled'=>1,'settings'=>maybe_serialize($settings)],['%s','%d','%d','%d','%s']))
    throw new RuntimeException('Failed adding zone '.$zone.': '.$wpdb->last_error);
  }
  $meta=is_array($previous)?$previous:[];
  $meta['_wcfmmp_user_shipping_enable']='yes';
  $meta['_wcfmmp_user_shipping_type']='by_zone';
  update_user_meta($vendor,'_wcfmmp_shipping',$meta);
  $wpdb->query('COMMIT');
 }catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
}
$after=$wpdb->get_results($wpdb->prepare(
 "SELECT * FROM {$methodtbl} WHERE vendor_id=%d ORDER BY zone_id,method_id,instance_id",$vendor),ARRAY_A);
if(count($after)!==4)throw new RuntimeException('Not exactly 4 vendor rates');
$seen=[];
foreach($after as $row){
 $zone=(int)$row['zone_id'];
 $settings=maybe_unserialize($row['settings']);
 if(!isset($map[$zone])||$row['method_id']!=='flat_rate'||(int)$row['is_enabled']!==1||
    abs((float)($settings['cost']??-1)-(float)$map[$zone]['cost'])>0.001||
    ($settings['tax_status']??'')!=='none')throw new RuntimeException('Wrong flat-rate settings');
 $seen[$zone]=$settings['cost'];
}
if(count($seen)!==4)throw new RuntimeException('Duplicate zone');
$others_after=$wpdb->get_results($wpdb->prepare(
 "SELECT * FROM {$methodtbl} WHERE vendor_id<>%d ORDER BY vendor_id,zone_id,method_id,instance_id",$vendor),ARRAY_A);
if(hash('sha256',serialize($others_after))!==$otherhash)throw new RuntimeException('Other vendor methods changed');
if(!get_user_meta($vendor,'_disable_vendor',true)||get_option('mdo_entresetas_commercial_ready',false))
 throw new RuntimeException('Sales inadvertently enabled');
if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('shipping',true);
do_action('mdo_shipping_destinations_invalidate');
wp_cache_flush();
$dest=[];
if(class_exists('MDO_Shipping_Destinations')){
 foreach(['Madrid'=>['ES','28001',true],'Palencia'=>['ES','34001',true],
 'Baleares'=>['ES','07001',true],'Portugal'=>['PT','1000-001',true],'Francia'=>['FR','75001',true],
 'Canarias'=>['ES','35001',false],'Ceuta'=>['ES','51001',false]] as $key=>$test){
  $actual=MDO_Shipping_Destinations::vendor_can_ship_to($vendor,$test[0],$test[1]);
  $dest[$key]=['actual'=>$actual,'expected'=>$test[2]];
  if($actual!==$test[2])throw new RuntimeException('Destination mismatch '.$key);
 }
}
echo 'ENTRESETAS_SHIPPING_VERIFIED='.wp_json_encode([
 'vendor_id'=>$vendor,'rates'=>$seen,'other_vendors_unchanged'=>true,'vendor_disabled'=>true,
 'commercial_ready'=>false,'destinations'=>$dest
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
