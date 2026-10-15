<?php
/** Editorial v2 2026-10-15, batch 01. Blog posts ONLY. */
if (!defined('ABSPATH')) exit(1);
const EMDO_QUALITY_BATCH = 'quality-v2-20261015-03';
$file=(string)getenv('EMDO_QUALITY_PAYLOAD');
if (!$file || !is_file($file)) throw new RuntimeException('Missing editorial quality payload');
$data=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if (($data['batch']??'') !== EMDO_QUALITY_BATCH || count($data['posts']??[])!==4) throw new RuntimeException('Invalid editorial quality manifest');
function emdo_quality_wc(string $html): int {
 $s=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($html)));
 if($s==='')return 0;
 preg_match_all('/[\p{L}\p{M}\p{N}]+/u',$s,$m);return count($m[0]);
}
$wanted=[
 'solomillo-de-avestruz-a-la-plancha-receta',
 'ancas-de-rana-al-ajillo-receta',
 'huevo-de-avestruz-peso-personas-como-cocinar',
 'ragout-carne-de-caza-cortes-coccion'
];
$entries=[]; $marker='<!-- EMDO_QUALITY_V2_20261015_03 -->';
foreach($data['posts'] as $a) {
 foreach(['slug','en_slug','es_title','en_title','es_desc','en_desc','es','en','expected_post_marker'] as $k)
   if(!isset($a[$k]) || !is_string($a[$k]) || trim($a[$k])==='')throw new RuntimeException('Missing '.$k);
 $slug=(string)$a['slug'];
 if(!in_array($slug,$wanted,true) || isset($entries[$slug]))throw new RuntimeException('Unexpected or repeated slug '.$slug);
 if(emdo_quality_wc($a['es'])<650 || emdo_quality_wc($a['en'])<570)throw new RuntimeException('Editorial length gate: '.$slug);
 if(stripos($a['es'],'Preguntas frecuentes')!==false || stripos($a['en'],'Frequently asked questions')!==false)throw new RuntimeException('Repetitive FAQ block '.$slug);
 if(!str_contains($a['es'],'https://www.elmercadodeorigen.com/') || !str_contains($a['en'],'https://www.elmercadodeorigen.com/en/'))
   throw new RuntimeException('Missing internal ES or EN link '.$slug);
 if(str_contains($a['es'],'<script') || str_contains($a['en'],'<script'))throw new RuntimeException('Unexpected script');
 $p=get_page_by_path($slug,OBJECT,'post');
 if(!$p instanceof WP_Post || $p->post_status!=='publish')throw new RuntimeException('Expected published post not found '.$slug);
 $expected_release='blog-carnes-exoticas-20261015-03';
 if((string)get_post_meta($p->ID,'_emdo_exo_batch',true)!==$expected_release)
   throw new RuntimeException('Post not part of expected editorial release '.$slug);
 $en_slug=(string)get_post_meta($p->ID,'_en_US_post_name',true);
 if($en_slug!==$a['en_slug'])throw new RuntimeException('English URL changed for '.$slug);
 $enbody=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 if(!str_contains($p->post_content,$a['expected_post_marker']) && !str_contains($p->post_content,$marker))throw new RuntimeException('Original ownership marker missing '.$slug);
 if(!str_contains($enbody,$a['expected_post_marker']) && !str_contains($enbody,$marker))throw new RuntimeException('Original English marker missing '.$slug);
 $entries[$slug]=['post'=>$p,'article'=>$a];
}
if(count($entries)!==4)throw new RuntimeException('Entry count mismatch');
$snapshots=[]; $results=[];
try {
 foreach($entries as $slug=>$pair) {
   $p=$pair['post'];$a=$pair['article'];$id=(int)$p->ID;
   $snapshots[$id]=['post'=>['post_title'=>$p->post_title,'post_content'=>$p->post_content,'post_excerpt'=>$p->post_excerpt],
     'meta'=>[]];
   foreach(['_en_US_post_title','_en_US_post_content','_en_US_post_excerpt','_emdo_seo_title','_emdo_seo_description','_en_US_seo_title','_en_US_seo_description','_emdo_quality_v2_batch'] as $k)
     $snapshots[$id]['meta'][$k]=get_post_meta($id,$k,true);
   $es=$marker."\n".trim((string)$a['es']);
   $en=$marker."\n".trim((string)$a['en']);
   $prior=(string)get_post_meta($id,'_emdo_quality_v2_batch',true);
   $changed=($p->post_content!==$es || (string)get_post_meta($id,'_en_US_post_content',true)!==$en || $p->post_title!==$a['es_title']);
   if($changed) {
      $r=wp_update_post(wp_slash(['ID'=>$id,'post_title'=>(string)$a['es_title'],'post_content'=>$es]),true);
      if(is_wp_error($r))throw new RuntimeException('Failed updating '.$slug.': '.$r->get_error_message());
      update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);
      update_post_meta($id,'_en_US_post_content',$en);
      update_post_meta($id,'_emdo_seo_title',(string)$a['es_title']);
      update_post_meta($id,'_emdo_seo_description',(string)$a['es_desc']);
      update_post_meta($id,'_en_US_seo_title',(string)$a['en_title']);
      update_post_meta($id,'_en_US_seo_description',(string)$a['en_desc']);
   }
   update_post_meta($id,'_emdo_quality_v2_batch',EMDO_QUALITY_BATCH);
   if(get_post_status($id)!=='publish'
      || !str_contains((string)get_post_field('post_content',$id),$marker)
      || !str_contains((string)get_post_meta($id,'_en_US_post_content',true),$marker)
      || (string)get_post_meta($id,'_en_US_post_name',true)!==$a['en_slug']) throw new RuntimeException('Final bilingual verification failed '.$slug);
   $results[]=['slug'=>$slug,'id'=>$id,'es_url'=>get_permalink($id),
     'en_url'=>home_url('/en/'.trim((string)$a['en_slug'],'/').'/'),
     'es_words'=>emdo_quality_wc($es),'en_words'=>emdo_quality_wc($en),
     'existing_featured_image'=>(int)get_post_thumbnail_id($id),'changed'=>$changed];
 }
} catch(Throwable $e) {
 foreach($snapshots as $id=>$old) {
    wp_update_post(wp_slash(['ID'=>$id]+$old['post']),true);
    foreach($old['meta'] as $k=>$v)update_post_meta($id,$k,$v);
 }
 wp_cache_flush();throw $e;
}
wp_cache_flush();
echo 'EMDO_QUALITY_V2 '.wp_json_encode(['verified'=>count($results)===4,'batch'=>EMDO_QUALITY_BATCH,'count'=>count($results),'posts'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
