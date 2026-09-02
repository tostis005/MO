<?php
/** Final blog hygiene: normalize Falang ready flags and trash obsolete drafts. */
if (!defined('ABSPATH')) { exit; }
function emdo_pf_words($html){preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$html),$m);return count($m[0]);}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'suppress_filters'=>true]);
$flags=[];
foreach($posts as $p){
  $title=trim((string)get_post_meta($p->ID,'_en_US_post_title',true));
  $slug=trim((string)get_post_meta($p->ID,'_en_US_post_name',true));
  $content=(string)get_post_meta($p->ID,'_en_US_post_content',true);
  $published=(string)get_post_meta($p->ID,'_en_US_published',true);
  if($title!=='' && $slug!=='' && emdo_pf_words($content)>=120 && $published==='1' && (string)get_post_meta($p->ID,'_en_US_ready',true)!=='1'){
    update_post_meta($p->ID,'_en_US_ready','1');
    $flags[]=(int)$p->ID;
  }
}
$obsolete=[13706,13741,14061,14062,14063,14064,14065,14066,14067,14068,14069,14070];
$trashed=[];
foreach($obsolete as $id){
  $st=get_post_status($id);
  if(in_array($st,['draft','pending','future','private'],true)){
    update_post_meta($id,'_en_US_published','0');
    update_post_meta($id,'_en_US_ready','0');
    $r=wp_trash_post($id);
    if($r) $trashed[]=$id;
  } elseif($st==='trash') {
    $trashed[]=$id;
  }
}
$out=['ready_flags_set'=>$flags,'ready_count'=>count($flags),'trashed'=>$trashed,'trash_count'=>count($trashed)];
echo "EMDO_PERFECTION_HYGIENE_BEGIN\n".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\nEMDO_PERFECTION_HYGIENE_END\n";
