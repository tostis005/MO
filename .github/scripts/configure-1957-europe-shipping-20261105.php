<?php
/**
 * 1957-only EU shipping update, 2026-11-05.
 * Change only the existing enabled 20*[qty] WCFM vendor rates to 25*[qty].
 * Never alter other vendors, destination coverage, quantity formula, taxes,
 * other 1957 shipping rates, or global WooCommerce shipping zones.
 *
 * MDO_1957_EU_SHIPPING_MODE=audit|apply|verify
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
    !class_exists('WooCommerce') || !class_exists('WCFMmp_Shipping_Zone')) {
    throw new RuntimeException('WP-CLI, WooCommerce and WCFM shipping are required.');
}
foreach (['home','siteurl'] as $key) {
    $host=strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST));
    if (!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Refusing to change shipping outside the expected production website.');
    }
}
global $wpdb;
$vendor=3;
$user=get_user_by('id',$vendor);
$profile=get_user_meta($vendor,'wcfmmp_profile_settings',true);
if (!$user || !is_array($profile) || (string)($profile['store_name']??'')!=='1957') {
    throw new RuntimeException('Vendor 1957 identity did not match expected account id 3.');
}
$shipping=get_user_meta($vendor,'_wcfmmp_shipping',true);
if (!is_array($shipping) || ($shipping['_wcfmmp_user_shipping_enable']??'')!=='yes' ||
    ($shipping['_wcfmmp_user_shipping_type']??'')!=='by_zone') {
    throw new RuntimeException('1957 shipping settings are not enabled by zone.');
}
$table=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$zones=$wpdb->prefix.'woocommerce_shipping_zones';
$locations=$wpdb->prefix.'woocommerce_shipping_zone_locations';
foreach ([$table,$zones,$locations] as $tbl) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$tbl))!==$tbl) {
        throw new RuntimeException('Missing shipping table: '.$tbl);
    }
}
// Only these seven zones had EUR 20 per item as of the production audit.
$targets=[
    11=>['name'=>'Bélgica','country'=>'BE'],
    12=>['name'=>'Alemania','country'=>'DE'],
    16=>['name'=>'Luxemburgo','country'=>'LU'],
    18=>['name'=>'Italia','country'=>'IT'],
    19=>['name'=>'Francia','country'=>'FR'],
    21=>['name'=>'Rep. Checa','country'=>'CZ'],
    22=>['name'=>'Polonia','country'=>'PL'],
];
$mode=getenv('MDO_1957_EU_SHIPPING_MODE') ?: 'audit';
if (!in_array($mode,['audit','apply','verify'],true)) {
    throw new RuntimeException('Invalid shipping operation mode.');
}
$before_all=$wpdb->get_results($wpdb->prepare(
    "SELECT instance_id,zone_id,method_id,is_enabled,settings
     FROM {$table} WHERE vendor_id=%d ORDER BY zone_id,instance_id",$vendor
),ARRAY_A);
$other_before=$wpdb->get_results($wpdb->prepare(
    "SELECT vendor_id,instance_id,zone_id,method_id,is_enabled,settings
     FROM {$table} WHERE vendor_id<>%d ORDER BY vendor_id,zone_id,instance_id",$vendor
),ARRAY_A);
$found=[];
$prior_costs=[];
foreach ($targets as $id=>$spec) {
    $name=(string)$wpdb->get_var($wpdb->prepare(
        "SELECT zone_name FROM {$zones} WHERE zone_id=%d",$id
    ));
    if ($name!==$spec['name']) throw new RuntimeException('Unexpected country zone: '.$id.' '.$name);
    $locs=$wpdb->get_results($wpdb->prepare(
        "SELECT location_code,location_type FROM {$locations} WHERE zone_id=%d",$id
    ),ARRAY_A);
    if (count($locs)!==1 || $locs[0]['location_code']!==$spec['country'] ||
        $locs[0]['location_type']!=='country') {
        throw new RuntimeException('Unexpected shipping destinations in zone '.$id);
    }
    $zone=new WC_Shipping_Zone($id);
    $gateway=false;
    foreach ($zone->get_shipping_methods(true) as $m) {
        if ($m->id==='wcfmmp_product_shipping_by_zone') $gateway=true;
    }
    if (!$gateway) throw new RuntimeException('WCFM gateway disabled in zone '.$id);
    $rows=array_values(array_filter($before_all,static fn($r)=>
        (int)$r['zone_id']===$id && $r['method_id']==='flat_rate'
    ));
    if (count($rows)!==1 || (int)$rows[0]['is_enabled']!==1) {
        throw new RuntimeException('Missing/duplicate/disabled 1957 flat rate in zone '.$id);
    }
    $record=$rows[0];
    $settings=maybe_unserialize($record['settings']);
    if (!is_array($settings) || !isset($settings['cost'])) {
        throw new RuntimeException('Invalid 1957 rate settings in zone '.$id);
    }
    $cost=(string)$settings['cost'];
    if (!in_array($cost,['20*[qty]','25*[qty]'],true)) {
        throw new RuntimeException('Unexpected old rate: '.$id.' '.$cost);
    }
    $found[$id]=['record'=>$record,'settings'=>$settings,'country'=>$spec['country'],'name'=>$name];
    $prior_costs[$spec['country']]=$cost;
}
if ($mode==='verify') {
    foreach ($prior_costs as $country=>$cost) {
        if ($cost!=='25*[qty]') throw new RuntimeException('Rate did not persist at EUR 25 in '.$country);
    }
}
if ($mode==='apply') {
    // Safeguard: save unchanged vendor-only snapshots for recovery.
    $backup_key='_emdo_1957_europe_shipping_backup_20261105';
    if (!metadata_exists('user',$vendor,$backup_key)) {
        if (!update_user_meta($vendor,$backup_key,[
            'created_at'=>current_time('mysql',true),
            'source'=>'producer_request_2026-11-05',
            'rows'=>$before_all,
        ])) throw new RuntimeException('Could not save 1957 shipping backup.');
    }
    if (false===$wpdb->query('START TRANSACTION')) throw new RuntimeException('No SQL transaction');
    try {
        foreach ($found as $id=>$item) {
            $r=$item['record'];
            if ($item['settings']['cost']==='25*[qty]') continue;
            $new=$item['settings'];
            $new['cost']='25*[qty]';
            $result=$wpdb->update($table,
                ['settings'=>maybe_serialize($new)],
                ['vendor_id'=>$vendor,'instance_id'=>(int)$r['instance_id'],
                 'zone_id'=>$id,'method_id'=>'flat_rate','settings'=>$r['settings']],
                ['%s'],['%d','%d','%d','%s','%s']);
            if ($result!==1) {
                throw new RuntimeException('Failed compare-and-swap rate update in '.$item['country'].': '.$wpdb->last_error);
            }
        }
        $wpdb->query('COMMIT');
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
    }
}
$after_all=$wpdb->get_results($wpdb->prepare(
    "SELECT instance_id,zone_id,method_id,is_enabled,settings
     FROM {$table} WHERE vendor_id=%d ORDER BY zone_id,instance_id",$vendor
),ARRAY_A);
$after_by_instance=[];
foreach ($after_all as $r) $after_by_instance[(int)$r['instance_id']]=$r;
if (count($after_all)!==count($before_all)) throw new RuntimeException('1957 rate row count changed.');
$verified=[];
foreach ($before_all as $r) {
    $id=(int)$r['zone_id'];
    $inst=(int)$r['instance_id'];
    $after=$after_by_instance[$inst]??null;
    if (!$after || (int)$after['zone_id']!==$id ||
        $after['method_id']!==$r['method_id'] ||
        (int)$after['is_enabled']!==(int)$r['is_enabled']) {
        throw new RuntimeException('1957 rate identity/method changed.');
    }
    if (isset($targets[$id]) && $r['method_id']==='flat_rate') {
        $old=maybe_unserialize($r['settings']);
        $new=maybe_unserialize($after['settings']);
        if (!is_array($old) || !is_array($new) ||
            $new['cost']!==($mode==='audit'?$old['cost']:'25*[qty]')) {
            throw new RuntimeException('Shipping price verification failed in zone '.$id);
        }
        $old['cost']=$new['cost'];
        if ($old!==$new) throw new RuntimeException('Unexpected shipping setting changed in zone '.$id);
        $verified[$targets[$id]['country']]=$new['cost'];
    } elseif ($r!==$after) {
        throw new RuntimeException('An unrelated 1957 shipping method changed.');
    }
}
$other_after=$wpdb->get_results($wpdb->prepare(
    "SELECT vendor_id,instance_id,zone_id,method_id,is_enabled,settings
     FROM {$table} WHERE vendor_id<>%d ORDER BY vendor_id,zone_id,instance_id",$vendor
),ARRAY_A);
if ($other_before!==$other_after) throw new RuntimeException('Other vendor shipping methods were changed.');
if (get_user_meta($vendor,'_wcfmmp_shipping',true)!==$shipping) {
    throw new RuntimeException('1957 shipping type was unexpectedly changed.');
}
if ($mode==='apply') {
    if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('shipping',true);
    do_action('mdo_shipping_destinations_invalidate');
    wp_cache_flush();
}
echo '1957_EU_SHIPPING_RESULT='.wp_json_encode([
    'mode'=>$mode,'vendor_id'=>$vendor,'countries'=>$verified,
    'old_costs'=>$prior_costs,'other_1957_rates_unchanged'=>true,
    'other_vendors_unchanged'=>true,'global_zones_unchanged'=>true,
    'quantity_formula_retained'=>true,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
