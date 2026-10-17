<?php
/**
 * Chocolates Artesanos Isabel only. Native WCFM seller shipping by zone.
 * Official checkout policy:
 * Spain mainland EUR4.95; free for orders exceeding EUR59.
 * Balearics EUR7.95; free for orders exceeding EUR100.
 * No other destinations; never modify Latitudes or global WC zones.
 */
if(!defined('ABSPATH'))exit(1);
if(!class_exists('WCFMmp_Shipping_Zone')||!class_exists('WooCommerce'))throw new RuntimeException('WCFM/WooCommerce no disponible');
foreach(array('siteurl','home') as $opt){
 $host=strtolower((string)wp_parse_url(get_option($opt),PHP_URL_HOST));
 if(!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Sólo producción');
}
global $wpdb;
$sup=MDO_Database::table('suppliers');
$vendor=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM {$sup} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'));
$latitudes=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM {$sup} WHERE code=%s LIMIT 1",'latitudes'));
if($vendor!==4964 || !$latitudes || $latitudes===$vendor)throw new RuntimeException('Identificación del vendedor inesperada');
$methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$locations=$wpdb->prefix.'wcfm_marketplace_shipping_zone_locations';
$core_zones=$wpdb->prefix.'woocommerce_shipping_zones';
$core_locs=$wpdb->prefix.'woocommerce_shipping_zone_locations';
$core_methods=$wpdb->prefix.'woocommerce_shipping_zone_methods';
foreach(array($methods,$locations,$core_zones,$core_locs,$core_methods) as $tbl){
 if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$tbl))!==$tbl)throw new RuntimeException('Tabla WCFM o WC no disponible: '.$tbl);
}
$zones=array(1=>array('title'=>'España (península)','flat'=>'4.95','free'=>'59.01'),
             10=>array('title'=>'Baleares','flat'=>'7.95','free'=>'100.01'));
foreach($zones as $zid=>$cfg){
 $found=(string)$wpdb->get_var($wpdb->prepare("SELECT zone_name FROM {$core_zones} WHERE zone_id=%d",$zid));
 if($found!==$cfg['title'])throw new RuntimeException('Zona WooCommerce inesperada: '.$zid.' '.$found);
 $active_gateway=(int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$core_methods} WHERE zone_id=%d AND method_id=%s AND is_enabled=1",$zid,'wcfmmp_product_shipping_by_zone'));
 if(!$active_gateway)throw new RuntimeException('Gateway WCFM global no activo para zona '.$zid);
}
$baleares_locs=$wpdb->get_results($wpdb->prepare("SELECT location_code,location_type FROM {$core_locs} WHERE zone_id=%d",10),ARRAY_A);
if(!array_filter($baleares_locs,static fn($l)=>$l['location_code']==='ES:PM'))throw new RuntimeException('Zona Baleares sin código provincial esperado');
$main_locs=$wpdb->get_results($wpdb->prepare("SELECT location_code,location_type FROM {$core_locs} WHERE zone_id=%d",1),ARRAY_A);
if(array_filter($main_locs,static fn($l)=>in_array($l['location_code'],array('ES:PM','ES:GC','ES:TF'),true)))throw new RuntimeException('Zona peninsular incluye islas no admitidas');
$other_before=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d ORDER BY instance_id",$latitudes),ARRAY_A);
$other_meta_before=get_user_meta($latitudes,'_wcfmmp_shipping',true);
$prior=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d ORDER BY instance_id",$vendor),ARRAY_A);
$before_meta=get_user_meta($vendor,'_wcfmmp_shipping',true);
echo 'ISABEL_SHIPPING_BEFORE='.wp_json_encode(array('isabel_rows'=>count($prior),'isabel_meta'=>$before_meta,'latitudes_rows'=>count($other_before)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
$wpdb->query('START TRANSACTION');
try {
 if(false===$wpdb->delete($methods,array('vendor_id'=>$vendor),array('%d')))throw new RuntimeException('No se pudieron limpiar tarifas antiguas de Isabel');
 if(false===$wpdb->delete($locations,array('vendor_id'=>$vendor),array('%d')))throw new RuntimeException('No se pudieron limpiar ubicaciones antiguas de Isabel');
 foreach($zones as $zid=>$cfg){
  $flat=array('title'=>'Envío a domicilio','description'=>'','cost'=>$cfg['flat'],'tax_status'=>'none',
    'class_cost_no_class_cost'=>'','calculation_type'=>'');
  $free=array('title'=>'Envío gratuito','description'=>'','cost'=>'0','tax_status'=>'none','min_amount'=>$cfg['free']);
  foreach(array('flat_rate'=>$flat,'free_shipping'=>$free) as $method_id=>$settings){
   $ok=$wpdb->insert($methods,array('method_id'=>$method_id,'zone_id'=>(int)$zid,'vendor_id'=>$vendor,
     'is_enabled'=>1,'settings'=>maybe_serialize($settings)),array('%s','%d','%d','%d','%s'));
   if(false===$ok)throw new RuntimeException('Tarifa no creada: '.$zid.' '.$method_id.' '.$wpdb->last_error);
  }
 }
 $meta=is_array($before_meta)?$before_meta:array();
 $meta['_wcfmmp_user_shipping_enable']='yes';
 $meta['_wcfmmp_user_shipping_type']='by_zone';
 update_user_meta($vendor,'_wcfmmp_shipping',$meta);
 $wpdb->query('COMMIT');
} catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
$after=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d ORDER BY zone_id,method_id",$vendor),ARRAY_A);
if(count($after)!==4)throw new RuntimeException('No existen exactamente 4 tarifas para Isabel');
$verified=array();
foreach($after as $r){
 $id=(int)$r['zone_id'];$method=(string)$r['method_id'];$settings=maybe_unserialize($r['settings']);
 if(!isset($zones[$id])||!in_array($method,array('flat_rate','free_shipping'),true)||(int)$r['is_enabled']!==1||!is_array($settings))throw new RuntimeException('Tarifa Isabel inesperada');
 $field=$method==='flat_rate'?'cost':'min_amount';$expected=$method==='flat_rate'?$zones[$id]['flat']:$zones[$id]['free'];
 if(abs((float)($settings[$field]??-1)-(float)$expected)>0.001)throw new RuntimeException('Coste Isabel no coincide');
 $verified[$id][$method]=$settings[$field];
}
$lat_now=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d ORDER BY instance_id",$latitudes),ARRAY_A);
if(wp_json_encode($lat_now)!==wp_json_encode($other_before)||get_user_meta($latitudes,'_wcfmmp_shipping',true)!==$other_meta_before)
 throw new RuntimeException('ALERTA: algún estado de envíos Latitudes ha variado');
$meta_now=get_user_meta($vendor,'_wcfmmp_shipping',true);
if(!is_array($meta_now)||($meta_now['_wcfmmp_user_shipping_type']??'')!=='by_zone'||($meta_now['_wcfmmp_user_shipping_enable']??'')!=='yes')
 throw new RuntimeException('Configuración de envíos Isabel no se guardó');
if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('shipping',true);
wp_cache_flush();
do_action('mdo_shipping_destinations_invalidate');
echo 'ISABEL_SHIPPING_APPLIED='.wp_json_encode(array('vendor_id'=>$vendor,'zones'=>$verified,
  'other_vendor_methods_unchanged'=>true,'global_zones_unchanged'=>true,'other_destinations'=>false),
 JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
