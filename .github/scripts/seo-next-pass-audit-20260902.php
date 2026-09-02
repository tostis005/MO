<?php
if (!defined('ABSPATH')) exit;
function emdo_np_words($s){ preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$s),$m); return count($m[0]); }
$cats=get_terms(['taxonomy'=>'category','hide_empty'=>false]);
$cat_out=[];
foreach($cats as $t){
  $meta=get_term_meta($t->term_id);
  $small=[];
  foreach($meta as $k=>$v){
    if(stripos($k,'en')!==false || stripos($k,'seo')!==false || stripos($k,'description')!==false || stripos($k,'falang')!==false) $small[$k]=$v;
  }
  $cat_out[]=['id'=>$t->term_id,'name'=>$t->name,'slug'=>$t->slug,'count'=>$t->count,'parent'=>$t->parent,'description'=>$t->description,'description_words'=>emdo_np_words($t->description),'meta'=>$small];
}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'suppress_filters'=>true]);
$img=[];$date_counts=[];$authors=[];
foreach($posts as $p){
  $d=substr($p->post_date,0,10);$date_counts[$d]=($date_counts[$d]??0)+1;
  $authors[$p->post_author]=($authors[$p->post_author]??0)+1;
  $thumb=get_post_thumbnail_id($p->ID);
  if($thumb){
    $alt=(string)get_post_meta($thumb,'_wp_attachment_image_alt',true);
    $title=(string)get_the_title($thumb);
    if($alt==='' || stripos($alt,'provisional')!==false || stripos($alt,'placeholder')!==false || stripos($title,'provisional')!==false || stripos($title,'placeholder')!==false){
      $img[]=['post_id'=>$p->ID,'post_title'=>$p->post_title,'post_slug'=>$p->post_name,'attachment_id'=>$thumb,'alt'=>$alt,'attachment_title'=>$title];
    }
  } else {
    $img[]=['post_id'=>$p->ID,'post_title'=>$p->post_title,'post_slug'=>$p->post_name,'attachment_id'=>0,'alt'=>'','attachment_title'=>'NO_FEATURED_IMAGE'];
  }
}
arsort($date_counts);arsort($authors);
$needles=['DOP, IGP','DOP IGP','ETG','etiqueta de un alimento','fecha de caducidad','consumo preferente','origen en la etiqueta','trazabilidad alimentaria'];
$topic_hits=[];
foreach($needles as $needle){
  $hits=[];
  foreach($posts as $p){
    $hay=mb_strtolower($p->post_title.' '.wp_strip_all_tags($p->post_content),'UTF-8');
    if(mb_strpos($hay,mb_strtolower($needle,'UTF-8'))!==false) $hits[]=['id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name];
  }
  $topic_hits[$needle]=array_slice($hits,0,25);
}
$sample=[];
foreach(array_slice($posts,0,3) as $p){
  $u=get_permalink($p->ID);$r=wp_remote_get($u,['timeout'=>20]);$body=is_wp_error($r)?'':wp_remote_retrieve_body($r);
  preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',$body,$m);
  $sample[]=['id'=>$p->ID,'url'=>$u,'date'=>$p->post_date,'modified'=>$p->post_modified,'author'=>(int)$p->post_author,'html_has_visible_published'=>(bool)preg_match('/(Publicado|Published|Actualizado|Updated)[^<]{0,80}\d{4}/iu',$body),'jsonld'=>array_slice($m[1]??[],0,5)];
}
echo "EMDO_SEO_NEXT_PASS_BEGIN\n";
echo wp_json_encode(['categories'=>$cat_out,'placeholder_images_count'=>count($img),'placeholder_images'=>$img,'publish_date_counts'=>$date_counts,'authors'=>$authors,'topic_hits'=>$topic_hits,'samples'=>$sample],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_SEO_NEXT_PASS_END\n";
