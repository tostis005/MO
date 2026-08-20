<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wpdb;

function mdo_diag_source( ReflectionFunctionAbstract $r ): string {
    $file = $r->getFileName();
    if ( ! $file || ! is_readable( $file ) ) return '';
    $lines = file( $file );
    if ( ! is_array( $lines ) ) return '';
    $start = max( 0, $r->getStartLine() - 3 );
    $len = max( 1, $r->getEndLine() - $start + 3 );
    return implode( '', array_slice( $lines, $start, $len ) );
}

$out = array(
    'generated_at' => gmdate( 'c' ),
    'siteurl' => get_option( 'siteurl' ),
    'wcfm_function' => array(),
    'wcfm_zone_class' => array(),
    'shipping_tables' => array(),
    'woocommerce_zones' => array(),
    'vendors' => array(),
    'resolver_supported_countries' => null,
    'frontend_supported_countries' => null,
);

if ( function_exists( 'wcfmmp_get_shipping_zone' ) ) {
    try {
        $rf = new ReflectionFunction( 'wcfmmp_get_shipping_zone' );
        $out['wcfm_function'] = array(
            'exists' => true,
            'file' => $rf->getFileName(),
            'start' => $rf->getStartLine(),
            'end' => $rf->getEndLine(),
            'parameters' => array_map( static function ( ReflectionParameter $p ) {
                return array('name'=>$p->getName(),'optional'=>$p->isOptional(),'default'=>$p->isDefaultValueAvailable()?$p->getDefaultValue():null);
            }, $rf->getParameters() ),
            'source' => mdo_diag_source( $rf ),
        );
    } catch ( Throwable $e ) { $out['wcfm_function'] = array('exists'=>true,'error'=>$e->getMessage()); }
} else { $out['wcfm_function'] = array('exists'=>false); }

if ( class_exists( 'WCFMmp_Shipping_Zone' ) ) {
    try {
        $rc = new ReflectionClass( 'WCFMmp_Shipping_Zone' );
        $out['wcfm_zone_class']['file'] = $rc->getFileName();
        foreach ( array('get_zones','get_zone','get_shipping_methods','get_shipping_method','get_shipping_zone_locations') as $method ) {
            if ( $rc->hasMethod( $method ) ) {
                $rm = $rc->getMethod( $method );
                $out['wcfm_zone_class']['methods'][$method] = array(
                    'start'=>$rm->getStartLine(),'end'=>$rm->getEndLine(),'source'=>mdo_diag_source($rm),
                );
            }
        }
    } catch ( Throwable $e ) { $out['wcfm_zone_class']['error'] = $e->getMessage(); }
}

$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%shipping%'" );
foreach ( (array) $tables as $table ) {
    if ( ! preg_match('/^[A-Za-z0-9_]+$/', $table) ) continue;
    $out['shipping_tables'][$table] = array(
        'columns' => $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A ),
        'rows' => $wpdb->get_results( "SELECT * FROM `{$table}` LIMIT 250", ARRAY_A ),
    );
}

if ( class_exists( 'WC_Shipping_Zones' ) ) {
    foreach ( WC_Shipping_Zones::get_zones() as $zone_id => $zone ) {
        $out['woocommerce_zones'][(string)$zone_id] = array(
            'zone_id'=>$zone['zone_id']??$zone_id,
            'zone_name'=>$zone['zone_name']??'',
            'zone_order'=>$zone['zone_order']??null,
            'zone_locations'=>$zone['zone_locations']??array(),
            'shipping_methods'=>array_map(static function($m){return array(
                'id'=>is_object($m)?($m->id??''):'','instance_id'=>is_object($m)?($m->instance_id??''):'',
                'enabled'=>is_object($m)?($m->enabled??''):'','title'=>is_object($m)?($m->title??''):'',
            );},(array)($zone['shipping_methods']??array())),
        );
    }
}

$vendor_ids = get_users( array( 'role'=>'wcfm_vendor', 'fields'=>'ids' ) );
foreach ( (array) $vendor_ids as $vendor_id ) {
    $vendor_id = absint($vendor_id);
    $u = get_userdata($vendor_id);
    $shipping_meta = $wpdb->get_results( $wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key LIKE %s ORDER BY meta_key",
        $vendor_id, '%shipping%'
    ), ARRAY_A );
    foreach ( $shipping_meta as &$row ) {
        $maybe = maybe_unserialize($row['meta_value']);
        $row['decoded'] = $maybe;
        unset($row['meta_value']);
    }
    unset($row);
    $entry = array(
        'id'=>$vendor_id,
        'name'=>$u?$u->display_name:'',
        'shipping_meta'=>$shipping_meta,
        'resolver_country_codes'=>null,
        'wcfm_zones'=>null,
    );
    if ( class_exists('MDO_Shipping_Destinations') ) {
        try { $entry['resolver_country_codes']=MDO_Shipping_Destinations::vendor_country_codes($vendor_id); }
        catch(Throwable $e){$entry['resolver_country_codes']=array('error'=>$e->getMessage());}
    }
    if ( function_exists('wcfmmp_get_shipping_zone') ) {
        try { $entry['wcfm_zones']=wcfmmp_get_shipping_zone('', $vendor_id); }
        catch(Throwable $e){$entry['wcfm_zones']=array('error'=>$e->getMessage());}
    }
    $out['vendors'][]=$entry;
}

if ( class_exists('MDO_Shipping_Destinations') ) {
    try {$out['resolver_supported_countries']=MDO_Shipping_Destinations::supported_countries(true);}
    catch(Throwable $e){$out['resolver_supported_countries']=array('error'=>$e->getMessage());}
}
if ( class_exists('MDO_Catalog_Destination_Frontend') ) {
    try {$out['frontend_supported_countries']=MDO_Catalog_Destination_Frontend::supported_countries();}
    catch(Throwable $e){$out['frontend_supported_countries']=array('error'=>$e->getMessage());}
}

echo wp_json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
