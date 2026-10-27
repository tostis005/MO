<?php
/**
 * Production read-only product compatibility matrix:
 * six newly created Mentta packs vs jamones already imported (Monjam and Hidalgo).
 * No user data/credentials/URLs or full metadata values emitted.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')||!class_exists('WC_Mentta_Plugin'))exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
global $wpdb;
$root=get_term_by('slug','mentta','product_cat');
if(!$root)exit(4);
$child=get_term_children((int)$root->term_id,'product_cat');
$child=is_wp_error($child)?[]:array_map('intval',$child);
$packs=[21814,21818,21820,21904,21906,21908];
// Approved producer labels are used to find existing benchmark jamones. Don't assume old IDs are black-label products.
$rows=$wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND (post_title LIKE %s OR post_title LIKE %s) ORDER BY ID DESC LIMIT 200",$wpdb->esc_like('brida negra')? '%'.$wpdb->esc_like('brida negra').'%':'%brida negra%','%jam%n%'),ARRAY_A);
$candidates=[];
foreach((array)$rows as $r){
 $id=(int)$r['ID'];$p=wc_get_product($id);
 if(!$p||!in_array((int)$root->term_id,array_map('intval',$p->get_category_ids()),true))continue;
 $name=remove_accents(mb_strtolower($p->get_name()));
 $producer=(string)get_post_field('post_author',$id);
 if(!preg_match('/jamon|paleta|brida|bellota/iu',$name))continue;
 $score=(preg_match('/brida negra|etiqueta negra/iu',$name)?100:0)+
   (preg_match('/monjam|montjam|hidalgo|jara/iu',$name)?30:0);
 $candidates[]=['id'=>$id,'name'=>$p->get_name(),'author'=>$producer,'score'=>$score];
}
usort($candidates,static fn($a,$b)=>$b['score']<=>$a['score'] ?: $b['id']<=>$a['id']);
$names=[];
foreach($candidates as $c){$id=$c['id'];$names[]=$id;if(count($names)>=10)break;}
$comparison=array_values(array_unique(array_merge($packs,array_slice($names,0,8),[14630,1350])));
if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products';
function pack_cmp_entry($id,$root,$child){
 $p=wc_get_product($id);
 if(!$p)return ['id'=>$id,'missing'=>true];
 $post=get_post($id);
 $taxonomy=wp_get_object_terms($id,'product_visibility',['fields'=>'slugs']);
 $cats=array_map('intval',$p->get_category_ids());
 $pa=wp_get_object_terms($id,'pa_productor',['fields'=>'names']);
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$id);
 $prior=$_SERVER['REQUEST_URI']??'';
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
 try {$res=WC_Mentta_Plugin::get_products($req);$body=$res instanceof WP_REST_Response?$res->get_data():null;}
 catch(Throwable $e){$body=null;}
 $_SERVER['REQUEST_URI']=$prior;
 $items=is_object($body)?($body->data??[]):(is_array($body)?($body['data']??[]):[]);
 $entry=is_array($items)?($items[0]??[]):[];
 $safe=[];
 foreach(['Id','Type','SKU','IsVisible','IsPurchasable','IsInStock','ManagingStock','ManageStock','StockQuantity','StockStatus','Price','RegularPrice','PriceIncludingTax','PriceExcludingTax','PriceDisplay','Weight','HasOptions','SoldIndividually','Image','Permalink','Link','DateModified','Published','Status'] as $field)if(array_key_exists($field,$entry)){
  $v=$entry[$field];
  if(in_array($field,['Image','Permalink','Link'],true))$safe[$field.'_present']=(bool)$v;
  elseif(in_array($field,['Price','RegularPrice','PriceIncludingTax','PriceExcludingTax','PriceDisplay'],true))$safe[$field]=is_numeric($v)?round((float)$v,3):$v;
  else $safe[$field]=is_scalar($v)?$v:gettype($v);
 }
 $safe['Categories']=$entry['Categories']??[];
 $safe['variations_count']=is_array($entry['Variations']??null)?count($entry['Variations']):null;
 $safe['attributes']=array_map(static fn($x)=>['name'=>$x['Name']??'','count'=>count($x['Options']??[]),'variation'=>$x['Variation']??null],(array)($entry['Attributes']??[]));
 $safe['meta_keys_sample']=array_slice(array_map(static function($x){if(is_array($x))return $x['key']??$x['Key']??'?';if(is_object($x))return $x->key??$x->Key??'?';return '?';},(array)($entry['MetaData']??[])),0,30);
 $safe['image_count']=count((array)($entry['Images']??$entry['Gallery']??[]));
 $safe['primary_image_attached']=((int)$p->get_image_id()>0);
 $safe['variation_ids_sample']=array_slice(array_map(static fn($x)=>(int)($x['Id']??0), (array)($entry['Variations']??[])),0,3);
 $safe['source_keys']=array_keys($entry);
 $exclude=function_exists('emdo_mentta_exclusive_ids')&&in_array($id,emdo_mentta_exclusive_ids(),true);
 return [
 'id'=>$id,'name'=>$p->get_name(),'author'=>(int)$post->post_author,'producer_tax_names'=>is_wp_error($pa)?[]:$pa,
 'status'=>$p->get_status(),'type'=>$p->get_type(),'sku'=>$p->get_sku(),
 'visibility'=>$p->get_catalog_visibility(),'taxonomy_visibility'=>is_wp_error($taxonomy)?[]:$taxonomy,
 'price_inc_tax'=>round((float)wc_get_price_including_tax($p),2),
 'stock_status'=>$p->get_stock_status(),'in_stock'=>$p->is_in_stock(),'purchasable'=>$p->is_purchasable(),
 'manage_stock'=>$p->get_manage_stock(),'stock_quantity'=>$p->get_stock_quantity(),
 'categories'=>$cats,'root_explicit'=>in_array((int)$root->term_id,$cats,true),'child'=>array_values(array_intersect($cats,$child)),
 'product_permalink_public_guarded'=>$exclude,
 'meta_flags'=>['_emdo_mentta_only'=>get_post_meta($id,'_emdo_mentta_only',true),'_emdo_mentta_pack_key_present'=>(bool)get_post_meta($id,'_emdo_mentta_pack_key',true)],
 'feed'=>$safe
 ];
}
$report=[];
foreach($comparison as $id)$report[]=pack_cmp_entry($id,$root,$child);
echo 'BENCHMARK_CANDIDATE_JAMONES '.wp_json_encode(array_slice($candidates,0,18),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
foreach($report as $r)echo 'MENTTA_PRODUCT_COMPARISON '.wp_json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "MENTTA_COMPARISON_DONE\n";
