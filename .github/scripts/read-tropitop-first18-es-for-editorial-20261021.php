<?php
/** Read-only Spanish editorial source for the first 18 purchasable Tropitop products. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WooCommerce')||!class_exists('MDO_Database'))throw new RuntimeException('WP CLI required');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))throw new RuntimeException('Wrong host');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$s)throw new RuntimeException('Producer missing');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_url FROM {$sp} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",(int)$s['id']),ARRAY_A);
$seen=[];$count=0;
foreach($rows as $r) {
 if($count>=18)break;
 $id=(int)$r['wc_product_id'];if(isset($seen[$id]))continue;$seen[$id]=1;
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock()||
  (int)get_post_field('post_author',$id)!==(int)$s['vendor_user_id'])continue;
 $url=(string)$r['source_url'];
 if(!in_array(strtolower((string)wp_parse_url($url,PHP_URL_HOST)),['tropitop.com','www.tropitop.com'],true))continue;
 $short=(string)$p->get_short_description();$full=(string)$p->get_description();
 $plain=static fn($v)=>trim((string)preg_replace('/\\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$v),ENT_QUOTES|ENT_HTML5,'UTF-8')));
 if(!$plain($full))continue;
 $count++;
 echo 'EMDO_FIRST18_SPANISH='.wp_json_encode([
    'source_id'=>(int)$r['id'],'id'=>$id,'title'=>$p->get_name(),
    'excerpt'=>$plain($short),'content'=>$plain($full),
    'slug'=>get_post_field('post_name',$id),'product_type'=>$p->get_type(),
    'variants'=>$p->is_type('variable')?count($p->get_children()):0
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
}
echo 'EMDO_FIRST18_SUMMARY='.wp_json_encode(['reviewed'=>$count,'supplier'=>'tropitop']).PHP_EOL;
