<?php
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) throw new RuntimeException('Need production Woo');
foreach(['home','siteurl'] as $opt) {
 $host=strtolower((string)wp_parse_url((string)get_option($opt),PHP_URL_HOST));
 if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Wrong site');
}
global $wpdb;
$t=MDO_Database::table('suppliers');$st=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Unexpected vendor');
$vid=(int)$s['vendor_user_id'];$vendor=get_user_by('id',$vid);
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,title,source_url,status,source_payload,last_error FROM {$st} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
$c=['source_total'=>count($rows),'draft'=>0,'publish'=>0,'other'=>0,'included'=>0,'excluded'=>0,'missing_cover'=>0,'missing_cover_file'=>0,'missing_short'=>0,'missing_long'=>0,'missing_price'=>0,'variable'=>0,'variable_with_no_children'=>0,'wrong_author'=>0,'bad_categories'=>0,'bad_origin'=>0,'excluded_conflict'=>0,'outofstock'=>0,'stock_unknown'=>0,'pending'=>0,'not_saleable'=>0];
$issues=[];$cats=[];$states=[];$excluded=[];
foreach($rows as $row){
 $states[$row['status']]=($states[$row['status']]??0)+1;
 $url=(string)$row['source_url'];$title=(string)$row['title'];
 if(!in_array(strtolower((string)wp_parse_url($url,PHP_URL_HOST)),['quesospajarete.com','www.quesospajarete.com'],true)){$c['bad_origin']++;}
 $is_excluded=class_exists('MDO_Connector_Quesos_Pajarete')&&MDO_Connector_Quesos_Pajarete::excluded($url,$title);
 if($row['status']==='excluded'||$is_excluded){
  $c['excluded']++;$excluded[]=['source'=>$row['id'],'title'=>$title,'wc'=>(int)$row['wc_product_id'],'status'=>$row['status'],'match'=>$is_excluded];
  if($row['status']!=='excluded'&&$is_excluded){$c['excluded_conflict']++;}
 }
 if(!$row['wc_product_id']){$c['pending']++;continue;}
 $p=wc_get_product((int)$row['wc_product_id']);
 if(!$p){$issues[]=['id'=>$row['wc_product_id'],'reason'=>'wc_missing'];continue;}
 $c['included']++;
 $c[$p->get_status()==='draft'?'draft':($p->get_status()==='publish'?'publish':'other')]++;
 if((int)get_post_field('post_author',$p->get_id())!==$vid){$c['wrong_author']++;$issues[]=['id'=>$p->get_id(),'reason'=>'wrong_vendor'];}
 $cover=$p->get_image_id();if(!$cover){$c['missing_cover']++;$issues[]=['id'=>$p->get_id(),'reason'=>'no_cover'];}
 if($cover&&(!get_attached_file($cover)||!is_readable(get_attached_file($cover))))$c['missing_cover_file']++;
 if(!trim(wp_strip_all_tags($p->get_short_description())))$c['missing_short']++;
 if(!trim(wp_strip_all_tags($p->get_description())))$c['missing_long']++;
 if($p->is_type('variable')){$c['variable']++;if(!count($p->get_children())){$c['variable_with_no_children']++;}}
 else if(!is_numeric($p->get_price())||(float)$p->get_price()<=0){$c['missing_price']++;$issues[]=['id'=>$p->get_id(),'reason'=>'no_price'];}
 if($p->get_stock_status()==='outofstock')$c['outofstock']++;
 if(!in_array($p->get_stock_status(),['instock','outofstock'],true))$c['stock_unknown']++;
 if(!$p->is_purchasable())$c['not_saleable']++;
 $terms=wp_get_post_terms($p->get_id(),'product_cat',['fields'=>'names']);
 if(is_wp_error($terms)||in_array('Sin categorizar',(array)$terms,true)||!$terms){
  $c['bad_categories']++;
  if(count($issues)<60)$issues[]=['id'=>$p->get_id(),'title'=>$p->get_name(),'reason'=>'category','categories'=>$terms];
 }
 foreach((array)$terms as $term){$cats[$term]=($cats[$term]??0)+1;}
 if($is_excluded){$c['excluded_conflict']++;$issues[]=['id'=>$p->get_id(),'reason'=>'excluded_exists'];}
}
$profile=get_user_meta($vid,'wcfmmp_profile_settings',true);
$profile=is_array($profile)?$profile:[];
$ship=$profile['shipping']??null;
$out=['vendor_id'=>$vid,'vendor_roles'=>$vendor?$vendor->roles:[],'disable_vendor'=>get_user_meta($vid,'_disable_vendor',true),
 'offline'=>get_user_meta($vid,'_wcfm_store_offline',true),'supplier_active'=>$s['active'],
 'supplier_notes'=>$s['notes']??'','supplier_commercial_rule'=>$s['commercial_rule']??'','supplier_shipping_fields'=>array_filter($s,static fn($k)=>stripos((string)$k,'ship')!==false,ARRAY_FILTER_USE_KEY),
 'profile_shop_description_chars'=>mb_strlen(wp_strip_all_tags((string)($profile['shop_description']??''))),'profile_banner'=>$profile['banner']??null,'profile_mobile_banner'=>$profile['mobile_banner']??null,'profile_list_banner'=>$profile['list_banner']??null,'profile_shipping'=>$ship,
 'profile_shipping_fields'=>array_filter($profile,static fn($k)=>stripos((string)$k,'shipping')!==false,ARRAY_FILTER_USE_KEY),
 'counts'=>$c,'states'=>$states,'issues'=>$issues,'excluded'=>$excluded,'categories'=>$cats,
 'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($vid):''];
echo 'PAJARETE_PUBLICATION_PREFLIGHT='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
