<?php
if (!defined('ABSPATH')) exit(1);
if (!taxonomy_exists('product_cat') || !class_exists('MDO_Database')) throw new Exception('Required components unavailable.');
global $wpdb;

$supplier_id=5;
$table=MDO_Database::table('source_products');
$rows=$wpdb->get_results($wpdb->prepare(
 "SELECT id,wc_product_id,title,status,source_url,source_stock_status FROM {$table} WHERE supplier_id=%d ORDER BY id ASC",
 $supplier_id
),ARRAY_A);
if(count($rows)!==198) throw new Exception('Unexpected Selectos source row count: '.count($rows));

$normalize=static function(string $s): string{
 $s=html_entity_decode($s,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $s=remove_accents(mb_strtolower($s,'UTF-8'));
 return trim(preg_replace('/\s+/',' ',(string)$s));
};

$get_term=static function(string $slug): WP_Term{
 $t=get_term_by('slug',$slug,'product_cat');
 if(!$t instanceof WP_Term) throw new Exception('Missing category '.$slug);
 return $t;
};

$required=array(
 'carnes','conservas','embutidos-y-curados','packs-y-lotes','pato','confit-de-pato',
 'foie-gras-de-pato','jamon-de-pato','magret-de-pato','pate-mousse-rillettes-de-pato',
 'pato-fresco','pescados-mariscos','foie-pates-untables'
);
$terms=array();
foreach($required as $slug) $terms[$slug]=$get_term($slug);

$classify=static function(string $url,string $title): array{
 $url=mb_strtolower($url,'UTF-8');
 $t=html_entity_decode($title,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $t=remove_accents(mb_strtolower($t,'UTF-8'));
 $t=trim(preg_replace('/\s+/',' ',(string)$t));

 $is_pack = str_starts_with($t,'lote ') || str_starts_with($t,'cesta ')
   || str_starts_with($t,'estuche ') || str_contains($t,'pie de pates');

 if(str_contains($url,'/detalles-bodas-bautizos-y-comuniones/')
   || str_contains($url,'/estuches-y-regalos-de-empresa/')
   || $is_pack){
   return array('packs-y-lotes');
 }
 if(str_contains($url,'/jamon-de-pato/')){
   return array('embutidos-y-curados','pato','jamon-de-pato');
 }
 if(str_contains($url,'/trucha/')){
   return array('pescados-mariscos');
 }
 if(str_contains($url,'/pato-fresco/')){
   return array('pato','pato-fresco');
 }
 if(str_contains($url,'/magret/')){
   return array('pato','magret-de-pato');
 }
 if(str_contains($url,'/foie-gras/')){
   if(str_contains($t,'oca') && !str_contains($t,'pato')) return array('foie-pates-untables');
   $out=array('foie-pates-untables','pato');
   $out[]=str_contains($t,'mousse') ? 'pate-mousse-rillettes-de-pato' : 'foie-gras-de-pato';
   return $out;
 }
 if(str_contains($url,'/pates/')){
   if(str_contains($t,'trucha')) return array('pescados-mariscos','foie-pates-untables');
   $out=array('foie-pates-untables');
   $other_species = str_contains($t,'avestruz') || str_contains($t,'cochinillo') || str_contains($t,'oca')
      || (str_contains($t,'lechazo') && !str_contains($t,'pato'));
   if(!$other_species){
     $out[]='pato';
     $out[]=str_contains($t,'bloc de foie gras') ? 'foie-gras-de-pato' : 'pate-mousse-rillettes-de-pato';
   }
   return $out;
 }
 if(str_contains($url,'/confit/')){
   if(str_contains($t,'cochinillo') || str_contains($t,'codorniz')) return array('carnes');
   if(str_contains($t,'rillettes')){
     if(str_contains($t,'lechazo') && !str_contains($t,'pato')) return array('foie-pates-untables');
     return array('foie-pates-untables','pato','pate-mousse-rillettes-de-pato');
   }
   if(str_contains($t,'grasa refinada')) return array('pato');
   return array('pato','confit-de-pato');
 }
 if(str_contains($url,'/los-manjares-de-la-tierra/') && str_contains($t,'cochinillo')){
   return array('carnes');
 }
 if(str_contains($url,'/complementos/')){
   return array('conservas');
 }
 if(str_contains($url,'/productos/')){
   if(str_contains($t,'grasa refinada de pato')) return array('pato');
   if(str_contains($t,'manchon') && str_contains($t,'confit')) return array('pato','confit-de-pato');
   if(str_starts_with($t,'cesta ') || str_starts_with($t,'estuche ')) return array('packs-y-lotes');
 }
 return array();
};

$active=0;$excluded=0;$unmapped=array();$status_before=array();$stock_before=array();
foreach($rows as $r){
 $wc=(int)$r['wc_product_id'];
 if(!$wc || !wc_get_product($wc)) throw new Exception('Missing WC product for source row '.$r['id']);
 $status_before[$wc]=(string)get_post_status($wc);
 $p=wc_get_product($wc); $stock_before[$wc]=(string)$p->get_stock_status();

 if($r['status']==='excluded'){
   $excluded++;
   $res=wp_set_object_terms($wc,array(),'product_cat',false);
   if(is_wp_error($res)) throw new Exception('Failed clearing excluded product '.$wc.': '.$res->get_error_message());
   clean_post_cache($wc);
   continue;
 }
 if($r['status']!=='active') throw new Exception('Unexpected source status '.$r['status'].' for row '.$r['id']);
 $active++;
 $slugs=$classify((string)$r['source_url'],(string)$r['title']);
 if(!$slugs){
   $unmapped[]=array('source_id'=>(int)$r['id'],'wc_product_id'=>$wc,'title'=>$r['title'],'url'=>$r['source_url']);
   continue;
 }
 $ids=array();
 foreach(array_values(array_unique($slugs)) as $slug){
   if(!isset($terms[$slug])) $terms[$slug]=$get_term($slug);
   $ids[]=(int)$terms[$slug]->term_id;
 }
 $res=wp_set_object_terms($wc,$ids,'product_cat',false);
 if(is_wp_error($res)) throw new Exception('Failed categorizing active product '.$wc.': '.$res->get_error_message());
 update_post_meta($wc,'_emdo_supplier_id',$supplier_id);
 delete_post_meta($wc,'_emdo_auto_category_attempted');
 delete_post_meta($wc,'_emdo_auto_category_reason');
 delete_post_meta($wc,'_emdo_auto_category_ids');
 delete_post_meta($wc,'_emdo_auto_category_score');
 clean_post_cache($wc);
}
if($unmapped) throw new Exception('Unmapped active Selectos products: '.wp_json_encode($unmapped,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
if($active!==120 || $excluded!==78) throw new Exception("Source count mismatch active={$active} excluded={$excluded}");

$deleted_categories=array();
$retained_categories=array();
foreach(array('despensa-gourmet','vinos-licores') as $slug){
 $term=get_term_by('slug',$slug,'product_cat');
 if(!$term instanceof WP_Term) continue;
 $objects=get_objects_in_term((int)$term->term_id,'product_cat');
 if(is_wp_error($objects)) throw new Exception($objects->get_error_message());
 if(!$objects){
   $res=wp_delete_term((int)$term->term_id,'product_cat');
   if(is_wp_error($res)) throw new Exception('Failed deleting '.$slug.': '.$res->get_error_message());
   $deleted_categories[]=$slug;
 } else {
   $retained_categories[]=array('slug'=>$slug,'objects'=>array_values(array_map('intval',$objects)));
 }
}

$active_missing=0;$active_uncat=0;$active_mentta=0;$active_bad_extra=array();
$excluded_with_categories=0;$status_changes=array();$stock_changes=array();$category_counts=array();$examples=array();

foreach($rows as $r){
 $wc=(int)$r['wc_product_id'];
 $p=wc_get_product($wc);
 $after_status=(string)get_post_status($wc);
 $after_stock=$p?(string)$p->get_stock_status():'';
 if($after_status!==$status_before[$wc]) $status_changes[]=array('id'=>$wc,'before'=>$status_before[$wc],'after'=>$after_status);
 if($after_stock!==$stock_before[$wc]) $stock_changes[]=array('id'=>$wc,'before'=>$stock_before[$wc],'after'=>$after_stock);

 $assigned=wp_get_post_terms($wc,'product_cat');
 if(is_wp_error($assigned)) throw new Exception($assigned->get_error_message());
 $slugs=array_values(array_map(static fn(WP_Term $t): string=>$t->slug,$assigned));

 if($r['status']==='excluded'){
   if($slugs) $excluded_with_categories++;
   continue;
 }

 if(!$slugs) $active_missing++;
 foreach($slugs as $slug){
   $category_counts[$slug]=($category_counts[$slug]??0)+1;
   if(in_array($slug,array('sin-categorizar','uncategorized'),true)) $active_uncat++;
   if($slug==='mentta'||str_starts_with($slug,'mentta-')) $active_mentta++;
 }
 $expected=$classify((string)$r['source_url'],(string)$r['title']);
 $a=$slugs;$b=$expected;sort($a);sort($b);
 if($a!==$b) $active_bad_extra[]=array('id'=>$wc,'title'=>$r['title'],'expected'=>$b,'actual'=>$a);
 if(count($examples)<12) $examples[]=array('id'=>$wc,'title'=>$r['title'],'categories'=>$slugs,'stock'=>$r['source_stock_status']);
}
ksort($category_counts);

if($active_missing||$active_uncat||$active_mentta||$active_bad_extra||$excluded_with_categories||$status_changes||$stock_changes){
 throw new Exception('Final verification failed: '.wp_json_encode(compact(
   'active_missing','active_uncat','active_mentta','active_bad_extra','excluded_with_categories','status_changes','stock_changes'
 ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
if($retained_categories) throw new Exception('Obsolete categories still in use: '.wp_json_encode($retained_categories));

flush_rewrite_rules(false);
echo wp_json_encode(array(
 'batch'=>'20261001-selectos-final-active-taxonomy',
 'supplier_id'=>$supplier_id,
 'active_products'=>$active,
 'excluded_products'=>$excluded,
 'active_missing_after'=>$active_missing,
 'active_uncategorized_after'=>$active_uncat,
 'active_mentta_after'=>$active_mentta,
 'excluded_with_categories_after'=>$excluded_with_categories,
 'status_changes'=>$status_changes,
 'stock_changes'=>$stock_changes,
 'deleted_categories'=>$deleted_categories,
 'category_counts_active'=>$category_counts,
 'examples'=>$examples
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
