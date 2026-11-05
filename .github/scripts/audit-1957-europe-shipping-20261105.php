<?php
/**
 * Read-only production audit of 1957 WCFM shipping methods and WC zones.
 * Never changes store or seller data.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WooCommerce') || !class_exists('WCFMmp_Shipping_Zone')) {
    throw new RuntimeException('Missing WordPress/WP-CLI/WooCommerce/WCFM.');
}
foreach (['home','siteurl'] as $opt) {
    $host = strtolower((string)wp_parse_url((string)get_option($opt), PHP_URL_HOST));
    if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Not the expected production website.');
    }
}
global $wpdb;
$vendor = 3;
$user = get_user_by('id',$vendor);
$profile = get_user_meta($vendor,'wcfmmp_profile_settings',true);
if (!$user || !is_array($profile) || stripos((string)($profile['store_name']??''),'1957')===false) {
    throw new RuntimeException('1957 WCFM vendor identity verification failed.');
}
$methodtable = $wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$zonetable = $wpdb->prefix.'woocommerce_shipping_zones';
$locatable = $wpdb->prefix.'woocommerce_shipping_zone_locations';
foreach ([$methodtable,$zonetable,$locatable] as $tbl) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$tbl))!==$tbl) throw new RuntimeException('Missing shipping table: '.$tbl);
}
$raw=$wpdb->get_results($wpdb->prepare("SELECT instance_id,zone_id,method_id,is_enabled,settings FROM {$methodtable} WHERE vendor_id=%d ORDER BY zone_id,instance_id",$vendor), ARRAY_A);
$methods=[];
foreach ($raw as $r) {
    $r['settings']=maybe_unserialize($r['settings']);
    $r['instance_id']=(int)$r['instance_id'];
    $r['zone_id']=(int)$r['zone_id'];
    $r['is_enabled']=(int)$r['is_enabled'];
    $methods[]=$r;
}
$zones=[];
foreach ($wpdb->get_results("SELECT zone_id,zone_name,zone_order FROM {$zonetable} ORDER BY zone_order,zone_id",ARRAY_A) as $r) {
    $id=(int)$r['zone_id'];
    $loc=$wpdb->get_results($wpdb->prepare("SELECT location_code,location_type FROM {$locatable} WHERE zone_id=%d ORDER BY location_type,location_code",$id),ARRAY_A);
    $z=new WC_Shipping_Zone($id);
    $gateway=false;
    foreach ($z->get_shipping_methods(true) as $m) {
        if ($m->id==='wcfmmp_product_shipping_by_zone') $gateway=true;
    }
    $zones[]=['id'=>$id,'name'=>$r['zone_name'],'locations'=>$loc,'wcfm_gateway'=>$gateway];
}
echo '1957_SHIPPING_AUDIT='.wp_json_encode([
    'vendor_id'=>$vendor, 'store_name'=>$profile['store_name'], 'login'=>$user->user_login,
    'shipping_meta'=>get_user_meta($vendor,'_wcfmmp_shipping',true),
    'methods'=>$methods,'zones'=>$zones,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
