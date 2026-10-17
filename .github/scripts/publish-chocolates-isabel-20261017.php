<?php
/** WordPress CLI importer: bilingual 30 article chocolate cluster. */
if (!defined('ABSPATH')) { throw new RuntimeException('WordPress only'); }
const EMDO_CHOCOLATE_RELEASE='emdo-chocolates-isabel-20261017-30-bilingual';
$src=getenv('EMDO_CHOCOLATE_PAYLOAD');
if (!$src || !is_readable($src)) { throw new RuntimeException('EMDO_CHOCOLATE_PAYLOAD missing'); }
$manifest=json_decode((string)file_get_contents($src),true,512,JSON_THROW_ON_ERROR);
if (($manifest['release']??'')!==EMDO_CHOCOLATE_RELEASE || count($manifest['posts']??[])!==30) throw new RuntimeException('Manifest invalid');
if (!class_exists('WooCommerce')) throw new RuntimeException('WooCommerce must be loaded');
if (!taxonomy_exists('category')) throw new RuntimeException('Blog category taxonomy absent');
$image_id=(int)get_option('emdo_default_blog_featured_attachment_id',0);
if (!$image_id || get_post_type($image_id)!=='attachment' || !wp_attachment_is_image($image_id)) {
 throw new RuntimeException('Blog common featured image attachment not available');
}
$slugs=[]; $en_slugs=[];
foreach($manifest['posts'] as $item){
 foreach(['es','en'] as $lang){
  foreach(['slug','title','excerpt','seo_title','meta_description','html'] as $key){
   if(!is_string($item[$lang][$key]??null) || !trim($item[$lang][$key])) throw new RuntimeException('Missing '.($item['number']??'?').':'.$lang.':'.$key);
  }
  $plain=wp_strip_all_tags((string)$item[$lang]['html']);
  preg_match_all('/[\p{L}]+/u',$plain,$words);
  if(count($words[0])<620)throw new RuntimeException('Thin article '.($item['number']??'?').':'.$lang);
 }
 if(isset($slugs[$item['es']['slug']]) || isset($en_slugs[$item['en']['slug']]))throw new RuntimeException('Duplicate slug');
 $slugs[$item['es']['slug']]=true;$en_slugs[$item['en']['slug']]=true;
 $existing=get_page_by_path($item['es']['slug'],OBJECT,'post');
 if($existing instanceof WP_Post && get_post_meta($existing->ID,'_emdo_chocolate_release',true)!==EMDO_CHOCOLATE_RELEASE){
  throw new RuntimeException('Collision with existing post: '.$item['es']['slug']);
 }
}
if(getenv('EMDO_CHOCOLATE_DRY_RUN')==='1'){
 echo 'EMDO_CHOCOLATES '.wp_json_encode(['dry_run'=>true,'count'=>30,'common_image_id'=>$image_id],JSON_UNESCAPED_UNICODE)."\n";return;
}
$cat=get_category_by_slug('chocolates-y-cacao'); $new_category=false;
if(!$cat instanceof WP_Term){
 $c=wp_insert_term('Chocolates y cacao','category',['slug'=>'chocolates-y-cacao']);
 if(is_wp_error($c))throw new RuntimeException($c->get_error_message());
 $cat=get_term((int)$c['term_id'],'category');$new_category=true;
}
if(!$cat instanceof WP_Term)throw new RuntimeException('Blog category unavailable');
update_term_meta($cat->term_id,'_en_US_name','Chocolate and cocoa');
update_term_meta($cat->term_id,'_en_US_slug','chocolate-and-cocoa');
update_term_meta($cat->term_id,'_en_US_published','1');
$authors=get_users(['role'=>'administrator','orderby'=>'ID','order'=>'ASC','number'=>1,'fields'=>'ID']);
$author=$authors ? (int)$authors[0] : 1;
$created=[];$snapshots=[];$published=[];
try{
 foreach($manifest['posts'] as $item){
  $es=$item['es'];$en=$item['en'];$existing=get_page_by_path($es['slug'],OBJECT,'post');
  if($existing instanceof WP_Post){
   $id=(int)$existing->ID;
   $snapshots[$id]=['post'=>(array)$existing,'meta'=>get_post_meta($id),'terms'=>wp_get_post_categories($id)];
  }else{$id=0;}
  $post=['post_type'=>'post','post_status'=>'publish','post_title'=>$es['title'],
         'post_name'=>$es['slug'],'post_content'=>$es['html'],'post_excerpt'=>$es['excerpt'],
         'post_author'=>$author,'comment_status'=>'closed','ping_status'=>'closed'];
  if($id)$post['ID']=$id;
  $res=$id?wp_update_post(wp_slash($post),true):wp_insert_post(wp_slash($post),true);
  if(is_wp_error($res)||!(int)$res)throw new RuntimeException('Cannot publish '.$es['slug']);
  $id=(int)$res; if(!$existing)$created[]=$id;
  wp_set_post_categories($id,[(int)$cat->term_id],false);
  foreach([
   '_emdo_chocolate_release'=>EMDO_CHOCOLATE_RELEASE,
   '_emdo_chocolate_topic'=>sprintf('%02d',(int)$item['number']),
   '_emdo_seo_title'=>$es['seo_title'],'_emdo_seo_description'=>$es['meta_description'],
   '_en_US_post_title'=>$en['title'],'_en_US_post_name'=>$en['slug'],
   '_en_US_post_excerpt'=>$en['excerpt'],'_en_US_post_content'=>$en['html'],
   '_en_US_seo_title'=>$en['seo_title'],'_en_US_seo_description'=>$en['meta_description'],
   '_en_US_ready'=>'1','_en_US_published'=>'1',
  ] as $key=>$value)update_post_meta($id,$key,$value);
  set_post_thumbnail($id,$image_id);
  if(get_post_status($id)!=='publish' || !has_category((int)$cat->term_id,$id) || get_post_thumbnail_id($id)!==$image_id){
   throw new RuntimeException('Post integrity failed '.$es['slug']);
  }
  $published[]=['id'=>$id,'number'=>(int)$item['number'],'es'=>get_permalink($id),'en'=>home_url('/en/'.$en['slug'].'/')];
 }
}catch(Throwable $e){
 foreach(array_reverse($created) as $id)wp_delete_post($id,true);
 foreach($snapshots as $id=>$data){
  $old=$data['post'];wp_update_post(wp_slash(['ID'=>$id,'post_status'=>$old['post_status'],'post_title'=>$old['post_title'],'post_name'=>$old['post_name'],'post_content'=>$old['post_content'],'post_excerpt'=>$old['post_excerpt']]),true);
  wp_set_post_categories($id,$data['terms']);
  foreach(get_post_meta($id) as $key=>$unused)delete_post_meta($id,$key);
  foreach($data['meta'] as $key=>$values)foreach($values as $v)add_post_meta($id,$key,maybe_unserialize($v));
 }
 if($new_category)wp_delete_term((int)$cat->term_id,'category');
 throw $e;
}
wp_cache_flush();
if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'EMDO_CHOCOLATES '.wp_json_encode(['verified'=>count($published)===30,'count'=>count($published),'category_id'=>(int)$cat->term_id,'common_image_id'=>$image_id,'posts'=>$published],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";