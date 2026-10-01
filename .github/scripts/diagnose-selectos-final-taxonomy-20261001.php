<?php
if(!defined('ABSPATH')) exit(1);
global $wpdb;
$table=MDO_Database::table('source_products');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,title,status,source_url,source_stock_status FROM {$table} WHERE supplier_id=%d ORDER BY id ASC",5),ARRAY_A);
$classify=static function(string $url,string $title): array{
 $url=mb_strtolower($url,'UTF-8');
 $t=html_entity_decode($title,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $t=remove_accents(mb_strtolower($t,'UTF-8'));
 $t=trim(preg_replace('/\s+/',' ',(string)$t));
 $is_pack=str_starts_with($t,'lote ')||str_starts_with($t,'cesta ')||str_starts_with($t,'estuche ')||str_contains($t,'pie de pates');
 if(str_contains($url,'/detalles-bodas-bautizos-y-comuniones/')||str_contains($url,'/estuches-y-regalos-de-empresa/')||$is_pack)return ['packs-y-lotes'];
 if(str_contains($url,'/jamon-de-pato/'))return ['embutidos-y-curados','pato','jamon-de-pato'];
 if(str_contains($url,'/trucha/'))return ['pescados-mariscos'];
 if(str_contains($url,'/pato-fresco/'))return ['pato','pato-fresco'];
 if(str_contains($url,'/magret/'))return ['pato','magret-de-pato'];
 if(str_contains($url,'/foie-gras/')){ $o=['foie-pates-untables','pato'];$o[]=str_contains($t,'mousse')?'pate-mousse-rillettes-de-pato':'foie-gras-de-pato';return $o;}
 if(str_contains($url,'/pates/')){
  if(str_contains($t,'trucha'))return ['pescados-mariscos','foie-pates-untables'];
  $o=['foie-pates-untables'];
  $other=str_contains($t,'avestruz')||str_contains($t,'cochinillo')||str_contains($t,'oca')||(str_contains($t,'lechazo')&&!str_contains($t,'pato'));
  if(!$other){$o[]='pato';$o[]=str_contains($t,'bloc de foie gras')?'foie-gras-de-pato':'pate-mousse-rillettes-de-pato';}
  return $o;
 }
 if(str_contains($url,'/confit/')){
  if(str_contains($t,'cochinillo')||str_contains($t,'codorniz'))return ['carnes'];
  if(str_contains($t,'rillettes')){
   if(str_contains($t,'lechazo')&&!str_contains($t,'pato'))return ['foie-pates-untables'];
   return ['foie-pates-untables','pato','pate-mousse-rillettes-de-pato'];
  }
  if(str_contains($t,'grasa refinada'))return ['pato'];
  return ['pato','confit-de-pato'];
 }
 if(str_contains($url,'/los-manjares-de-la-tierra/')&&str_contains($t,'cochinillo'))return ['carnes'];
 if(str_contains($url,'/complementos/'))return ['conservas'];
 if(str_contains($url,'/productos/')){
  if(str_contains($t,'grasa refinada de pato'))return ['pato'];
  if(str_contains($t,'manchon')&&str_contains($t,'confit'))return ['pato','confit-de-pato'];
  if(str_starts_with($t,'cesta ')||str_starts_with($t,'estuche '))return ['packs-y-lotes'];
 }
 return [];
};
$out=['batch'=>'20261001-selectos-final-diagnostic','rows'=>count($rows),'active'=>0,'excluded'=>0,'unmapped'=>[],'active_mismatches'=>[],'excluded_with_categories'=>[],'terms'=>[]];
foreach($rows as $r){
 $wc=(int)$r['wc_product_id'];$ts=$wc?wp_get_post_terms($wc,'product_cat'):[];
 $actual=[];if(!is_wp_error($ts))foreach($ts as $t)$actual[]=$t->slug;
 if($r['status']==='active'){
  $out['active']++;
  $exp=$classify((string)$r['source_url'],(string)$r['title']);
  if(!$exp)$out['unmapped'][]=['source_id'=>(int)$r['id'],'wc'=>$wc,'title'=>$r['title'],'url'=>$r['source_url']];
  $a=$actual;$b=$exp;sort($a);sort($b);
  if($a!==$b)$out['active_mismatches'][]=['source_id'=>(int)$r['id'],'wc'=>$wc,'title'=>$r['title'],'expected'=>$b,'actual'=>$a];
 }elseif($r['status']==='excluded'){
  $out['excluded']++;
  if($actual)$out['excluded_with_categories'][]=['source_id'=>(int)$r['id'],'wc'=>$wc,'title'=>$r['title'],'actual'=>$actual];
 }
}
foreach(['despensa-gourmet','vinos-licores','foie-pates-untables','pescados-mariscos'] as $slug){
 $t=get_term_by('slug',$slug,'product_cat');
 if(!$t instanceof WP_Term){$out['terms'][$slug]=null;continue;}
 $objs=get_objects_in_term((int)$t->term_id,'product_cat');
 $out['terms'][$slug]=['id'=>(int)$t->term_id,'count'=>is_wp_error($objs)?null:count($objs),'objects'=>is_wp_error($objs)?[]:array_values(array_map('intval',$objs))];
}
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
