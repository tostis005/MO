<?php
/**
 * Strict, bounded clean-up of the 35 Tropitop fiches flagged by the 21 Oct
 * retailer-voice QA. Mode audit (default) prints planned edits; apply writes.
 * This never touches product categories, pricing, shipping, stock, options,
 * URLs, images, statuses or other suppliers.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(1);
$hostname=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($hostname,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong site');
foreach(['WooCommerce','MDO_Database','MDO_Tropitop_Editorial'] as $needed)if(!class_exists($needed))throw new RuntimeException('Unavailable '.$needed);
$mode=(string)(getenv('TROPITOP_COPY_MODE')?:'audit');
if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
$target_ids=[18307,18491,18541,19519,19571,19587,19718,19732,19746,19762,
 19924,19937,19962,20006,20120,20145,20164,20181,20278,20293,
 20370,20385,20399,20413,20423,20438,20452,20466,20482,20654,
 21103,21187,21126,21147,21199];
if(count($target_ids)!==35||count(array_unique($target_ids))!==35)throw new RuntimeException('Target list must contain exactly 35 unique products');
global $wpdb;
$source=MDO_Database::table('source_products');$suppliers=MDO_Database::table('suppliers');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$suppliers} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$supplier)throw new RuntimeException('Tropitop vendor absent');
$vendor=(int)$supplier['vendor_user_id'];
$linked=$wpdb->get_col($wpdb->prepare("SELECT wc_product_id FROM {$source} WHERE supplier_id=%d AND wc_product_id>0",(int)$supplier['id']));
$linked=array_flip(array_map('intval',(array)$linked));
$summary=['mode'=>$mode,'targets'=>35,'reviewed'=>0,'changed'=>0,'titles_changed'=>0,'description_changed'=>0,
 'short_changed'=>0,'remaining_voice'=>0,'missing_specs'=>0,'errors'=>0,'category_drift'=>0];
$guard='~\b(?:trabajamos|nos\s+enorgullece|nos\s+comprometemos|Tropitop\s+(?:ofrece|garantiza|presenta)|elegir\s+Tropitop|con\s+Tropitop|de\s+confianza\s+de\s+Tropitop|(?:comprar|compra)\s+[^.!?]{0,100}\bonline|aceptamos\s+pagos)\b~iu';
foreach($target_ids as $id){
 try {
  if(!isset($linked[$id]))throw new RuntimeException('ID not linked to Tropitop source');
  $product=wc_get_product($id);
  if(!$product || $product->is_type('variation') ||
    (int)get_post_field('post_author',$id)!==$vendor)throw new RuntimeException('Product/author mismatch');
  ++$summary['reviewed'];
  $name=(string)$product->get_name('edit');
  $before=(string)$product->get_description('edit');
  $short=(string)$product->get_short_description('edit');
  $cats=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
  if(is_wp_error($cats))throw new RuntimeException('Failed to retrieve categories');
  $cats=array_map('intval',(array)$cats);sort($cats);
  $after_name=MDO_Tropitop_Editorial::title($name);
  $after=MDO_Tropitop_Editorial::description($before,$name,$short);
  $after_short=MDO_Tropitop_Editorial::short_description($short);
  $before_text=trim(wp_strip_all_tags($before));$after_text=trim(wp_strip_all_tags($after));
  if(mb_strlen($after_text)<80)throw new RuntimeException('Result too short, human editorial review needed');
  if(mb_strlen($after_text)<0.37*max(1,mb_strlen($before_text)))throw new RuntimeException('Excessive loss of source facts');
  // Every measurement remains somewhere in the product description.
  // All individual sizes/weights are crucial to product selection.
  $num='/\b\d+(?:[.,]\d+)?\s*(?:kg|g|ml|litros?|unidades?|uds?|botellas?|botes?|tarros?)\b/iu';
  preg_match_all($num,$before_text,$before_hits);
  $after_fold=mb_strtolower(preg_replace('/\s+/u','', $after_text));
  $unmatched=[];
  foreach(array_unique($before_hits[0]) as $spec){
    $k=mb_strtolower(preg_replace('/\s+/u','',$spec));
    if(!str_contains($after_fold,$k))$unmatched[]=$spec;
  }
  if($unmatched) {
    ++$summary['missing_specs'];
    throw new RuntimeException('Quantities dropped: '.implode(', ',array_slice($unmatched,0,8)));
  }
  $voice=MDO_Tropitop_Editorial::residual_markers($after.' '.$after_short);
  $plain=wp_strip_all_tags($after.' '.$after_short);
  if(preg_match($guard,$plain)||$voice){
    ++$summary['remaining_voice'];
    preg_match($guard,$plain,$match);
    throw new RuntimeException('Retailer pitch remains: '.($match[0]??implode(',',$voice)));
  }
  $change=($name!==$after_name)||($before!==$after)||($short!==$after_short);
  if($change){
    ++$summary['changed'];
    if($name!==$after_name)++$summary['titles_changed'];
    if($before!==$after)++$summary['description_changed'];
    if($short!==$after_short)++$summary['short_changed'];
  }
  echo 'TROPITOP_COPY_PRODUCT='.wp_json_encode([
    'id'=>$id,'sku'=>(string)$product->get_sku(),'title'=>$name,
    'text_changed'=>$change,'title_changed'=>$name!==$after_name,
    'chars_before'=>mb_strlen($before_text),'chars_after'=>mb_strlen($after_text),
    'qa_pass'=>true
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
  if($mode==='apply' && $change){
    $saved_fields=['description'=>$before,'short_description'=>$short,'title'=>$name];
    if(!metadata_exists('post',$id,'_emdo_tropitop_editorial_backup_20261021'))
      add_post_meta($id,'_emdo_tropitop_editorial_backup_20261021',$saved_fields,true);
    $updated=wp_update_post(['ID'=>$id,'post_title'=>$after_name,'post_content'=>$after,'post_excerpt'=>$after_short],true);
    if(is_wp_error($updated)||(int)$updated!==$id)throw new RuntimeException('WordPress update failed');
    clean_post_cache($id);
    if(function_exists('wc_delete_product_transients'))wc_delete_product_transients($id);
    $categories_after=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
    if(is_wp_error($categories_after))throw new RuntimeException('Categories after update unreadable');
    $categories_after=array_map('intval',(array)$categories_after);sort($categories_after);
    if($categories_after!==$cats){
      wp_set_object_terms($id,$cats,'product_cat',false);
      ++$summary['category_drift'];
      throw new RuntimeException('Unexpected category modification reverted');
    }
    $saved=wc_get_product($id);
    if(!$saved||trim(wp_strip_all_tags($saved->get_description('edit')))!==$after_text)
      throw new RuntimeException('Saved description does not match verified copy');
  }
 }catch(Throwable $e){
  ++$summary['errors'];
  echo 'TROPITOP_COPY_ERROR='.wp_json_encode(['id'=>$id,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 }
}
echo 'TROPITOP_COPY_SUMMARY='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($summary['errors']!==0||$summary['reviewed']!==35)
  throw new RuntimeException('Product copy safety gate failed');
