<?php
/** Read-only WordPress QA of 28 SEO editorial interventions. */
if (!defined('ABSPATH')) exit(1);
$slugs=["carne-de-avestruz-sabor-propiedades-como-cocinarla","como-cocinar-carne-de-canguro-tierna","carne-de-cocodrilo-sabor-como-cocinar","carne-de-bisonte-sabor-propiedades-diferencias-ternera","carne-de-venado-sabor-cortes-como-cocinar","wagyu-japones-vs-australiano-diferencias","comprar-wagyu-japones-online-que-comprobar","que-corte-wagyu-elegir","wagyu-vs-angus-diferencias-marmoleo-sabor","estofado-de-jabali-receta-carne-tierna","hamburguesas-carnes-exoticas-comparativa","bufalo-vs-bisonte-diferencias-carne","que-significa-a5-plus-wagyu","wagyu-kobe-diferencias","que-significa-100-wagyu-etiqueta","kobe-miyazaki-kagoshima-diferencias-wagyu-japones","solomillo-de-avestruz-a-la-plancha-receta","ancas-de-rana-al-ajillo-receta","huevo-de-avestruz-peso-personas-como-cocinar","ragout-carne-de-caza-cortes-coccion","carne-de-cebra-sabor-origen-como-cocinar","como-descongelar-wagyu-correctamente","wagyu-yakiniku-teppanyaki-shabu-shabu-sukiyaki","carne-de-alce-sabor-cortes-como-cocinar","corzo-gamo-venado-diferencias-carne","hamburguesa-wagyu-japones-carne-ingredientes-coccion","entrecot-black-angus-plancha-receta","carnes-exoticas-para-barbacoa-elegir-preparar"];
$items=[];$missing=[];$orphan=[];
foreach ($slugs as $slug) {
  $post=get_page_by_path($slug,OBJECT,'post');
  if (!$post instanceof WP_Post || $post->post_status!=='publish') { $missing[]=$slug;continue; }
  $es=(string)$post->post_content;
  $en=(string)get_post_meta($post->ID,'_en_US_post_content',true);
  $clean=function($html){ $txt=trim(preg_replace('/\\s+/u',' ',wp_strip_all_tags(strip_shortcodes((string)$html))));preg_match_all('/[\\p{L}\\p{M}\\p{N}]+/u',$txt,$m);return count($m[0]); };
  $hs=function($html){preg_match_all('/<h2[^>]*>(.*?)<\\/h2>/isu',$html,$m);return array_values(array_map(fn($x)=>trim(wp_strip_all_tags($x)),$m[1]));};
  $a=(int)get_post_thumbnail_id($post->ID);
  $items[]=['slug'=>$slug,'id'=>$post->ID,'es_words'=>$clean($es),'en_words'=>$clean($en),
    'es_h2'=>$hs($es),'en_h2'=>$hs($en),
    'es_faq'=>str_contains($es,'Preguntas frecuentes'),
    'en_faq'=>str_contains($en,'Frequently asked questions'),
    'es_links'=>substr_count($es,'<a '),'en_links'=>substr_count($en,'<a '),
    'featured_image_id'=>$a,
    'featured_alt'=>$a ? get_post_meta($a,'_wp_attachment_image_alt',true):null,
    'en_ready'=>get_post_meta($post->ID,'_en_US_ready',true),
    'has_editorial_marker'=>str_contains($es,'EMDO_EXO_'),
    'title'=>$post->post_title];
}
echo 'EMDO_EDITORIAL_QA '.wp_json_encode(['verified'=>count($items)===28 && !$missing,'count'=>count($items),'missing'=>$missing,'posts'=>$items],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if ($missing) throw new RuntimeException('Editorial posts missing');
