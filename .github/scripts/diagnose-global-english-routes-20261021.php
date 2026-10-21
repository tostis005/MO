<?php
/** Read-only native route diagnostic for 404 URLs in global SEO registry. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) throw new RuntimeException('WP-CLI only');
global $wpdb;
$slugs=array('buy-6-pay-for-5-100-g-packs-of-50-iberian-acorn-fed-ham','acorn-fed-shoulder-ham-pack','premium-ham-shavings');
$out=array('home'=>get_option('home'),'products'=>array(),'categories'=>array());
foreach($slugs as $slug) {
 $rows=$wpdb->get_results($wpdb->prepare(
   "SELECT p.ID,p.post_name,p.post_status,p.post_type,m.meta_value as en_slug FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID=m.post_id AND m.meta_key='_en_US_post_name' WHERE m.meta_value=%s LIMIT 8",
   $slug),ARRAY_A);
 foreach($rows as $row) {
   $id=(int)$row['ID'];$p=wc_get_product($id);
   $out['products'][]=array('english_slug'=>$slug,'id'=>$id,'native_slug'=>$row['post_name'],
    'post_status'=>$row['post_status'],'post_type'=>$row['post_type'],
    'translation_published'=>(string)get_post_meta($id,'_en_US_published',true),
    'es_permalink'=>(string)get_permalink($id),
    'en_permalink'=>function_exists('mdoer_en_url')?mdoer_en_url(get_post($id)):'',
    'wc_visible'=>$p?$p->is_visible():false,
    'wc_purchasable'=>$p?$p->is_purchasable():false,
    'wc_stock'=>$p?$p->get_stock_status():'missing',
    'wc_catalog_visibility'=>$p?$p->get_catalog_visibility():'missing');
 }
}
foreach(array('hams-and-shoulders','oranges','cheeses','meat','preserves') as $slug) {
 foreach(array('category','product_cat') as $tax) {
   $rows=$wpdb->get_results($wpdb->prepare(
     "SELECT t.term_id,t.slug,tm.meta_value AS en_slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id AND tt.taxonomy=%s JOIN {$wpdb->termmeta} tm ON t.term_id=tm.term_id AND tm.meta_key='_en_US_slug' WHERE tm.meta_value=%s LIMIT 8",
     $tax,$slug),ARRAY_A);
   foreach($rows as $row) {
     $term=get_term((int)$row['term_id'],$tax);
     $out['categories'][]=array('english_slug'=>$slug,'taxonomy'=>$tax,'native_slug'=>$term->slug,
     'term_id'=>$term->term_id,'term_count'=>$term->count,'spanish_url'=>get_term_link($term),
     'translation_published'=>get_term_meta($term->term_id,'_en_US_published',true),
     'english_url'=>function_exists('mdoer_term_en_url')?mdoer_term_en_url($term):'');
   }
 }
}
echo 'EMDO_GLOBAL_ROUTE_DIAG '.wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
