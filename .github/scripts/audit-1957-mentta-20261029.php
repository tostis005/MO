<?php
/**
 * Read-only Mentta category audit for 1957 campaign 26/27 (3 parent oils).
 * No customer data, API keys or mutations.
 */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) {fwrite(STDERR,"AUDIT_ABORT_WOO_NOT_LOADED\n");exit(2);}
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)){fwrite(STDERR,"AUDIT_ABORT_NOT_PRODUCTION\n");exit(3);}
$vendor=get_user_by('login','1957');if(!$vendor)$vendor=get_user_by('slug','1957');
if(!$vendor){fwrite(STDERR,"AUDIT_ABORT_VENDOR_NOT_FOUND\n");exit(4);}
$root=get_term_by('slug','mentta','product_cat');
if(!$root){fwrite(STDERR,"AUDIT_ABORT_MENTTA_NOT_FOUND\n");exit(5);}
$children=get_terms(['taxonomy'=>'product_cat','parent'=>(int)$root->term_id,'hide_empty'=>false]);
if(is_wp_error($children)){fwrite(STDERR,"AUDIT_ABORT_TERMS\n");exit(6);}
echo 'MENTTA_ROOT='.wp_json_encode(['id'=>(int)$root->term_id,'name'=>$root->name,'slug'=>$root->slug],JSON_UNESCAPED_UNICODE)."\n";
foreach($children as $child) echo 'MENTTA_CHILD='.wp_json_encode(['id'=>(int)$child->term_id,'name'=>$child->name,'slug'=>$child->slug,'parent'=>(int)$child->parent],JSON_UNESCAPED_UNICODE)."\n";
$all=get_posts(['post_type'=>'product','post_status'=>['publish','draft','private','pending','archived'],'author'=>(int)$vendor->ID,'posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true]);
$count=0;
foreach($all as $id){
 $p=wc_get_product($id);
 if(!$p)continue;
 $name=$p->get_name();
 if(!preg_match('/COSECHA\\s*26\\/27|NUEVA CAMPAÑA\\s*26\\/27/i',$name))continue;
 $cats=wp_get_object_terms($id,'product_cat');
 $safe=[];foreach($cats as $c)if($c instanceof WP_Term)$safe[]=['id'=>(int)$c->term_id,'name'=>$c->name,'slug'=>$c->slug,'parent'=>(int)$c->parent];
 echo 'NEW_1957_OIL='.wp_json_encode(['id'=>$id,'name'=>$name,'status'=>$p->get_status(),'type'=>$p->get_type(),'visibility'=>$p->get_catalog_visibility(),'price'=>$p->get_price(),'categories'=>$safe,'variations'=>$p->is_type('variable')?count($p->get_children()):0],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 $count++;
}
echo 'NEW_1957_COUNT='.$count."\n";
foreach($all as $id){
 $p=wc_get_product($id); if(!$p)continue;
 if(preg_match('/25\\/26/i',$p->get_name())){
 $cats=wp_get_object_terms($id,'product_cat',['fields'=>'slugs']);
 echo 'OLD_1957_OIL='.wp_json_encode(['id'=>$id,'name'=>$p->get_name(),'categories'=>$cats],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
}
echo 'AUDIT_1957_MENTTA_CATEGORIES_COMPLETE'."\n";
