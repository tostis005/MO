<?php
/**
 * Read-only census of native WooCommerce product taxonomy.
 * Does not update posts, products or terms. Runs only for the EMDO production host.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
$hostname=strtolower((string)wp_parse_url(get_option('siteurl'), PHP_URL_HOST));
if(!in_array($hostname,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)){throw new RuntimeException('Wrong site');}
if(!class_exists('WooCommerce'))throw new RuntimeException('WooCommerce missing');
$terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
if(is_wp_error($terms))throw new RuntimeException('Failed categories');
$tax=[];$bycat=[];$byvendor=[];
foreach($terms as $t){$tax[(int)$t->term_id]=['id'=>(int)$t->term_id,'name'=>$t->name,'slug'=>$t->slug,'parent'=>(int)$t->parent,'count'=>(int)$t->count];}
foreach($tax as $t){echo 'CATALOG_CATEGORY='.wp_json_encode($t,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;}
global $wpdb;
$offset=0;$limit=350;$total=0;$statuses=[];
do {
 $posts=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status IN ('publish','draft','private','pending','future') ORDER BY ID ASC LIMIT %d OFFSET %d",'product',$limit,$offset));
 foreach($posts as $id){
  $product=wc_get_product($id);if(!$product)continue;
  $cids=array_map('intval',$product->get_category_ids());
  $cats=array_values(array_filter(array_map(static fn($id)=>$tax[$id]['slug']??null,$cids)));
  $src=(string)get_post_meta($id,'_emdo_source_url',true);
  $host=(string)wp_parse_url($src,PHP_URL_HOST);
  $author=(int)get_post_field('post_author',$id);
  $vendorKey=(string)$author;$byvendor[$vendorKey]=($byvendor[$vendorKey]??0)+1;
  foreach($cats as $cat){$bycat[$cat]=($bycat[$cat]??0)+1;}
  $status=$product->get_status();$statuses[$status]=($statuses[$status]??0)+1;
  $p=['id'=>(int)$id,'title'=>$product->get_name(),'sku'=>$product->get_sku(),'author'=>$author,'status'=>$status,'cats'=>$cats,'host'=>$host,'type'=>$product->get_type()];
  echo 'CATALOG_PRODUCT='.wp_json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
  ++$total;
 }
 $offset+=$limit;
}while(count($posts)===$limit && $offset<12000);
echo 'CATALOG_SUMMARY='.wp_json_encode(['total'=>$total,'status'=>$statuses,'vendors'=>$byvendor,'assigned_categories'=>$bycat,'terms'=>count($tax)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
