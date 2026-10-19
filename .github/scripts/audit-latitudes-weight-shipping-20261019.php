<?php
/** Read-only production audit: Latitudes product weights and WCFM shipping capabilities. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
foreach (['siteurl','home'] as $opt) {
  $host = strtolower((string)wp_parse_url(get_option($opt), PHP_URL_HOST));
  if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) WP_CLI::error('Wrong site');
}
if (!function_exists('wc_get_product') || !class_exists('WC_Shipping_Zones') || !class_exists('MDO_Database')) WP_CLI::error('Missing required plugins');
global $wpdb;
$t = MDO_Database::table('suppliers');
$s = $wpdb->get_row($wpdb->prepare("SELECT id, vendor_user_id FROM $t WHERE code=%s",'latitudes'), ARRAY_A);
if (!$s || (int)$s['vendor_user_id'] !== 4949) WP_CLI::error('Unexpected Latitudes vendor');
$vendor=4949;
$zone_table=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$all_methods=$wpdb->get_results($wpdb->prepare("SELECT zone_id,method_id,is_enabled,settings FROM $zone_table WHERE vendor_id=%d ORDER BY zone_id,method_id", $vendor),ARRAY_A);
foreach ($all_methods as &$r) $r['settings']=maybe_unserialize($r['settings']);
unset($r);
$shipping=get_user_meta($vendor, '_wcfmmp_shipping', true);
$relevant_user_meta=$wpdb->get_results($wpdb->prepare("SELECT meta_key, LEFT(meta_value,350) AS sample FROM {$wpdb->usermeta} WHERE user_id=%d AND (meta_key LIKE %s OR meta_key LIKE %s) ORDER BY meta_key", $vendor,'%ship%','%weight%'),ARRAY_A);
$plugins=array();
foreach (get_option('active_plugins',[]) as $plugin) if (stripos($plugin,'wcfm')!==false || stripos($plugin,'shipping')!==false) $plugins[]=$plugin;
$classes=array_values(array_filter(get_declared_classes(),static fn($n)=>preg_match('/WCFM.*Shipping|Shipping.*Weight|WCFMmp/i',$n)));
$ref=[];
foreach($classes as $name) {
  if (!preg_match('/shipping|weight/i',$name)) continue;
  $c=new ReflectionClass($name);
  $ref[]=['name'=>$name,'file'=>str_replace(ABSPATH,'',$c->getFileName()?:''),'methods'=>array_values(array_filter(array_map(static fn($m)=>$m->name,$c->getMethods()),static fn($m)=>preg_match('/weight|rate|cost|ship|setting/i',$m)))];
}
WP_CLI::log('LWS_SETTINGS='.wp_json_encode(['vendor'=>$vendor,'units'=>get_option('woocommerce_weight_unit'),'zone_methods'=>$all_methods,'shipping'=>$shipping,'vendor_meta'=>$relevant_user_meta,'plugins'=>$plugins,'classes'=>$ref],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$ids=get_posts(['post_type'=>'product','post_status'=>'any','author'=>$vendor,'fields'=>'ids','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
$source_table=MDO_Database::table('source_products');
$stats=['total'=>count($ids),'blank'=>0,'positive'=>0,'weight_strings'=>0];
foreach($ids as $id){
  $p=wc_get_product($id);
  if (!$p) continue;
  $sid=(int)get_post_meta($id,'_emdo_source_product_id',true);
  $src=$sid ? $wpdb->get_row($wpdb->prepare("SELECT title,source_url,source_payload FROM $source_table WHERE id=%d AND supplier_id=%d LIMIT 1", $sid, (int)$s['id']),ARRAY_A):[];
  $payload=$src ? json_decode((string)$src['source_payload'],true):[];
  $txt=html_entity_decode(wp_strip_all_tags($p->get_description()." ".$p->get_short_description()),ENT_QUOTES|ENT_HTML5,'UTF-8');
  $hits=[];
  preg_match_all('/(?:\d{1,3}(?:[.,]\d{1,3})?\s*(?:kg|kilo(?:s|gramos)?|g|grs?\.?|gramos)\b|[1-9]\d?\s*[x×]\s*\d{2,4}\s*(?:g|grs?))\b/ui',$p->get_name().' '.$txt,$mm);
  if (!empty($mm[0])) $hits=array_values(array_unique(array_slice($mm[0],0,8)));
  $weight=(string)$p->get_weight('edit');
  $stats[is_numeric($weight) && (float)$weight>0 ? 'positive':'blank']++;
  if($hits) $stats['weight_strings']++;
  WP_CLI::log('LWS_PRODUCT='.wp_json_encode([
     'id'=>(int)$id,'status'=>$p->get_status(),'name'=>$p->get_name(),
     'weight'=>$weight,'source_weight'=>$payload['weight']??null,
     'source_keys'=>is_array($payload)?array_keys($payload):[],
     'source_title'=>$src['title']??'','source_url'=>$src['source_url']??'',
     'hits'=>$hits,'description_head'=>mb_substr($txt,0,180)
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
WP_CLI::log('LWS_STATS='.wp_json_encode($stats));
WP_CLI::success('Latitudes read-only audit complete');
