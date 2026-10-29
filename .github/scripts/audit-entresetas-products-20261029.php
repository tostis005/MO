<?php
/** Read-only auditing of the EntreSetas WCFM catalog. */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) throw new RuntimeException('WooCommerce and EMDO required');
foreach (['home','siteurl'] as $key) {
  if (!in_array(strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Not production.');
}
global $wpdb;
$table=MDO_Database::table('suppliers');$sources=MDO_Database::table('source_products');
$sup=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE code=%s LIMIT 1",'entresetas'),ARRAY_A);
if (!$sup || (int)$sup['vendor_user_id']!==4982) throw new RuntimeException('EntreSetas supplier mapping changed.');
$user=get_user_by('id',4982);
if (!$user||$user->user_login!=='entresetas') throw new RuntimeException('Unexpected vendor identity.');
$shippingTable=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$zoneTable=$wpdb->prefix.'woocommerce_shipping_zones';
$methods=$wpdb->get_results($wpdb->prepare("SELECT zone_id,method_id,is_enabled,settings FROM {$shippingTable} WHERE vendor_id=%d ORDER BY zone_id,method_id",4982),ARRAY_A);
echo 'ENTRESETAS_SHIPPING='.wp_json_encode([
 'vendor_id'=>4982,'vendor_disabled'=>(bool)get_user_meta(4982,'_disable_vendor',true),
 'seller_shipping_meta'=>get_user_meta(4982,'_wcfmmp_shipping',true),
 'vendor_methods'=>array_map(static function($m){$m['settings']=maybe_unserialize($m['settings']);return $m;},$methods),
 'global_zone_names'=>$wpdb->get_results("SELECT zone_id,zone_name FROM {$zoneTable} ORDER BY zone_order",ARRAY_A),
 'supplier_minimum'=>$sup['minimum_order_amount']
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$sources} WHERE supplier_id=%d ORDER BY id",(int)$sup['id']),ARRAY_A);
$c=['source_rows'=>count($rows),'woo_exists'=>0,'draft_hidden'=>0,'cover_images'=>0,'source_images'=>0,
 'short_present'=>0,'long_present'=>0,'prices_equal'=>0,'weights_equal'=>0,'with_categories'=>0,'unknown_categories'=>0];
foreach($rows as $r){
 $payload=json_decode((string)$r['source_payload'],true);$p=is_array($payload)?$payload:[];
 $wc_id=(int)$r['wc_product_id'];$w=$wc_id?wc_get_product($wc_id):false;
 $src_price=isset($p['price'])?(float)$p['price']:null;
 $wc_price=$w?(float)$w->get_price('edit'):null;
 $src_long=trim(wp_strip_all_tags((string)($p['description']??'')));
 $dst_long=$w?trim(wp_strip_all_tags((string)$w->get_description('edit'))):'';
 $src_short=trim(wp_strip_all_tags((string)($p['short_description']??'')));
 $dst_short=$w?trim(wp_strip_all_tags((string)$w->get_short_description('edit'))):'';
 $images=(array)($p['images']??[]);$src_weight=isset($p['weight_kg'])?(float)$p['weight_kg']:null;
 $wc_weight=$w?$w->get_weight('edit'):null;
 $termids=$w?$w->get_category_ids():[];
 $catnames=[];
 foreach($termids as $termid){$t=get_term((int)$termid,'product_cat');if($t&&!is_wp_error($t))$catnames[]=(string)$t->name;}
 $price_ok=$w&&$src_price!==null&&abs($src_price-$wc_price)<0.011;
 $draft_ok=$w&&'draft'===$w->get_status()&&'hidden'===$w->get_catalog_visibility()&&(int)get_post_field('post_author',$wc_id)===4982;
 $weight_ok=$w&&($src_weight===null||abs($src_weight-(float)$wc_weight)<0.0001);
 $c['woo_exists']+=(int)(bool)$w;
 $c['draft_hidden']+=(int)$draft_ok;
 $c['cover_images']+=(int)($w&&$w->get_image_id()>0);
 $c['source_images']+=(int)(count($images)>0);
 $c['short_present']+=(int)($dst_short!=='');
 $c['long_present']+=(int)($dst_long!=='');
 $c['prices_equal']+=(int)$price_ok;
 $c['weights_equal']+=(int)$weight_ok;
 $c['with_categories']+=(int)(count($catnames)>0);
 $c['unknown_categories']+=(int)(!$catnames);
 echo 'ENTRESETAS_ITEM='.wp_json_encode([
  'id'=>$wc_id,'source_id'=>(int)$r['id'],'name'=>$r['title'],
  'source_url'=>$r['source_url'],'sku'=>$w?$w->get_sku('edit'):null,
  'source_price'=>$src_price,'woo_price'=>$wc_price,'price_match'=>$price_ok,
  'source_imgs'=>count($images),'sample_source_image'=>count($images)?$images[0]:null,
  'woo_image_id'=>$w?$w->get_image_id():0,
  'source_long_chars'=>mb_strlen($src_long),'woo_long_chars'=>mb_strlen($dst_long),
  'source_short_chars'=>mb_strlen($src_short),'woo_short_chars'=>mb_strlen($dst_short),
  'short_same'=>$src_short===$dst_short,'long_same'=>$src_long===$dst_long,
  'source_weight_kg'=>$src_weight,'wc_weight_kg'=>$wc_weight,
  'categories'=>$catnames,'draft_hidden'=>$draft_ok,
  'source_status'=>$r['status'],'stock_source'=>$p['stock_status']??null,'stock_woo'=>$w?$w->get_stock_status():null
 ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
}
echo 'ENTRESETAS_SUMMARY='.wp_json_encode($c,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
