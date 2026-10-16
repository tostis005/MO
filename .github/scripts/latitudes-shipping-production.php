<?php
/** Latitudes WCFM native shipping audit (WP-CLI). Read-only. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit(1); }
$host = strtolower( (string) wp_parse_url( site_url('/'), PHP_URL_HOST ) );
$home_host = strtolower( (string) wp_parse_url( home_url('/'), PHP_URL_HOST ) );
if ( ! in_array($host, array('www.elmercadodeorigen.com','elmercadodeorigen.com'), true) || ! in_array($home_host,array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true) ) {
  WP_CLI::error('Not production');
}
if (!class_exists('MDO_Database') || !class_exists('WC_Shipping_Zones') || !class_exists('WCFMmp_Shipping_Zone')) { WP_CLI::error('WooCommerce/WCFM/EMDO missing'); }
global $wpdb;
$table=MDO_Database::table('suppliers');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,code,vendor_user_id FROM {$table} WHERE code=%s",'latitudes'),ARRAY_A);
if (!$supplier) { WP_CLI::error('Latitudes missing'); }
$vendor=(int)$supplier['vendor_user_id'];
$user=get_userdata($vendor);
if (!$user || !in_array('wcfm_vendor',(array)$user->roles,true)) { WP_CLI::error('Latitudes vendor invalid'); }
$shipping=get_user_meta($vendor,'_wcfmmp_shipping',true);
$out=array(
  'vendor'=>$vendor,
  'shipping'=>array(
    'type'=>is_array($shipping)?(string)($shipping['_wcfmmp_user_shipping_type']??''):'',
    'enabled'=>is_array($shipping)?(string)($shipping['_wcfmmp_user_shipping_enable']??''):'',
    'settings_keys'=>is_array($shipping)?array_keys($shipping):array()
  ),
  'zones'=>array()
);
foreach (array(1=>'Peninsula',10=>'Baleares',20=>'Portugal') as $id=>$label) {
  $zone=new WC_Shipping_Zone($id);
  $global=array();
  foreach ($zone->get_shipping_methods(true) as $m) { $global[]=(string)$m->id; }
  $native=WCFMmp_Shipping_Zone::get_shipping_methods($id,$vendor);
  $methods=array();
  foreach ($native as $m) {
    $settings=(array)($m['settings']??array());
    $methods[]=array(
      'id'=>(string)($m['id']??''),
      'enabled'=>(string)($m['enabled']??''),
      'cost'=>(string)($settings['cost']??''),
      'min_amount'=>(string)($settings['min_amount']??''),
      'instance_id'=>(int)($m['instance_id']??0)
    );
  }
  $out['zones'][$label]=array(
    'id'=>$id,'name'=>$zone->get_zone_name(),
    'global_gateway'=>in_array('wcfmmp_product_shipping_by_zone',$global,true),
    'methods'=>$methods
  );
}
WP_CLI::log('LATITUDES_SHIPPING_AUDIT='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$mode=getenv('LATITUDES_SHIPPING_MODE')?:'inspect';
if ('inspect'===$mode) {
  WP_CLI::success('Read-only shipping audit complete');
  exit;
}
if ('apply'!==$mode || 4949!==$vendor) { WP_CLI::error('Not approved for this vendor/mode'); }
$rates=array(
  1=>array('flat_rate'=>'13.45','free_shipping'=>'200'),
  10=>array('flat_rate'=>'29.00'),
  20=>array('flat_rate'=>'13.45','free_shipping'=>'200'),
);
$expected_names=array(1=>'España (península)',10=>'Baleares',20=>'Portugal');
foreach ($out['zones'] as $data) {
  $id=(int)$data['id'];
  if (!isset($rates[$id]) || !$data['global_gateway'] || $data['name']!==$expected_names[$id]) {
    WP_CLI::error('Global WC zone configuration is unexpected: '.$id);
  }
  $seen=array();
  foreach ($data['methods'] as $m) {
    if (!in_array($m['id'],array('flat_rate','free_shipping'),true) || isset($seen[$m['id']])) {
      WP_CLI::error('Unexpected or duplicate vendor shipping method '.$id);
    }
    $seen[$m['id']]=true;
  }
}
$method_table=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$method_table))!==$method_table) {
  WP_CLI::error('WCFM native shipping method table absent');
}
$changes=array();
foreach ($rates as $zone_id=>$methods) {
  $existing=$wpdb->get_results($wpdb->prepare(
    "SELECT instance_id,method_id,is_enabled,settings FROM {$method_table} WHERE vendor_id=%d AND zone_id=%d ORDER BY instance_id",
    $vendor,$zone_id
  ),ARRAY_A);
  $by_method=array();
  foreach ($existing as $item) {
    if (isset($by_method[$item['method_id']])) { WP_CLI::error('Duplicate vendor method in zone '.$zone_id); }
    $by_method[$item['method_id']]=$item;
  }
  foreach ($methods as $method_id=>$value) {
    $current=$by_method[$method_id]??null;
    $settings=$current?maybe_unserialize($current['settings']):array();
    if (!is_array($settings)) { WP_CLI::error('Invalid method settings in zone '.$zone_id); }
    $settings=wp_parse_args($settings,array(
      'title'=>'flat_rate'===$method_id?'Envío a domicilio':'Envío gratuito',
      'description'=>'','tax_status'=>'none'
    ));
    if ('flat_rate'===$method_id) {
      $settings['cost']=$value;
    } else {
      $settings['cost']='0';
      $settings['min_amount']=$value;
    }
    if ($current) {
      $instance_id=(int)$current['instance_id'];
    } else {
      $instance_id=WCFMmp_Shipping_Zone::add_shipping_methods(array(
        'user_id'=>$vendor,'zone_id'=>$zone_id,'method_id'=>$method_id
      ));
      if (is_wp_error($instance_id) || (int)$instance_id<=0) { WP_CLI::error('WCFM failed creating zone '.$zone_id.' method '.$method_id); }
    }
    $saved=$wpdb->update($method_table,array('settings'=>maybe_serialize($settings),'is_enabled'=>1),
      array('instance_id'=>(int)$instance_id,'vendor_id'=>$vendor,'zone_id'=>$zone_id,'method_id'=>$method_id),
      array('%s','%d'),array('%d','%d','%d','%s'));
    if (false===$saved) { WP_CLI::error('WCFM failed saving zone '.$zone_id.' '.$wpdb->last_error); }
    $changes[]=array('zone'=>$zone_id,'method'=>$method_id,'value'=>$value,'instance'=>(int)$instance_id);
  }
  if (10===$zone_id && isset($by_method['free_shipping'])) {
    $off=$wpdb->update($method_table,array('is_enabled'=>0),
      array('instance_id'=>(int)$by_method['free_shipping']['instance_id'],'zone_id'=>10,'vendor_id'=>$vendor,'method_id'=>'free_shipping'),
      array('%d'),array('%d','%d','%d','%s'));
    if (false===$off) { WP_CLI::error('Could not disable free shipping for Baleares'); }
  }
}
if (!is_array($shipping)) { $shipping=array(); }
$shipping['_wcfmmp_user_shipping_enable']='yes';
$shipping['_wcfmmp_user_shipping_type']='by_zone';
update_user_meta($vendor,'_wcfmmp_shipping',$shipping);
if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
do_action('mdo_shipping_destinations_invalidate');
if (class_exists('MDO_Shipping_Destinations')) { MDO_Shipping_Destinations::supported_countries(true); }
$verified=array();
foreach ($rates as $zone_id=>$methods) {
  $native=WCFMmp_Shipping_Zone::get_shipping_methods($zone_id,$vendor);
  foreach ($methods as $method_id=>$expected) {
    $found=array_values(array_filter((array)$native,static fn($m)=>($m['id']??'')===$method_id&&($m['enabled']??'')==='yes'));
    if (count($found)!==1) { WP_CLI::error('Post-verification failed for '.$zone_id.' '.$method_id); }
    $sett=(array)($found[0]['settings']??array());
    $actual=(string)($sett[$method_id==='flat_rate'?'cost':'min_amount']??'');
    if (abs((float)$actual-(float)$expected)>0.00001) { WP_CLI::error('Rate mismatched in '.$zone_id.' '.$method_id.': '.$actual); }
    $verified[$zone_id][$method_id]=$actual;
  }
  if ($zone_id===10) {
    foreach ($native as $m) {
      if (($m['id']??'')==='free_shipping' && ($m['enabled']??'')==='yes') {
        WP_CLI::error('Baleares must never have free shipping');
      }
    }
  }
}
WP_CLI::success('LATITUDES_SHIPPING_APPLIED='.wp_json_encode(array('vendor_id'=>$vendor,'changes'=>$changes,'verified'=>$verified),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

