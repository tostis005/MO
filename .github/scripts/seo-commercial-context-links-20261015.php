<?php
/**
 * Link reviewed evergreen buying guides to the new published pieces.
 * No duplicate promotional CTA headings; preserve ES/EN text, URLs and titles.
 */
if(!defined('ABSPATH'))throw new RuntimeException('WordPress unavailable');

$new=array(
 'producer'=>'comprar-directamente-al-productor-como-funciona',
 'vegetables'=>'comprar-verduras-hortalizas-online-elegir-frescas-temporada',
 'proximity'=>'productos-proximidad-que-son-como-reconocerlos-comprarlos',
);
$ids=array();
foreach($new as $key=>$slug){
 $p=get_page_by_path($slug,OBJECT,'post');
 if(!$p instanceof WP_Post||$p->post_status!=='publish'||get_post_meta($p->ID,'_en_US_published',true)!=='1')throw new RuntimeException('New bilingual guide not published: '.$slug);
 $ids[$key]=$p->ID;
}
$es=array();$en=array();
foreach($ids as $key=>$id){
 $es[$key]=esc_url(get_permalink($id));
 $en_slug=(string)get_post_meta($id,'_en_US_post_name',true);
 $en[$key]=esc_url(home_url('/en/'.$en_slug.'/'));
}
$targets=array(
 'comprar-carne-online-que-mirar-antes-elegir-corte'=>array(
  'es'=>'<p><!-- mdo-context-commerce-meat-20261015 -->Si además quieres entender qué significa comprar con una relación directa con quien produce, elabora o envía la carne, consulta nuestra guía sobre <a href="'.$es['producer'].'">comprar directamente al productor</a>. Conviene distinguir al ganadero del vendedor especializado antes de atribuir a uno las funciones del otro.</p>',
  'en'=>'<p><!-- mdo-context-commerce-meat-20261015 -->To better understand the difference between a grower, processor and specialist food seller, see our guide to <a href="'.$en['producer'].'">buying food directly from producers</a>. The commercial relationship should not be confused with the farm of origin.</p>',
 ),
 'como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio'=>array(
  'es'=>'<p><!-- mdo-context-commerce-gourmet-20261015 -->Si el argumento principal de un alimento es que es «local» o «de proximidad», merece la pena comprobar qué territorio toma como referencia. En nuestra guía de <a href="'.$es['proximity'].'">productos de proximidad</a> explicamos por qué origen, distancia, producción ecológica y compra directa no significan lo mismo.</p>',
  'en'=>'<p><!-- mdo-context-commerce-gourmet-20261015 -->When a food is described as local or nearby, check which place or distribution area that claim refers to. Our guide to <a href="'.$en['proximity'].'">local and proximity food</a> distinguishes origin, distance, organic standards and direct-from-producer buying.</p>',
 ),
 'verduras-temporada-espana-calendario-meses-que-comprar'=>array(
  'es'=>'<p><!-- mdo-context-commerce-season-20261015 -->El calendario responde a cuándo suele cultivarse cada hortaliza; para decidir un pedido online hacen falta otros criterios. Nuestra guía para <a href="'.$es['vegetables'].'">comprar verduras y hortalizas online</a> explica cómo evaluar variedad, frescura, embalaje y condiciones de entrega sin depender de una oferta concreta.</p>',
  'en'=>'<p><!-- mdo-context-commerce-season-20261015 -->A seasonal calendar explains typical harvest windows. For ordering produce, consult our guide to <a href="'.$en['vegetables'].'">buying fresh vegetables online</a>, which covers variety, freshness, packaging and delivery without relying on a specific offer.</p>',
 ),
 'que-significa-aceite-oliva-virgen-extra'=>array(
  'es'=>'<p><!-- mdo-context-commerce-aove-20261015 -->Una almazara identificada facilita conocer la procedencia y elaboración del aceite, pero no implica que el producto sea geográficamente cercano a cualquier comprador. Consulta también cómo <a href="'.$es['proximity'].'">comprobar qué significa un alimento de proximidad</a> antes de interpretar una referencia regional como una garantía de cercanía.</p>',
  'en'=>'<p><!-- mdo-context-commerce-aove-20261015 -->Knowing an olive mill can clarify where and how an oil is made, but it does not mean the mill is local to every customer. Read how to <a href="'.$en['proximity'].'">assess local food claims</a> rather than treating every regional reference as proof of geographical closeness.</p>',
 ),
);
if(getenv('MDO_CONTEXT_ROLLBACK')==='1'){
 foreach($targets as $slug=>$ed){
   $p=get_page_by_path($slug,OBJECT,'post'); if(!$p instanceof WP_Post)continue;
   foreach(array('es','en') as $lang){
     $key='_mdo_context_original_'.$lang.'_20261015';
     $old=get_post_meta($p->ID,$key,true);
     if(!is_string($old)||$old==='')continue;
     if($lang==='es')wp_update_post(wp_slash(array('ID'=>$p->ID,'post_content'=>$old)));
     else update_post_meta($p->ID,'_en_US_post_content',$old);
   }
 }
 echo "MDO_CONTEXT_ROLLBACK_OK\n";return;
}
$out=array();
foreach($targets as $slug=>$fragment){
 $p=get_page_by_path($slug,OBJECT,'post');
 if(!$p instanceof WP_Post||$p->post_status!=='publish')throw new RuntimeException('Existing article missing: '.$slug);
 $current=(string)$p->post_content;
 $after=str_contains($current,'mdo-context-commerce-')?$current:rtrim($current)."\n".$fragment['es'];
 if($current!==$after){
   if(get_post_meta($p->ID,'_mdo_context_original_es_20261015',true)==='') update_post_meta($p->ID,'_mdo_context_original_es_20261015',$current);
   $saved=wp_update_post(wp_slash(array('ID'=>$p->ID,'post_content'=>$after)),true);
   if(is_wp_error($saved))throw new RuntimeException($saved->get_error_message());
 }
 $en_body=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 $en_changed=false;
 if(get_post_meta($p->ID,'_en_US_published',true)==='1'&&$en_body!==''){
   $en_after=str_contains($en_body,'mdo-context-commerce-')?$en_body:rtrim($en_body)."\n".$fragment['en'];
   if($en_after!==$en_body){
     if(get_post_meta($p->ID,'_mdo_context_original_en_20261015',true)==='') update_post_meta($p->ID,'_mdo_context_original_en_20261015',$en_body);
     update_post_meta($p->ID,'_en_US_post_content',$en_after);
     $en_changed=true;
   }
 }
 clean_post_cache($p->ID);
 $out[]=array('id'=>$p->ID,'slug'=>$slug,'en_updated'=>$en_changed);
}
echo 'MDO_CONTEXT_RESULT '.wp_json_encode($out,JSON_UNESCAPED_UNICODE)."\n";
