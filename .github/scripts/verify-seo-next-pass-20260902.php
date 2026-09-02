<?php
if (!defined('ABSPATH')) exit;
function emdo_v_words($s){ preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$s),$m); return count($m[0]); }
$errors=[];
$cat_ids=[439,440,441,442,443,444,438,450,445];
$cats=[];
foreach($cat_ids as $id){
 $t=get_term($id,'category');
 if(!$t||is_wp_error($t)){ $errors[]='Missing category '.$id; continue; }
 $en=(string)get_term_meta($id,'_en_US_description',true);
 $esw=emdo_v_words($t->description); $enw=emdo_v_words($en);
 if($esw<45 || $enw<40) $errors[]='Category copy too short '.$id;
 $cats[]=['id'=>$id,'slug'=>$t->slug,'count'=>$t->count,'es_words'=>$esw,'en_words'=>$enw];
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
 if(!$p||$p->post_status!=='publish'){ $errors[]='Missing live pillar '.$slug; continue; }
 $en_slug=(string)get_post_meta($p->ID,'_en_US_post_name',true);$en_content=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 $ready=(string)get_post_meta($p->ID,'_en_US_ready',true); $published=(string)get_post_meta($p->ID,'_en_US_published',true);
 $esw=emdo_v_words($p->post_content); $enw=emdo_v_words($en_content); $categories=array_map('intval',wp_get_post_categories($p->ID));
 if($ready!=='1'||$published!=='1') $errors[]='EN flags bad '.$p->ID;
 if($esw<500||$enw<450) $errors[]='Pillar too short '.$p->ID;
 if(!in_array(445,$categories,true)) $errors[]='Wrong category '.$p->ID;
 $posts[]=['id'=>$p->ID,'slug'=>$slug,'en_slug'=>$en_slug,'es_words'=>$esw,'en_words'=>$enw,'ready'=>$ready,'published'=>$published,'categories'=>$categories,'es_url'=>get_permalink($p->ID),'en_url'=>rtrim(home_url('/'),'/').'/en/'.$en_slug.'/'];
}
$alt=(string)get_post_meta(13442,'_wp_attachment_image_alt',true);
if($alt!=='') $errors[]='Placeholder alt not empty';

$schema=['sample'=>null,'http_code'=>0,'has_blogposting'=>false,'has_org_author_ref'=>false,'has_person_author_node'=>false,'has_datePublished'=>false,'has_dateModified'=>false,'has_article_published_og'=>false,'has_placeholder_text'=>false,'jsonld_types'=>[]];
if($posts){
 $sample=$posts[0]['es_url']; $schema['sample']=$sample;
 $r=wp_remote_get($sample,['timeout'=>20,'redirection'=>2,'headers'=>['Cache-Control'=>'no-cache']]);
 if(is_wp_error($r)){ $errors[]='Sample HTTP failed: '.$r->get_error_message(); }
 else {
   $schema['http_code']=(int)wp_remote_retrieve_response_code($r); $body=(string)wp_remote_retrieve_body($r);
   if($schema['http_code']!==200) $errors[]='Sample HTTP code '.$schema['http_code'];
   $schema['has_blogposting']=strpos($body,'"@type":"BlogPosting"')!==false;
   $schema['has_org_author_ref']=strpos($body,home_url('/').'#organization')!==false;
   $schema['has_person_author_node']=strpos($body,'"@type":"Person"')!==false && strpos($body,'#author')!==false;
   $schema['has_datePublished']=strpos($body,'datePublished')!==false;
   $schema['has_dateModified']=strpos($body,'dateModified')!==false;
   $schema['has_article_published_og']=strpos($body,'article:published_time')!==false;
   $schema['has_placeholder_text']=stripos($body,'Imagen provisional del blog de El Mercado de Origen')!==false;
   if(preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',$body,$mm)){
     foreach($mm[1] as $raw){ $j=json_decode(html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8'),true); if(!is_array($j)) continue; $nodes=isset($j['@graph'])&&is_array($j['@graph'])?$j['@graph']:[$j]; foreach($nodes as $node){ if(isset($node['@type'])) $schema['jsonld_types'][]=$node['@type']; } }
   }
   if(!$schema['has_blogposting']) $errors[]='Schema missing BlogPosting';
   if(!$schema['has_org_author_ref']) $errors[]='Schema missing Organization author ref';
   if($schema['has_person_author_node']) $errors[]='Schema still has Person author node';
   if($schema['has_datePublished']) $errors[]='Schema still has datePublished';
   if($schema['has_dateModified']) $errors[]='Schema still has dateModified';
   if($schema['has_article_published_og']) $errors[]='OpenGraph still has article:published_time';
   if($schema['has_placeholder_text']) $errors[]='Frontend still exposes placeholder text';
 }
}

$sitemap_url=rtrim(home_url('/'),'/').'/mdo-sitemap-posts.xml';
$sr=wp_remote_get($sitemap_url,['timeout'=>20,'headers'=>['Cache-Control'=>'no-cache']]);$sbody=is_wp_error($sr)?'':(string)wp_remote_retrieve_body($sr);
$sitemap=['url'=>$sitemap_url,'code'=>is_wp_error($sr)?0:(int)wp_remote_retrieve_response_code($sr),'lastmod_present'=>strpos($sbody,'<lastmod>')!==false,'missing'=>[]];
foreach($posts as $p){ if(strpos($sbody,$p['es_url'])===false) $sitemap['missing'][]=$p['es_url']; if(strpos($sbody,$p['en_url'])===false) $sitemap['missing'][]=$p['en_url']; }
if($sitemap['code']!==200) $errors[]='Sitemap HTTP code '.$sitemap['code'];
if(!$sitemap['lastmod_present']) $errors[]='Sitemap missing lastmod';
if($sitemap['missing']) $errors[]='Sitemap missing pillar URLs';

$out=['errors'=>$errors,'categories'=>$cats,'pillars'=>$posts,'placeholder'=>['id'=>13442,'alt'=>$alt,'title'=>get_the_title(13442)],'schema'=>$schema,'sitemap'=>$sitemap,'backup_present'=>(bool)get_option('emdo_seo_next_pass_backup_20260902',false)];
echo "EMDO_SEO_NEXT_VERIFY_BEGIN\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_SEO_NEXT_VERIFY_END\n";
if($errors){ throw new Exception('Verification errors: '.implode(' | ',$errors)); }
