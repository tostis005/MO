<?php
if (!defined('ABSPATH')) exit;
function emdo_v_words($s){ preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$s),$m); return count($m[0]); }
$cat_ids=[439,440,441,442,443,444,438,450,445];
$cats=[];
foreach($cat_ids as $id){
 $t=get_term($id,'category');
 if(!$t||is_wp_error($t)) throw new Exception('Missing category '.$id);
 $en=(string)get_term_meta($id,'_en_US_description',true);
 if(emdo_v_words($t->description)<45 || emdo_v_words($en)<40) throw new Exception('Category copy too short '.$id);
 $cats[]=['id'=>$id,'slug'=>$t->slug,'count'=>$t->count,'es_words'=>emdo_v_words($t->description),'en_words'=>emdo_v_words($en)];
}
$slugs=[
'dop-igp-etg-diferencias-sellos-calidad-alimentos',
'como-leer-etiqueta-alimento-ingredientes-nutricion-origen-lote-conservacion',
'fecha-caducidad-consumo-preferente-diferencias',
'origen-etiqueta-alimento-pais-procedencia-ingrediente-primario',
'trazabilidad-alimentaria-que-es-como-funciona-productor-consumidor'
];
$posts=[];
foreach($slugs as $slug){
 $p=get_page_by_path($slug,OBJECT,'post');
 if(!$p||$p->post_status!=='publish') throw new Exception('Missing live pillar '.$slug);
 $en_slug=(string)get_post_meta($p->ID,'_en_US_post_name',true);$en_content=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 if(get_post_meta($p->ID,'_en_US_ready',true)!=='1'||get_post_meta($p->ID,'_en_US_published',true)!=='1') throw new Exception('EN flags bad '.$p->ID);
 if(emdo_v_words($p->post_content)<500||emdo_v_words($en_content)<450) throw new Exception('Pillar too short '.$p->ID);
 if(!in_array(445,wp_get_post_categories($p->ID),true)) throw new Exception('Wrong category '.$p->ID);
 $posts[]=['id'=>$p->ID,'slug'=>$slug,'en_slug'=>$en_slug,'es_words'=>emdo_v_words($p->post_content),'en_words'=>emdo_v_words($en_content),'es_url'=>get_permalink($p->ID),'en_url'=>rtrim(home_url('/'),'/').'/en/'.$en_slug.'/'];
}
$alt=(string)get_post_meta(13442,'_wp_attachment_image_alt',true);
if($alt!=='') throw new Exception('Placeholder alt not empty');

$sample=$posts[0]['es_url'];
$r=wp_remote_get($sample,['timeout'=>20,'redirection'=>2]);
if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200) throw new Exception('Sample HTTP failed');
$body=(string)wp_remote_retrieve_body($r);
$schema=[
 'has_blogposting'=>strpos($body,'"@type":"BlogPosting"')!==false,
 'has_org_author_ref'=>strpos($body,home_url('/').'#organization')!==false,
 'has_person_author_node'=>strpos($body,'"@type":"Person"')!==false && strpos($body,'#author')!==false,
 'has_datePublished'=>strpos($body,'datePublished')!==false,
 'has_dateModified'=>strpos($body,'dateModified')!==false,
 'has_article_published_og'=>strpos($body,'article:published_time')!==false,
 'has_placeholder_text'=>stripos($body,'Imagen provisional del blog de El Mercado de Origen')!==false,
];
if(!$schema['has_blogposting']||!$schema['has_org_author_ref']||$schema['has_person_author_node']||$schema['has_datePublished']||$schema['has_dateModified']||$schema['has_article_published_og']||$schema['has_placeholder_text']) throw new Exception('Schema/placeholder verification failed '.wp_json_encode($schema));

$sitemap_url=rtrim(home_url('/'),'/').'/mdo-sitemap-posts.xml';
$sr=wp_remote_get($sitemap_url,['timeout'=>20]);$sbody=is_wp_error($sr)?'':(string)wp_remote_retrieve_body($sr);
$sitemap=['url'=>$sitemap_url,'code'=>is_wp_error($sr)?0:(int)wp_remote_retrieve_response_code($sr),'lastmod_present'=>strpos($sbody,'<lastmod>')!==false,'missing'=>[]];
foreach($posts as $p){ if(strpos($sbody,$p['es_url'])===false) $sitemap['missing'][]=$p['es_url']; if(strpos($sbody,$p['en_url'])===false) $sitemap['missing'][]=$p['en_url']; }
if($sitemap['code']!==200||!$sitemap['lastmod_present']||$sitemap['missing']) throw new Exception('Sitemap verification failed '.wp_json_encode($sitemap));

echo "EMDO_SEO_NEXT_VERIFY_BEGIN\n";
echo wp_json_encode(['categories'=>$cats,'pillars'=>$posts,'placeholder'=>['id'=>13442,'alt'=>$alt,'title'=>get_the_title(13442)],'schema'=>$schema,'sitemap'=>$sitemap,'backup_present'=>(bool)get_option('emdo_seo_next_pass_backup_20260902',false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_SEO_NEXT_VERIFY_END\n";
