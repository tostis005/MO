<?php
if(!defined('ABSPATH')||!class_exists('MDO_Database')){throw new RuntimeException('WP required');}
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)){throw new RuntimeException('Wrong target');}
global $wpdb;
$s= MDO_Database::table('suppliers');
$t= MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$s} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$supplier){throw new RuntimeException('Supplier missing');}
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_url,source_payload FROM {$t} WHERE supplier_id=%d",$supplier['id']),ARRAY_A);
$records=array();
for($page=1;$page<=5;$page++){
 $url='https://quesospajarete.com/wp-json/wc/store/v1/products?per_page=100&page='.$page;
 $res=wp_remote_get($url,array('timeout'=>45,'redirection'=>2,'user-agent'=>'Mozilla/5.0 (compatible; EMDO-Catalog-Audit/1.0)'));
 if(is_wp_error($res)){echo 'PAJARETE_API_ERROR='.wp_json_encode(array('page'=>$page,'error'=>$res->get_error_message())).PHP_EOL;break;}
 $status=(int)wp_remote_retrieve_response_code($res);
 if($status!==200){echo 'PAJARETE_API_ERROR='.wp_json_encode(array('page'=>$page,'status'=>$status)).PHP_EOL;break;}
 $data=json_decode((string)wp_remote_retrieve_body($res),true);
 if(!is_array($data)){throw new RuntimeException('API invalid JSON');}
 echo 'PAJARETE_API_PAGE='.wp_json_encode(array('page'=>$page,'count'=>count($data),'wp_total'=>wp_remote_retrieve_header($res,'x-wp-total'),'wp_pages'=>wp_remote_retrieve_header($res,'x-wp-totalpages'))).PHP_EOL;
 foreach($data as $item){
   if(!is_array($item)||empty($item['permalink']))continue;
   $key=trim((string)wp_parse_url((string)$item['permalink'],PHP_URL_PATH),'/');
   $records[$key]=$item;
 }
 if(count($data)<100)break;
}
$count=array('woo'=>0,'matched'=>0,'images_api'=>0,'short_api'=>0,'long_api'=>0,'images_missing_local'=>0,'short_missing_local'=>0,'long_missing_local'=>0,'matched_category_api'=>0,'skipped'=>0,'no_api_match'=>0,'variable_api'=>0,'no_price_api'=>0);
$unmatched=[];$examples=[];$categories=[];
foreach($rows as $r){
 $p=$r['wc_product_id']?wc_get_product((int)$r['wc_product_id']):null;
 if(!$p){$count['skipped']++;continue;}
 $count['woo']++;
 $key=trim((string)wp_parse_url((string)$r['source_url'],PHP_URL_PATH),'/');
 $item=$records[$key]??null;
 if(!$item){$count['no_api_match']++;$unmatched[]=array('id'=>$p->get_id(),'title'=>$p->get_name(),'key'=>$key);continue;}
 $count['matched']++;
 $img=array_values(array_filter((array)($item['images']??[]),static fn($a)=>is_array($a)&&!empty($a['src'])));
 $short=trim(wp_strip_all_tags((string)($item['short_description']??'')));
 $long=trim(wp_strip_all_tags((string)($item['description']??'')));
 if($img){$count['images_api']++;if(!$p->get_image_id())$count['images_missing_local']++;}
 if($short){$count['short_api']++;if(!trim(wp_strip_all_tags($p->get_short_description())))$count['short_missing_local']++;}
 if($long){$count['long_api']++;if(!trim(wp_strip_all_tags($p->get_description())))$count['long_missing_local']++;}
 if(!empty($item['categories'])){$count['matched_category_api']++;}
 if(($item['type']??'')==='variable'){$count['variable_api']++;}
 if(empty($item['prices']['price'])){$count['no_price_api']++;}
 foreach((array)($item['categories']??[]) as $cat){$n=(string)($cat['name']??'');if($n)$categories[$n]=($categories[$n]??0)+1;}
 if(count($examples)<8||(!$img&&count($examples)<15)){
   $examples[]=array('id'=>$p->get_id(),'title'=>$p->get_name(),'src_type'=>$item['type']??null,'api_images'=>count($img),'image_1'=>$img[0]['src']??null,'api_short_chars'=>mb_strlen($short),'api_long_chars'=>mb_strlen($long),'short_preview'=>mb_substr($short,0,150),'long_preview'=>mb_substr($long,0,170),'api_cats'=>array_map(static fn($a)=>$a['name']??'',(array)($item['categories']??[])));
 }
}
echo 'PAJARETE_API_AUDIT='.wp_json_encode(array('count'=>$count,'total_source_api'=>count($records),'categories'=>$categories,'unmatched'=>array_slice($unmatched,0,25),'examples'=>$examples),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
