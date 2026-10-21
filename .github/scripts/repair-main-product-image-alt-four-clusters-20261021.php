<?php
/** One-off, image-only ALT repair for proven purchasable food products. */
if(!defined('ABSPATH')||!function_exists('wc_get_product'))throw new RuntimeException('WooCommerce required');
$products=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>800,'fields'=>'ids']);
$set=[];$source=[];
foreach($products as $pid){
  $p=wc_get_product((int)$pid);
  if(!$p||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock())continue;
  $terms=wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);
  if(is_wp_error($terms))continue;
  $title=remove_accents(mb_strtolower($p->get_name()));
  $allowed=in_array('cafe-de-especialidad',$terms,true)
      || in_array('aguacates',$terms,true)
      || in_array('chocolates-artesanos',$terms,true)
      || (bool)array_intersect($terms,['bombones-y-chocolatinas','tabletas-de-chocolate','cremas-de-cacao','cacao-para-beber-y-cocinar'])
      || ((bool)array_intersect($terms,['frutas','frutas-tropicales','frutas-del-mundo','packs-y-lotes'])
          && (bool)preg_match('/(?<![a-z])(mangos?|naranjas?|mandarinas?|pomelos?)(?![a-z])/u',$title));
  if(!$allowed)continue;
  $image=(int)$p->get_image_id();
  if(!$image || !wp_attachment_is_image($image))continue;
  if(!isset($source[$image]))$source[$image]=[];
  $source[$image][]=['id'=>$pid,'name'=>trim(wp_strip_all_tags($p->get_name()))];
}
$skipped_shared=0;
foreach($source as $image=>$all){
  $titles=array_unique(array_map(static fn($a)=>mb_strtolower($a['name']),$all));
  if(count($titles)>1){$skipped_shared++;continue;}
  $original=(string)get_post_meta($image,'_wp_attachment_image_alt',true);
  $es=trim(preg_replace('/\s+/u',' ',(string)$all[0]['name']));
  $es=mb_substr($es,0,125);
  if($original===''){
    $set[$image]=['name'=>$es,'product'=>$all[0]['id'],'english'=>''];
    $en_title=(string)get_post_meta($all[0]['id'],'_en_US_post_title',true);
    if($en_title!=='' && (string)get_post_meta($all[0]['id'],'_en_US_published',true)==='1'){
      $set[$image]['english']=mb_substr(trim(wp_strip_all_tags($en_title)),0,125);
    }
  }
}
if(count($set)>300)throw new RuntimeException('Suspicious bulk ALT changes');
if(getenv('EMDO_PRODUCT_ALT_PREFLIGHT')==='1'){
 echo 'EMDO_PRODUCT_ALT_PREFLIGHT '.wp_json_encode(['ok'=>true,'candidates'=>count($source),'missing'=>count($set),'shared_skipped'=>$skipped_shared,
   'preview'=>array_slice(array_values($set),0,6)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 return;
}
$changed=0;$englishChanged=0;
foreach($set as $id=>$item){
 if((string)get_post_meta($id,'_wp_attachment_image_alt',true)!=='')continue;
 if(!metadata_exists('post',$id,'_emdo_cluster_20261021_original_alt'))update_post_meta($id,'_emdo_cluster_20261021_original_alt','');
 update_post_meta($id,'_wp_attachment_image_alt',$item['name']);
 if($item['english']!=='' && (string)get_post_meta($id,'_emdo_alt_en',true)===''){
   update_post_meta($id,'_emdo_alt_en',$item['english']);$englishChanged++;
 }
 $changed++;
}
wp_cache_flush();
echo 'EMDO_PRODUCT_ALT_APPLIED '.wp_json_encode(['ok'=>true,'updated'=>$changed,'english_updated'=>$englishChanged,'shared_skipped'=>$skipped_shared],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
