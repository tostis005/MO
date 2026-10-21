<?php
/**
 * Read-only, bounded first batch of published, purchasable Tropitop items
 * missing all native EN metadata. Never touches other vendors or source rows.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||
 !class_exists('WooCommerce')||!class_exists('MDO_Database'))throw new RuntimeException('Woo CLI required');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))throw new RuntimeException('Wrong host');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$s)throw new RuntimeException('Tropitop supplier missing');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_url FROM {$sp} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",(int)$s['id']),ARRAY_A);
$cap=18;$seen=[];$products=[];
$plain=static fn($text)=>trim((string)preg_replace('/\\s+/u',' ',wp_strip_all_tags((string)$text)));
foreach($rows as $r){
 if(count($products)>=$cap)break;
 $id=(int)$r['wc_product_id'];if(isset($seen[$id]))continue;$seen[$id]=1;
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock()||
  (int)get_post_field('post_author',$id)!==(int)$s['vendor_user_id'])continue;
 $url=(string)$r['source_url'];
 if(!in_array(strtolower((string)wp_parse_url($url,PHP_URL_HOST)),['tropitop.com','www.tropitop.com'],true))continue;
 $en=[
   'published'=>(string)get_post_meta($id,'_en_US_published',true),
   'title'=>(string)get_post_meta($id,'_en_US_post_title',true),
   'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
   'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),
   'content'=>(string)get_post_meta($id,'_en_US_post_content',true)
 ];
 $missing=$en['published']!=='1'||!$plain($en['title'])||!$en['slug']||
  ($plain($p->get_description())&&!$plain($en['content']))||
  ($plain($p->get_short_description())&&!$plain($en['excerpt']));
 if(!$missing)continue;
 // Do not overwrite existing hand-written drafts or partial translations.
 if($plain($en['title'])||$en['slug']||$plain($en['excerpt'])||$plain($en['content']))continue;
 $post=get_post($id);
 if(!$post||!$plain($post->post_content)||!$plain($post->post_title))continue;
 $products[]=[
   'id'=>$id,'source_id'=>(int)$r['id'],'supplier_code'=>'tropitop','author_id'=>(int)$post->post_author,
   'status'=>$post->post_status,'title'=>$post->post_title,
   'excerpt'=>$post->post_excerpt,'content'=>$post->post_content,'existing_english'=>$en,
 ];
 echo 'EMDO_TROPITOP_EN_CANDIDATE='.wp_json_encode([
   'id'=>$id,'source_id'=>(int)$r['id'],'title'=>$p->get_name(),
   'description_chars'=>mb_strlen($post->post_content),
   'source_url'=>$url
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
echo 'EMDO_TROPITOP_EN_EXPORT_SUMMARY='.wp_json_encode(['batch_limit'=>$cap,'exported'=>count($products),'supplier'=>'tropitop']).PHP_EOL;
echo 'EMDO_TROPITOP_EN_EXPORT='.wp_json_encode(['site'=>get_option('siteurl'),'count'=>count($products),'products'=>$products],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
