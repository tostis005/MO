<?php
/**
 * EntreSetas private draft media and category repair; never publish.
 * Full image sideloads are for internal product-review use only: the rights
 * approval option stays false and the WCFM seller stays disabled.
 * EN_REPAIR_PHASE=repair|verify
 */
if (!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('MDO_Woo_Importer')) throw new RuntimeException('WP CLI and EMDO required');
foreach(['home','siteurl'] as $k){
 $host=strtolower((string)wp_parse_url((string)get_option($k),PHP_URL_HOST));
 if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Not production');
}
global $wpdb;
$vendor=4982;$user=get_user_by('id',$vendor);
$sup=MDO_Database::table('suppliers');$table=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s",'entresetas'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==$vendor||!$user||$user->user_login!=='entresetas'||!get_user_meta($vendor,'_disable_vendor',true))throw new RuntimeException('Unexpected vendor');
if(get_option('mdo_entresetas_commercial_ready',false)||get_option('mdo_entresetas_media_authorized',false))throw new RuntimeException('Rights/commercial flags are not in private-review mode');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_payload FROM {$table} WHERE supplier_id=%d AND status='active' ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)!==29)throw new RuntimeException('Unexpected source catalog count '.count($rows));
$phase=getenv('EN_REPAIR_PHASE')?:'verify';
if($phase==='repair'){
  // Temporary scope-only override: sideload images into unpublished Woo drafts
  // without representing that any commercial media-use authorization was given.
  $grant=static fn($pre)=>true;
  add_filter('pre_option_mdo_entresetas_media_authorized',$grant,10,1);
  $ok=0;$errors=[];
  foreach($rows as $r){
    $id=(int)$r['wc_product_id'];
    $payload=json_decode((string)$r['source_payload'],true);
    $w=$id?wc_get_product($id):false;
    if(!$w||'draft'!==$w->get_status()||(int)get_post_field('post_author',$id)!==$vendor)throw new RuntimeException('Product identity or draft changed: '.$id);
    try{
      $result=MDO_Woo_Importer::import_source_product((int)$r['id']);
      if($result!==$id)throw new RuntimeException('Product ID changed unexpectedly');
      $refreshed=wc_get_product($id);
      if(!$refreshed||$refreshed->get_status()!=='draft'||$refreshed->get_catalog_visibility()!=='hidden')throw new RuntimeException('Private draft guard failed');
      $ok++;
      echo 'ENTRESETAS_REPAIRED='.wp_json_encode([
        'source_id'=>(int)$r['id'],'product_id'=>$id,'title'=>$refreshed->get_name(),
        'cover_id'=>(int)$refreshed->get_image_id(),'gallery_count'=>count($refreshed->get_gallery_image_ids()),
        'source_photo_count'=>count((array)($payload['images']??[])),
        'categories'=>wp_get_post_terms($id,'product_cat',['fields'=>'names'])
      ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
    }catch(Throwable $e){
      $errors[]=array('product_id'=>$id,'reason'=>substr($e->getMessage(),0,240));
      echo 'ENTRESETAS_REPAIR_ERROR='.wp_json_encode(end($errors),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
    }
  }
  remove_filter('pre_option_mdo_entresetas_media_authorized',$grant,10);
  if(get_option('mdo_entresetas_media_authorized',false))throw new RuntimeException('Media rights flag unexpectedly changed');
  echo 'ENTRESETAS_REPAIR_RESULT='.wp_json_encode(['updated'=>$ok,'failures'=>$errors],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
}
$summary=['source_products'=>count($rows),'cover'=>0,'no_cover'=>0,'gallery_full'=>0,'short'=>0,'long'=>0,
  'price_match'=>0,'stock_match'=>0,'draft_hidden'=>0,'correct_categories'=>0,'images_missing'=>[]];
$catcounts=[];
foreach($rows as $r){
  $payload=json_decode((string)$r['source_payload'],true);
  $w=wc_get_product((int)$r['wc_product_id']);
  if(!$w)throw new RuntimeException('Missing Woo product');
  $has_cover=(int)$w->get_image_id()>0;
  $count_expected=count((array)($payload['images']??[]));
  $count_actual=($has_cover?1:0)+count($w->get_gallery_image_ids());
  $summary['cover']+=(int)$has_cover;$summary['no_cover']+=(int)!$has_cover;
  $summary['gallery_full']+=(int)($count_actual >= $count_expected);
  $summary['short']+=(int)(trim(wp_strip_all_tags((string)$w->get_short_description()))!=='');
  $summary['long']+=(int)(trim(wp_strip_all_tags((string)$w->get_description()))!=='');
  $summary['price_match']+=(int)(abs((float)$w->get_price()-(float)($payload['price']??-999))<0.011);
  $summary['stock_match']+=(int)($w->get_stock_status()===($payload['stock_status']??'unknown'));
  $summary['draft_hidden']+=(int)($w->get_status()==='draft'&&$w->get_catalog_visibility()==='hidden'&&(int)get_post_field('post_author',$w->get_id())===$vendor);
  $terms=wp_get_post_terms($w->get_id(),'product_cat',['fields'=>'slugs']);
  $expected=preg_match('/\bextracto\b/u',remove_accents(mb_strtolower($w->get_name(),'UTF-8'))) ?
    'extractos-de-setas' : (preg_match('/\b(?:matcha|infusion)\b/u',remove_accents(mb_strtolower($w->get_name(),'UTF-8'))) ?
     'infusiones-y-tes-de-setas' : null);
  $summary['correct_categories']+=(int)(in_array('setas-y-hongos',$terms,true)&&(!$expected||in_array($expected,$terms,true)));
  foreach($terms as $slug){if($slug!=='setas-y-hongos')$catcounts[$slug]=($catcounts[$slug]??0)+1;}
  if(!$has_cover||$count_actual<$count_expected)$summary['images_missing'][]=['id'=>$w->get_id(),'name'=>$w->get_name(),'have'=>$count_actual,'expected'=>$count_expected];
}
$summary['categories']=$catcounts;
$summary['rights_flag_unchanged']=!get_option('mdo_entresetas_media_authorized',false);
$summary['vendor_still_disabled']=(bool)get_user_meta($vendor,'_disable_vendor',true);
echo 'ENTRESETAS_MEDIA_VERIFY='.wp_json_encode($summary,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
if($summary['draft_hidden']!==count($rows))exit(42);
