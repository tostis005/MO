<?php
if (!defined('ABSPATH')) exit;
function emdo_v_words($s){ preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$s),$m); return count($m[0]); }
function emdo_v_schema_state($url){
 $state=['url'=>$url,'http_code'=>0,'has_blogposting'=>false,'blogposting_author'=>null,'has_org_author_ref'=>false,'has_person_author_node'=>false,'has_datePublished'=>false,'has_dateModified'=>false,'has_article_published_og'=>false,'has_placeholder_text'=>false,'has_visible_date_hide_css'=>false,'jsonld_types'=>[]];
 $r=wp_remote_get(add_query_arg('emdo_verify',time(),$url),['timeout'=>20,'redirection'=>2,'headers'=>['Cache-Control'=>'no-cache']]);
 if(is_wp_error($r)){ $state['error']=$r->get_error_message(); return $state; }
 $state['http_code']=(int)wp_remote_retrieve_response_code($r); $body=(string)wp_remote_retrieve_body($r);
 $state['has_article_published_og']=strpos($body,'article:published_time')!==false;
 $state['has_placeholder_text']=stripos($body,'Imagen provisional del blog de El Mercado de Origen')!==false;
 $state['has_visible_date_hide_css']=strpos($body,'emdo-evergreen-visible-date-20260902')!==false;
 if(preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',$body,$mm)){
  foreach($mm[1] as $raw){
   $j=json_decode(html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8'),true); if(!is_array($j)) continue;
   $nodes=isset($j['@graph'])&&is_array($j['@graph'])?$j['@graph']:[$j];
   foreach($nodes as $node){
    if(!is_array($node)||empty($node['@type'])) continue;
    $types=(array)$node['@type']; foreach($types as $type)$state['jsonld_types'][]=$type;
    $lower=array_map('strtolower',$types);
    if(array_intersect($lower,['article','blogposting','newsarticle'])){
     $state['has_blogposting']=true;
     $state['blogposting_author']=$node['author']??null;
     $author_id=is_array($state['blogposting_author'])?($state['blogposting_author']['@id']??''):'';
     $author_parts=wp_parse_url((string)$author_id); $site_parts=wp_parse_url(home_url('/'));
     $state['has_org_author_ref']=!empty($author_parts['host'])&&!empty($site_parts['host'])&&strtolower($author_parts['host'])===strtolower($site_parts['host'])&&str_ends_with((string)$author_id,'/#organization');
     if(isset($node['datePublished']))$state['has_datePublished']=true;
     if(isset($node['dateModified']))$state['has_dateModified']=true;
    }
    if(in_array('person',$lower,true)){
     $name=trim(wp_strip_all_tags((string)($node['name']??''))); $id=(string)($node['@id']??'');
     if($name==='El Mercado de Origen'||str_contains($id,'/author/admin-mercado/'))$state['has_person_author_node']=true;
    }
   }
  }
 }
 return $state;
}
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

$schema=[];
if($posts){
 foreach([['lang'=>'es','url'=>$posts[0]['es_url']],['lang'=>'en','url'=>$posts[0]['en_url']]] as $sample){
  $s=emdo_v_schema_state($sample['url']); $s['lang']=$sample['lang']; $schema[]=$s;
  if(($s['http_code']??0)!==200)$errors[]='Sample HTTP failed '.$sample['lang'];
  if(!$s['has_blogposting'])$errors[]='Schema missing BlogPosting '.$sample['lang'];
  if(!$s['has_org_author_ref'])$errors[]='BlogPosting author is not Organization '.$sample['lang'];
  if($s['has_person_author_node'])$errors[]='Schema still has brand Person author '.$sample['lang'];
  if(!$s['has_datePublished'])$errors[]='Schema missing truthful datePublished '.$sample['lang'];
  if(!$s['has_dateModified'])$errors[]='Schema missing truthful dateModified '.$sample['lang'];
  if(!$s['has_visible_date_hide_css'])$errors[]='Visible evergreen date CSS missing '.$sample['lang'];
  if($s['has_placeholder_text'])$errors[]='Frontend still exposes placeholder text '.$sample['lang'];
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
