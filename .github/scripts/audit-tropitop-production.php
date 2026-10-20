<?php
/** Nonmutating production prelaunch audit. */
if (!defined('ABSPATH')) exit(1);
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if (!in_array($host,array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true)) throw new RuntimeException('Unexpected site');
global $wpdb;
$vendor=get_user_by('login','tropitop');
$supplier_table=MDO_Database::table('suppliers');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,active,connector,vendor_user_id,source_url FROM {$supplier_table} WHERE code=%s",'tropitop'),ARRAY_A);
$terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));$cats=array();
foreach($terms as $t) if(preg_match('~aguacat|fruta|verdura|ecolog|bio|citrico~iu',$t->name.' '.$t->slug))
 $cats[]=array('id'=>$t->term_id,'slug'=>$t->slug,'parent'=>$t->parent,'name'=>$t->name);
$existing=wc_get_product_id_by_sku('TROPITOP-33');
$vendor_profile=$vendor?get_user_meta($vendor->ID,'wcfmmp_profile_settings',true):array();
$vendor_profile=is_array($vendor_profile)?$vendor_profile:array();
$vendor_shipmeta=$vendor?get_user_meta($vendor->ID,'_wcfmmp_shipping',true):array();
$vendor_shipmeta=is_array($vendor_shipmeta)?$vendor_shipmeta:array();
$wcfm_methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$has_methods=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wcfm_methods))===$wcfm_methods;
$vendor_ship=($vendor&&$has_methods)?$wpdb->get_results($wpdb->prepare("SELECT zone_id,method_id,is_enabled,settings FROM {$wcfm_methods} WHERE vendor_id=%d",$vendor->ID),ARRAY_A):array();
$vendor_ship=array_map(static fn($x)=>array('zone_id'=>(int)$x['zone_id'],'method_id'=>$x['method_id'],'is_enabled'=>(int)$x['is_enabled'],'settings_keys'=>array_keys((array)maybe_unserialize($x['settings']))),$vendor_ship);
$main_opts=get_option('wcfm_commission_options',array());
$out=array(
 'site'=>$host,'emdo_version'=>defined('MDO_SUPPLIER_SYNC_VERSION')?MDO_SUPPLIER_SYNC_VERSION:'unknown',
 'vendor_exists'=>(bool)$vendor,'vendor_role'=>$vendor?$vendor->roles:array(),
 'vendor_disabled'=>$vendor?(bool)get_user_meta($vendor->ID,'_disable_vendor',true):null,
 'vendor_image_ids'=>$vendor?array('logo'=>($vendor_profile['gravatar']??null),'banner'=>($vendor_profile['banner']??null)):null,
 'vendor_shipping_type'=>$vendor?($vendor_shipmeta['_wcfmmp_user_shipping_type']??'unset'):null,
 'vendor_rates'=>$vendor_ship,'supplier'=>$supplier,
 'shipping_verified'=>(bool)get_option('mdo_tropitop_shipping_rates_verified',false),
 'product_33_exists'=>(bool)$existing,'product_33_published'=>$existing?get_post_status($existing):null,
 'candidate_categories'=>$cats,'wcfm_commission_direction'=>$main_opts['commission_for']??'unset',
);
echo 'EMDO_TROPITOP_PRODUCTION_READ_ONLY='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
