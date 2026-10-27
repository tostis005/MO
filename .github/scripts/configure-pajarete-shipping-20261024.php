<?php
/**
 * Apply El Mercado de Origen's updated commercial terms for Quesos Pajarete only:
 * minimum EUR 50 of vendor products, EUR 5 shipping for EUR 50-75,
 * free shipping strictly above EUR 75 (from EUR 75.01; VAT inclusive).
 * Mainland Spain only; never modify global WC zones or other vendors.
 *
 * PAJARETE_SHIPPING_MODE=audit|apply|verify
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
    !class_exists('WooCommerce') || !class_exists('MDO_Database') ||
    !class_exists('WCFMmp_Shipping_Zone') || !class_exists('MDO_Minimum_Order')) {
    throw new RuntimeException('WP-CLI, WooCommerce, EMDO and WCFM zone shipping required.');
}
foreach (['home', 'siteurl'] as $key) {
    $host = strtolower((string) wp_parse_url((string) get_option($key), PHP_URL_HOST));
    if (!in_array($host, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) {
        throw new RuntimeException('Refusing nonproduction shipping changes.');
    }
}
global $wpdb;
$suppliers = MDO_Database::table('suppliers');
$supplier = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1", 'quesos-pajarete'
), ARRAY_A);
$vendor_id = 4974;
$user = get_user_by('id', $vendor_id);
if (!$supplier || (int) $supplier['vendor_user_id'] !== $vendor_id ||
    !$user || $user->user_login !== 'quesos-pajarete') {
    throw new RuntimeException('Quesos Pajarete vendor identity mismatch.');
}
$profile = get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
if (!is_array($profile) || ($profile['store_name'] ?? '') !== 'Quesos Pajarete') {
    throw new RuntimeException('Unexpected WCFM vendor store profile.');
}
$tables = [
    'vendor_methods' => $wpdb->prefix.'wcfm_marketplace_shipping_zone_methods',
    'vendor_locations' => $wpdb->prefix.'wcfm_marketplace_shipping_zone_locations',
    'wc_zones' => $wpdb->prefix.'woocommerce_shipping_zones',
    'wc_locations' => $wpdb->prefix.'woocommerce_shipping_zone_locations',
    'wc_methods' => $wpdb->prefix.'woocommerce_shipping_zone_methods',
];
foreach ($tables as $key => $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) {
        throw new RuntimeException('Shipping table missing: '.$key);
    }
}
$zone_id = 1;
$name = (string) $wpdb->get_var($wpdb->prepare(
    "SELECT zone_name FROM {$tables['wc_zones']} WHERE zone_id=%d", $zone_id
));
if ($name !== 'España (península)') {
    throw new RuntimeException('Expected mainland Spain WC zone 1, got '.$name);
}
$locations = $wpdb->get_results($wpdb->prepare(
    "SELECT location_code,location_type FROM {$tables['wc_locations']} WHERE zone_id=%d",
    $zone_id
),ARRAY_A);
if (!$locations) throw new RuntimeException('Mainland zone has no locations.');
$location_values = array_map(static fn($r)=>strtoupper($r['location_code']), $locations);
foreach (['ES:PM','ES:GC','ES:TF','ES:CE','ES:ML'] as $forbidden) {
    if (in_array($forbidden,$location_values,true)) {
        throw new RuntimeException('Mainland zone includes excluded area: '.$forbidden);
    }
}
if (!array_filter($locations, static fn($r)=>$r['location_type']==='state' &&
    in_array(strtoupper((string)$r['location_code']),['ES:M','ES:CA','ES:SE','ES:MA'],true))) {
    throw new RuntimeException('Mainland zone not confirmed by expected Spanish provinces.');
}
$global_method = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tables['wc_methods']}
       WHERE zone_id=%d AND method_id=%s AND is_enabled=1",
    $zone_id,'wcfmmp_product_shipping_by_zone'
));
if (!$global_method) {
    throw new RuntimeException('WCFM shipping bridge not active for mainland Spain.');
}
$mode = getenv('PAJARETE_SHIPPING_MODE') ?: 'audit';
if (!in_array($mode, ['audit', 'apply', 'verify'], true)) {
    throw new RuntimeException('Invalid Pajarete shipping phase.');
}
$vendor_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tables['vendor_methods']} WHERE vendor_id=%d ORDER BY instance_id",
    $vendor_id
), ARRAY_A);
$vendor_locs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tables['vendor_locations']} WHERE vendor_id=%d ORDER BY zone_id",
    $vendor_id
),ARRAY_A);
$shipping_meta = get_user_meta($vendor_id,'_wcfmmp_shipping',true);
$other_methods_before = (string)$wpdb->get_var($wpdb->prepare(
    "SELECT SHA2(GROUP_CONCAT(
       CONCAT_WS(':',vendor_id,zone_id,method_id,is_enabled,HEX(settings))
       ORDER BY vendor_id,zone_id,method_id,instance_id SEPARATOR '|'
     ),256)
     FROM {$tables['vendor_methods']} WHERE vendor_id<>%d",
    $vendor_id
));
if ('' === $other_methods_before) {
    // Non-empty other vendor shipping configuration is expected.
    throw new RuntimeException('Cannot fingerprint other vendor shipping rows.');
}
$other_vendor_meta_before = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key=%s AND user_id<>%d",
    '_wcfmmp_shipping',$vendor_id
));
$expected = [
  'flat_rate' => [
    'title'=>'Envío a domicilio',
    'description'=>'',
    'cost'=>'5.00',
    // Source's EUR5 is VAT-inclusive, as in existing WCFM store rules.
    'tax_status'=>'none',
    'class_cost_no_class_cost'=>'',
    'calculation_type'=>'',
  ],
  'free_shipping' => [
    'title'=>'Envío gratuito',
    'description'=>'',
    'cost'=>'0',
    'tax_status'=>'none',
    'min_amount'=>'75.01'
  ]
];
if ($mode === 'audit') {
    echo 'PAJARETE_SHIPPING_AUDIT='.wp_json_encode([
      'vendor_id'=>$vendor_id,'vendor_methods_before'=>array_map(
          static fn($r)=>['zone'=>(int)$r['zone_id'],'method'=>$r['method_id'],
            'enabled'=>(int)$r['is_enabled'],'settings'=>maybe_unserialize($r['settings'])],$vendor_rows),
      'vendor_location_overrides_before'=>count($vendor_locs),
      'vendor_shipping_type_before'=>is_array($shipping_meta)?($shipping_meta['_wcfmmp_user_shipping_type']??''):null,
      'mainland_zone_id'=>$zone_id,'mainland_zone'=>$name,
      'supplier_minimum_before'=>$supplier['minimum_order_amount']??null,
      'desired'=>['supplier_minimum_eur'=>'50.00','flat_eur'=>'5.00','free_above_eur'=>'75.00','free_from_eur'=>'75.01','destination'=>'peninsular_spain_only'],
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if ($mode === 'apply') {
    $backup_key='_emdo_pajarete_commercial_backup_20261027';
    if (!metadata_exists('user',$vendor_id,$backup_key)) {
        update_user_meta($vendor_id,$backup_key,[
          'methods'=>$vendor_rows,'locations'=>$vendor_locs,'shipping_meta'=>$shipping_meta,
          'minimum_order_amount'=>$supplier['minimum_order_amount']??null,
          'shipping_policy_source'=>'merchant_instruction_2026-10-27'
        ]);
    }
    if (false === $wpdb->query('START TRANSACTION')) {
        throw new RuntimeException('Could not start shipping update transaction.');
    }
    try {
        if (false === $wpdb->delete($tables['vendor_methods'],['vendor_id'=>$vendor_id],['%d'])) {
            throw new RuntimeException('Deleting prior Pajarete shipping methods failed.');
        }
        if (false === $wpdb->delete($tables['vendor_locations'],['vendor_id'=>$vendor_id],['%d'])) {
            throw new RuntimeException('Deleting prior Pajarete shipping overrides failed.');
        }
        foreach ($expected as $method => $settings) {
            $ok=$wpdb->insert($tables['vendor_methods'],[
              'method_id'=>$method,'zone_id'=>$zone_id,
              'vendor_id'=>$vendor_id,'is_enabled'=>1,'settings'=>maybe_serialize($settings),
            ],['%s','%d','%d','%d','%s']);
            if (false === $ok)throw new RuntimeException('Failed to add '.$method.': '.$wpdb->last_error);
        }
        $m = is_array($shipping_meta)?$shipping_meta:[];
        $m['_wcfmmp_user_shipping_enable']='yes';
        $m['_wcfmmp_user_shipping_type']='by_zone';
        if (false === update_user_meta($vendor_id,'_wcfmmp_shipping',$m) && $shipping_meta!==$m) {
            throw new RuntimeException('Could not update WCFM shipping mode.');
        }
        $min_update=$wpdb->update($suppliers,
            ['minimum_order_amount'=>'50.00'],['id'=>(int)$supplier['id']],['%f'],['%d']);
        if (false === $min_update) {
            throw new RuntimeException('Could not set Pajarete supplier minimum order.');
        }
        $wpdb->query('COMMIT');
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
    }
}
$minimum_saved=$wpdb->get_var($wpdb->prepare(
    "SELECT minimum_order_amount FROM {$suppliers} WHERE id=%d AND vendor_user_id=%d",
    (int)$supplier['id'],$vendor_id
));
if ($minimum_saved===null || abs((float)$minimum_saved-50.00)>0.0001) {
    throw new RuntimeException('Pajarete supplier minimum was not persisted as EUR 50.');
}
$after=$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tables['vendor_methods']} WHERE vendor_id=%d ORDER BY zone_id,method_id,instance_id",
    $vendor_id
),ARRAY_A);
if (count($after)!==2)throw new RuntimeException('Expected exactly two Pajarete mainland shipping methods.');
$seen = [];
foreach ($after as $row) {
    $id=(int)$row['zone_id'];$method=(string)$row['method_id'];
    if ($id!==$zone_id || !isset($expected[$method]) ||
        (int)$row['is_enabled']!==1 || isset($seen[$method])) {
        throw new RuntimeException('Unexpected Pajarete method or destination.');
    }
    $settings=maybe_unserialize($row['settings']);
    if (!is_array($settings))throw new RuntimeException('Invalid stored WCFM method.');
    $field=$method==='flat_rate'?'cost':'min_amount';
    if (abs((float)($settings[$field]??-1)-(float)$expected[$method][$field])>0.0001) {
        throw new RuntimeException('Shipping price/threshold mismatch.');
    }
    if (($settings['tax_status']??'')!=='none') {
        throw new RuntimeException('Unexpected shipping tax status.');
    }
    $seen[$method] = $settings;
}
if (count($seen)!==2)throw new RuntimeException('Expected both shipping methods.');
$loc_after=(int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tables['vendor_locations']} WHERE vendor_id=%d",$vendor_id
));
if ($loc_after!==0)throw new RuntimeException('Unexpected vendor-specific destinations.');
$meta_after=get_user_meta($vendor_id,'_wcfmmp_shipping',true);
if (!is_array($meta_after) ||
    ($meta_after['_wcfmmp_user_shipping_enable']??'')!=='yes' ||
    ($meta_after['_wcfmmp_user_shipping_type']??'')!=='by_zone') {
    throw new RuntimeException('WCFM Pajarete shipping mode not persisted.');
}
$other_methods_after=(string)$wpdb->get_var($wpdb->prepare(
    "SELECT SHA2(GROUP_CONCAT(
       CONCAT_WS(':',vendor_id,zone_id,method_id,is_enabled,HEX(settings))
       ORDER BY vendor_id,zone_id,method_id,instance_id SEPARATOR '|'
     ),256)
     FROM {$tables['vendor_methods']} WHERE vendor_id<>%d", $vendor_id
));
if ($other_methods_before!==$other_methods_after) {
    throw new RuntimeException('Other vendor shipping methods changed unexpectedly.');
}
if ((int)$other_vendor_meta_before!==(int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key=%s AND user_id<>%d",
    '_wcfmmp_shipping',$vendor_id
))) {
    throw new RuntimeException('Unexpected other seller shipping meta change.');
}
if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('shipping',true);
if (function_exists('mdo_shipping_destinations_invalidate')) mdo_shipping_destinations_invalidate();
do_action('mdo_shipping_destinations_invalidate');
wp_cache_flush();
$destination_tests=[];
if (class_exists('MDO_Shipping_Destinations')) {
    foreach ([
      'Madrid'=>['ES','28001',true],
      'Barcelona'=>['ES','08001',true],
      'Sevilla'=>['ES','41001',true],
      'Baleares'=>['ES','07001',false],
      'Canarias'=>['ES','35001',false],
      'Ceuta'=>['ES','51001',false],
      'Melilla'=>['ES','52001',false],
      'Portugal'=>['PT','1000-001',false]
    ] as $label=>$test) {
        $actual=MDO_Shipping_Destinations::vendor_can_ship_to($vendor_id,$test[0],$test[1]);
        $destination_tests[$label]=['actual'=>$actual,'expected'=>$test[2]];
        if ($actual!==$test[2]) {
            throw new RuntimeException('Destination safety check failed for '.$label);
        }
    }
}
echo 'PAJARETE_SHIPPING_RESULT='.wp_json_encode([
 'mode'=>$mode,'vendor_id'=>$vendor_id,'zone'=>$name,'supplier_minimum_eur'=>50.00,
 'flat_eur'=>5.00,'free_above_eur'=>75.00,'free_from_eur'=>75.01,
 'method_settings'=>$seen,'only_mainland_zone'=>true,'vendor_location_overrides'=>0,
 'other_vendor_rows_unchanged'=>true,'destinations'=>$destination_tests,
 'source'=>'merchant_instruction_2026-10-27'
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
