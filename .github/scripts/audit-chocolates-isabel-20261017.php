<?php
/** Read-only final QA of the Chocolates Isabel bilingual release. */
if(!defined('ABSPATH'))throw new RuntimeException('Requires WordPress');
const EMDO_CHAUD='emdo-chocolates-isabel-20261017-30-bilingual';
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>60,'meta_key'=>'_emdo_chocolate_release','meta_value'=>EMDO_CHAUD,'suppress_filters'=>true]);
if(count($posts)!==30)throw new RuntimeException('Expected 30 published posts, found '.count($posts));
$image=(int)get_option('emdo_default_blog_featured_attachment_id',0);
if($image<1||!wp_attachment_is_image($image))throw new RuntimeException('Missing shared image');
$cat=get_category_by_slug('chocolates-y-cacao');
if(!$cat instanceof WP_Term)throw new RuntimeException('Missing category');
$numbers=[];$pages=[];
foreach($posts as $p){
 $id=(int)$p->ID;$n=(int)get_post_meta($id,'_emdo_chocolate_topic',true);
 if(!$n||$n>30||isset($numbers[$n]))throw new RuntimeException('Topic collision '.$n);
 $numbers[$n]=true;
 if((int)get_post_thumbnail_id($id)!==$image)throw new RuntimeException('Wrong image '.$id);
 if(!has_category($cat->term_id,$id))throw new RuntimeException('Missing category '.$id);
 if(!get_post_meta($id,'_en_US_published',true)||!get_post_meta($id,'_en_US_post_title',true)||!get_post_meta($id,'_en_US_post_content',true))throw new RuntimeException('Missing EN '.$id);
 $es=(string)$p->post_content;$en=(string)get_post_meta($id,'_en_US_post_content',true);
 if(mb_strlen(wp_strip_all_tags($es))<2300||mb_strlen(wp_strip_all_tags($en))<2300)throw new RuntimeException('Thin article '.$id);
 $en_slug=(string)get_post_meta($id,'_en_US_post_name',true);
 if(!$en_slug)throw new RuntimeException('EN slug missing '.$id);
 $pages[$n]=['id'=>$id,'es'=>get_permalink($id),'en'=>home_url('/en/'.$en_slug.'/')];
}
ksort($pages);
if(array_keys($pages)!==range(1,30))throw new RuntimeException('Non-contiguous topics');
if(!function_exists('emdo_choco_articles')||!function_exists('emdo_choco_related_products'))throw new RuntimeException('Dynamic module not loaded');
$term_slugs=emdo_choco_categories();
if(count($term_slugs)<4)throw new RuntimeException('Product category mapping incomplete');
$product_total=0;$product_url='';
foreach($term_slugs as $slug){
 $products=wc_get_products(['status'=>'publish','category'=>[$slug],'limit'=>12]);
 foreach($products as $product){
  if(!$product->is_visible()||!$product->is_in_stock()||$product->get_stock_status()!=='instock')continue;
  $product_total++;
  if(!$product_url)$product_url=get_permalink($product->get_id());
 }
}
if($product_total<1)throw new RuntimeException('No available chocolate product found for cluster');
$sample_product_topics=[];
foreach([1,5,6,10,14,20,21,25,29] as $n){
 $items=emdo_choco_related_products($n);
 $sample_product_topics[$n]=count($items);
 foreach($items as $p){
  if(!$p->is_visible()||!$p->is_in_stock()||$p->get_stock_status()!=='instock')throw new RuntimeException('Unavailable product leaked '.$p->get_id());
 }
}
if(!has_action('woocommerce_after_single_product_summary'))throw new RuntimeException('Product page hook missing');
echo 'EMDO_CHOCOLATE_AUDIT '.wp_json_encode([
 'verified'=>true,'article_pairs'=>30,'common_image_id'=>$image,'blog_category_id'=>(int)$cat->term_id,
 'product_categories'=>$term_slugs,'live_product_sample'=>$product_url,'available_product_sample_count'=>$product_total,
 'related_product_counts'=>$sample_product_topics,'posts'=>array_values($pages)
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
