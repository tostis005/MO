<?php
if(!defined('ABSPATH')||!class_exists('WooCommerce')||!class_exists('MDO_Database')) throw new RuntimeException('WP needed');
foreach(['siteurl','home'] as $k)if(!in_array(strtolower((string)wp_parse_url(get_option($k),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
global $wpdb;
$t=MDO_Database::table('suppliers');$st=MDO_Database::table('source_products');
$sr=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code=%s",'entresetas'),ARRAY_A);
if(!$sr||(int)$sr['vendor_user_id']!==4982)throw new RuntimeException('Wrong vendor');
$ids=[4982,4974,4964,4976,4979];
foreach($ids as $id) {
 $u=get_user_by('id',$id); if(!$u)continue;
 $p=get_user_meta($id,'wcfmmp_profile_settings',true);
 $keys=['store_name','store_slug','gravatar','store_logo','logo','banner','banner_type','mobile_banner','list_banner','list_banner_type','store_hide_description','store_hide_products','store_hide_map','store_hide_about','shop_description'];
 $safe=[];foreach($keys as $key){if(isset($p[$key])){$value=$p[$key];if($key==='shop_description')$value=mb_substr(wp_strip_all_tags((string)$value),0,200);$safe[$key]=$value;}}
 echo 'VENDOR_LOGO_AUDIT='.wp_json_encode(['id'=>$id,'login'=>$u->user_login,'disabled'=>(bool)get_user_meta($id,'_disable_vendor',true),'settings'=>$safe,'profile_keys'=>is_array($p)?array_keys($p):[], 'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($id):''],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_payload,status FROM {$st} WHERE supplier_id=%d ORDER BY id",(int)$sr['id']),ARRAY_A);
$good=[];$bad=[];$total=['exists'=>0,'public'=>0,'draft'=>0,'image'=>0,'stock_in'=>0];
foreach($rows as $r){
 $p=wc_get_product((int)$r['wc_product_id']);$payload=json_decode((string)$r['source_payload'],true);
 if(!$p){$bad[]=['id'=>(int)$r['wc_product_id'],'why'=>'missing'];continue;}
 $total['exists']++;$total['public']+=(int)($p->get_status()==='publish'&&$p->get_catalog_visibility()==='visible');
 $total['draft']+=(int)($p->get_status()==='draft');$total['image']+=(int)((int)$p->get_image_id()>0);
 $total['stock_in']+=(int)($p->is_in_stock());
 if((float)$p->get_price()<=0||!$p->get_image_id()||!$p->get_short_description()||!$p->get_description()||(int)get_post_field('post_author',$p->get_id())!==4982)$bad[]=['id'=>$p->get_id(),'title'=>$p->get_name(),'price'=>$p->get_price(),'cover'=>$p->get_image_id(),'description'=>strlen($p->get_description()),'short'=>strlen($p->get_short_description())];
}
echo 'ENTRESETAS_PUBLICATION_AUDIT='.wp_json_encode(['source_count'=>count($rows),'totals'=>$total,'bad'=>$bad,'flags'=>['media_authorized'=>(bool)get_option('mdo_entresetas_media_authorized'),'commercial_ready'=>(bool)get_option('mdo_entresetas_commercial_ready'),'storefront_enabled'=>(bool)get_option('mdo_entresetas_storefront_enabled')],'url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url(4982):''],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
