<?php
/**
 * Chocolates Isabel: safe, idempotent editorial and category reconciliation.
 * Only the seller's own imported products. No photos, price, shipping or stock edits.
 * Technical supplier descriptions always remain intact and refresh on future imports.
 */
if(!defined('ABSPATH')||!class_exists('WooCommerce')||!class_exists('MDO_Isabel_Quality'))throw new RuntimeException('WordPress + Isabel quality required');
$path=getenv('ISABEL_EDITORIAL_BRIEF');if(!$path||!is_readable($path))throw new RuntimeException('Missing editorial brief');
$brief=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
if(($brief['scope']??'')!=='chocolates-artesanos-isabel')throw new RuntimeException('Unexpected supplier');
$vendor=4964;
global $wpdb;
$t=MDO_Database::table('source_products');$s=MDO_Database::table('suppliers');
$rows=$wpdb->get_results($wpdb->prepare("SELECT sp.wc_product_id,sp.title,sp.source_payload,sp.source_url,sp.source_stock_status FROM {$t} sp INNER JOIN {$s} sup ON sup.id=sp.supplier_id WHERE sup.code=%s AND sp.wc_product_id > 0 ORDER BY sp.wc_product_id",'chocolates-artesanos-isabel'),ARRAY_A);
if(count($rows)!==103)throw new RuntimeException('Supplier source inventory changed: '.count($rows));
if(!method_exists('MDO_Isabel_Quality','apply'))throw new RuntimeException('Updated category authority missing');
$class_file=(new ReflectionClass('MDO_Isabel_Quality'))->getFileName();
$class_source=is_file($class_file)?file_get_contents($class_file):'';
if(!str_contains($class_source,'chocobone')||!str_contains($class_source,'preparar bebidas'))throw new RuntimeException('Latest importer category revision not deployed yet');
$summary=['batch'=>'20261017-isabel-editorial','source_count'=>count($rows),'reviewed'=>0,'edits'=>0,'category_corrected'=>[],'descriptions_updated'=>[],'personalized_excluded'=>[],'excluded_due_to_missing_source'=>[],'changes'=>[],'verified'=>false];
$snapshots=[];$known=array_fill_keys(array_keys($brief['products']),false);
$keys=['_emdo_isabel_editorial_approved','_emdo_isabel_editorial_intro','_emdo_isabel_editorial_short_description'];
try {
 foreach($rows as $r){
  $id=(int)$r['wc_product_id'];$p=wc_get_product($id);
  if(!$p||get_post_type($id)!=='product'||(int)get_post_field('post_author',$id)!==$vendor)throw new RuntimeException('Product outside Isabel or missing: '.$id);
  $payload=json_decode((string)$r['source_payload'],true);
  if(!is_array($payload)||empty($payload['title']))throw new RuntimeException('Unusable source payload: '.$id);
  $payload['source_url']=(string)$r['source_url'];
  $original=(string)($payload['description']??'');
  if(trim(wp_strip_all_tags($original))==='')throw new RuntimeException('Missing technical description: '.$id);
  $category_before=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
  if(!is_array($category_before))throw new RuntimeException('Category read failed: '.$id);
  $snapshots[$id]=[
   'category'=>$category_before,'long'=>$p->get_description(),'short'=>$p->get_short_description(),
   'weight'=>$p->get_weight(),'length'=>$p->get_length(),'width'=>$p->get_width(),'height'=>$p->get_height(),
   'meta'=>array_combine($keys,array_map(static fn($k)=>get_post_meta($id,$k,true),$keys))
  ];
  $old_price=$p->get_price('edit');$old_regular=$p->get_regular_price('edit');
  $old_stock=$p->get_stock_status('edit');$old_visibility=$p->get_catalog_visibility();
  MDO_Isabel_Quality::apply($id,$payload);
  $p=wc_get_product($id);
  if(!$p||$p->get_price('edit')!==$old_price||$p->get_regular_price('edit')!==$old_regular||$p->get_stock_status('edit')!==$old_stock||$p->get_catalog_visibility()!==$old_visibility)throw new RuntimeException('Commercial data changed: '.$id);
  $new_cats=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);sort($new_cats);$old_cats=$category_before;sort($old_cats);
  if($new_cats!==$old_cats)$summary['category_corrected'][]=['id'=>$id,'name'=>$p->get_name(),'before'=>$old_cats,'after'=>$new_cats];
  $curated=$brief['products'][(string)$id]??null;
  $protected=get_post_meta($id,'_emdo_isabel_customization_review_required',true)==='1';
  if($protected){
   $summary['personalized_excluded'][]=$id;
  }elseif(is_array($curated)){
   $short=trim((string)($curated['short']??''));$intro=trim((string)($curated['intro']??''));
   if(mb_strlen($short)<65||mb_strlen($intro)<85)throw new RuntimeException('Editorial brief too short '.$id);
   if(str_contains($short,'<')||str_contains($intro,'<'))throw new RuntimeException('Plain editorial copy expected');
   $short_html='<p>'.esc_html($short).'</p>';
   $intro_html='<p>'.esc_html($intro).'</p>';
   $full_html=$intro_html."\n".wp_kses_post($original);
   update_post_meta($id,'_emdo_isabel_editorial_intro',$intro_html);
   update_post_meta($id,'_emdo_isabel_editorial_short_description',$short_html);
   update_post_meta($id,'_emdo_isabel_editorial_approved','1');
   $p->set_short_description($short_html);$p->set_description($full_html);$p->save();
   if(!str_contains(wp_strip_all_tags($p->get_description()),$intro))throw new RuntimeException('Approved intro missing '.$id);
   $known[(string)$id]=true;
   $summary['descriptions_updated'][]=$id;
  }
  $summary['reviewed']++;
 }
} catch(Throwable $e){
 foreach(array_reverse(array_keys($snapshots)) as $id){
  $snap=$snapshots[$id];$product=wc_get_product($id);
  if($product){
   $product->set_description($snap['long']);$product->set_short_description($snap['short']);
   $product->set_weight($snap['weight']);$product->set_length($snap['length']);$product->set_width($snap['width']);$product->set_height($snap['height']);$product->save();
  }
  wp_set_object_terms($id,$snap['category'],'product_cat',false);
  foreach($snap['meta'] as $key=>$val){if($val==='')delete_post_meta($id,$key);else update_post_meta($id,$key,$val);}
 }
 wp_cache_flush();throw $e;
}
$summary['edits']=count($summary['descriptions_updated']);
$summary['unused_briefs']=array_keys(array_filter($known,static fn($v)=>!$v));
$summary['verified']=$summary['reviewed']===103 && count($summary['unused_briefs'])<=8;
if(!$summary['verified'])throw new RuntimeException('Audit not complete or excessive skipped briefs');
wp_cache_flush();if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'ISABEL_EDITORIAL_FIX='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
