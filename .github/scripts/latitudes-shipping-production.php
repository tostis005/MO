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
WP_CLI::success('Read-only shipping audit complete');
