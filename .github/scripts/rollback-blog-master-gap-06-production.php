<?php
/** Roll back only posts created by EMDO master gap batch 06. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
const EMDO_MASTER_GAP_06_ROLLBACK_MARKER = '2026-09-08.master-gap-06';
const EMDO_GAP06_ROLLBACK_COMP_SHA = 'e057e1e087154844a440ba504ba5c8cf0fc0db5525c2a131740a53d8fc9ee83a';
const EMDO_GAP06_ROLLBACK_RAW_SHA = '9792f7f263b29ad45d162df57403b64e5b45c7b519ba8f17a0da50702c9c0f02';
$enc=''; for($i=1;$i<=2;$i++){ $p=__DIR__.'/blog-master-gap-06-'.$i.'.b64'; if(is_readable($p))$enc.=trim((string)file_get_contents($p)); }
$comp=base64_decode($enc,true); $json=false;
if(false!==$comp && hash('sha256',$comp)===EMDO_GAP06_ROLLBACK_COMP_SHA){ $json=gzdecode($comp); if(false!==$json && hash('sha256',$json)!==EMDO_GAP06_ROLLBACK_RAW_SHA)$json=false; }
$articles=false===$json?array():json_decode($json,true); $deleted=[];
if(is_array($articles)){
 foreach($articles as $a){
  $slug=isset($a['slug'])?(string)$a['slug']:''; if($slug==='')continue;
  $post=get_page_by_path($slug,OBJECT,'post'); if(!$post instanceof WP_Post)continue;
  if((string)get_post_meta($post->ID,'_emdo_master_gap_06',true)!==EMDO_MASTER_GAP_06_ROLLBACK_MARKER)continue;
  if(wp_delete_post($post->ID,true))$deleted[]=(int)$post->ID;
 }
}
flush_rewrite_rules(false); wp_cache_flush(); echo wp_json_encode(array('deleted'=>$deleted,'count'=>count($deleted)))."\n";
