<?php
/**
 * Entresetas 2026-10-29 public catalog launch (29 existing reviewed Woo products)
 * and official circular WCFM seller logo. User-requested publication is not
 * represented as a verified rights or commercial contract authorization.
 * EN_LAUNCH_PHASE=apply|verify. Production only, vendor 4982 exclusively.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
 throw new RuntimeException('WooCommerce, EMDO and WP-CLI required');
}
foreach (['home','siteurl'] as $key) {
 if(!in_array(strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST)),
     ['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Production only.');
}
global $wpdb;
$vendor_id=4982;$code='entresetas';
$suppliers=MDO_Database::table('suppliers');$source=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",$code),ARRAY_A);
$user=get_user_by('id',$vendor_id);
if (!$supplier || (int)$supplier['id']!==10 || (int)$supplier['vendor_user_id']!==$vendor_id ||
    !$user || $user->user_login!=='entresetas' || !in_array('wcfm_vendor',(array)$user->roles,true)) {
 throw new RuntimeException('Unexpected EntreSetas vendor identity.');
}
$rows=$wpdb->get_results($wpdb->prepare(
 "SELECT id,wc_product_id,status,source_payload FROM {$source} WHERE supplier_id=%d ORDER BY id",(int)$supplier['id']),ARRAY_A);
if (count($rows)!==29) throw new RuntimeException('Expected 29 previously reviewed source listings.');
$products=[];
foreach ($rows as $row) {
 if ($row['status']!=='active')throw new RuntimeException('Source item unexpectedly not active: '.$row['id']);
 $pid=(int)$row['wc_product_id'];$product=$pid?wc_get_product($pid):false;
 $payload=json_decode((string)$row['source_payload'],true);
 if (!$product || !is_array($payload) || (int)get_post_field('post_author',$pid)!==$vendor_id ||
      !in_array($product->get_status(),['draft','publish'],true) ||
      (int)$product->get_image_id()<1 || (float)$product->get_price()<=0 ||
      trim(wp_strip_all_tags((string)$product->get_description()))==='' ||
      trim(wp_strip_all_tags((string)$product->get_short_description()))==='' ||
      abs((float)$product->get_price()-(float)($payload['price']??-1))>0.011) {
  throw new RuntimeException('Listing safety gate failed for source #'.$row['id'].' Woo #'.$pid);
 }
 $products[]=$product;
}
$profile=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
if (!is_array($profile) || ($profile['store_name']??'')!=='EntreSetas') throw new RuntimeException('Unexpected WCFM profile.');
$logo_source='https://www.entresetas.com/wp-content/uploads/2021/01/EntreSetas_Logo.png';
$phase=getenv('EN_LAUNCH_PHASE')?:'verify';
if (!in_array($phase,['apply','verify'],true))throw new RuntimeException('Unexpected phase');
if ($phase==='apply') {
 require_once ABSPATH.'wp-admin/includes/media.php';
 require_once ABSPATH.'wp-admin/includes/file.php';
 require_once ABSPATH.'wp-admin/includes/image.php';
 $logo_id=(int)($profile['gravatar']??0);
 if (!$logo_id || get_post_type($logo_id)!=='attachment') {
  $existing=(int)get_user_meta($vendor_id,'_emdo_entresetas_official_logo_20261029',true);
  if ($existing && get_post_type($existing)==='attachment' && get_post_meta($existing,'_emdo_entresetas_official_logo_source',true)===$logo_source) {
   $logo_id=$existing;
  } else {
   $logo_id=media_sideload_image($logo_source,0,'EntreSetas — logotipo oficial del productor','id');
   if(is_wp_error($logo_id)) throw new RuntimeException('Logo original could not be imported: '.$logo_id->get_error_message());
   $logo_id=(int)$logo_id;
   $meta=wp_get_attachment_metadata($logo_id);
   if(!is_array($meta)||(int)($meta['width']??0)<300||(int)($meta['height']??0)<250) {
    wp_delete_attachment($logo_id,true);
    throw new RuntimeException('Official logo file has unexpected size.');
   }
   update_post_meta($logo_id,'_emdo_entresetas_official_logo_source',$logo_source);
   wp_update_post(['ID'=>$logo_id,'post_title'=>'EntreSetas — logotipo oficial']);
   update_user_meta($vendor_id,'_emdo_entresetas_official_logo_20261029',$logo_id);
  }
  $profile['gravatar']=(string)$logo_id;
  update_user_meta($vendor_id,'wcfmmp_profile_settings',$profile);
 }
 // Per-product explicit publication scope: do not authorize future, unreviewed
 // source products automatically or flip rights/contract verification options.
 foreach($products as $product) {
  $id=$product->get_id();
  update_post_meta($id,'_mdo_entresetas_publication_user_requested','1');
  update_post_meta($id,'_mdo_entresetas_publication_requested_date','2026-10-29');
  $product->set_status('publish');
  $product->set_catalog_visibility('visible');
  $product->save();
  wc_delete_product_transients($id);
 }
 if(get_user_meta($vendor_id,'_disable_vendor',true)) {
  delete_user_meta($vendor_id,'_disable_vendor');
 }
 if (class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
 if (function_exists('rocket_clean_domain'))rocket_clean_domain();
 if (function_exists('litespeed_purge_all'))litespeed_purge_all();
 do_action('litespeed_purge_all');
 wp_cache_flush();
 echo 'ENTRESETAS_LAUNCH_APPLIED='.wp_json_encode([
  'vendor_id'=>$vendor_id,'marked'=>count($products),'logo_id'=>(int)$logo_id,
  'logo_url'=>wp_get_attachment_url((int)$logo_id),
  'rights_option_untouched'=>(bool)get_option('mdo_entresetas_media_authorized',false),
  'commercial_option_untouched'=>(bool)get_option('mdo_entresetas_commercial_ready',false)
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
wp_cache_flush();
$published=0;$images=0;$marked=0;$urls=[];
foreach($products as $p) {
 $fresh=wc_get_product($p->get_id());
 if($fresh->get_status()==='publish'&&$fresh->get_catalog_visibility()==='visible' &&
    (int)get_post_field('post_author',$p->get_id())===$vendor_id) $published++;
 $images+=(int)($fresh->get_image_id()>0);
 $marked+=(int)('1'===get_post_meta($p->get_id(),'_mdo_entresetas_publication_user_requested',true));
 if(count($urls)<4)$urls[]=get_permalink($p->get_id());
}
$profile=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
$logo_id=(int)($profile['gravatar']??0);
$logo_url=$logo_id?wp_get_attachment_url($logo_id):false;
$report=[
 'source_rows'=>count($rows),'published_visible'=>$published,'with_covers'=>$images,
 'explicit_publication_marker'=>$marked,'vendor_enabled'=>!get_user_meta($vendor_id,'_disable_vendor',true),
 'logo_id'=>$logo_id,'logo_url'=>$logo_url,'official_logo_source'=>get_post_meta($logo_id,'_emdo_entresetas_official_logo_source',true),
 'logo_valid'=>(bool)($logo_id&&get_post_type($logo_id)==='attachment'&&$logo_url),
 'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($vendor_id):'',
 'sample_links'=>$urls,
 'media_rights_confirmed'=>(bool)get_option('mdo_entresetas_media_authorized',false),
 'commercial_agreement_confirmed'=>(bool)get_option('mdo_entresetas_commercial_ready',false),
];
echo 'ENTRESETAS_LAUNCH_VERIFIED='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($published!==29||$images!==29||$marked!==29||!$report['vendor_enabled']||!$report['logo_valid'])exit(42);
