<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$vendors=[4508=>'Puente Robles',4509=>'El Catedrático'];
$expected=['Puente Robles'=>106,'El Catedrático'=>95];
$out=['ok'=>true,'total'=>0,'vendors'=>[],'ready'=>0,'published_en'=>0,'missing_fields'=>[],'bad_patterns'=>[],'bad_slugs'=>[],'duplicate_slugs'=>[],'status_counts'=>[]];
$seen=[];
$bad_text='/\b(?:cesarean|caesarean|reservation|headboard)\b|Iberian\s+Iberian|\bCátedra\b/ui';
$bad_slug='/\b(?:jamon|paleta|lote|deshuesado|cortado-a-cuchillo|cortado-a-maquina|raza-iberica)\b/ui';
foreach($vendors as $uid=>$vendor){
  $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$uid));
  $out['vendors'][$vendor]=['total'=>count($ids),'ready'=>0,'published_en'=>0,'complete'=>0,'bad'=>0];
  foreach($ids as $id){
    $id=(int)$id;$p=get_post($id);$out['total']++;
    $out['status_counts'][$vendor][$p->post_status]=($out['status_counts'][$vendor][$p->post_status]??0)+1;
    $title=(string)get_post_meta($id,'_en_US_post_title',true);
    $content=(string)get_post_meta($id,'_en_US_post_content',true);
    $excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);
    $slug=(string)get_post_meta($id,'_en_US_post_name',true);
    $ready=(string)get_post_meta($id,'_en_US_ready',true);
    $pub=(string)get_post_meta($id,'_en_US_published',true);
    if($ready==='1'){$out['ready']++;$out['vendors'][$vendor]['ready']++;}
    if($pub==='1'){$out['published_en']++;$out['vendors'][$vendor]['published_en']++;}
    $missing=[];
    if(trim($title)==='')$missing[]='title';
    if(trim($slug)==='')$missing[]='slug';
    if(trim(wp_strip_all_tags((string)$p->post_content))!=='' && trim(wp_strip_all_tags($content))==='')$missing[]='content';
    if(trim(wp_strip_all_tags((string)$p->post_excerpt))!=='' && trim(wp_strip_all_tags($excerpt))==='')$missing[]='excerpt';
    if($ready!=='1')$missing[]='ready';
    if($pub!=='0')$missing[]='published_flag';
    if($missing){$out['missing_fields'][]=['id'=>$id,'vendor'=>$vendor,'missing'=>$missing];$out['vendors'][$vendor]['bad']++;}
    else{$out['vendors'][$vendor]['complete']++;}
    $text=html_entity_decode(wp_strip_all_tags($title.' '.$content.' '.$excerpt),ENT_QUOTES|ENT_HTML5,'UTF-8');
    if(preg_match($bad_text,$text,$m)){$out['bad_patterns'][]=['id'=>$id,'vendor'=>$vendor,'match'=>$m[0]];$out['vendors'][$vendor]['bad']++;}
    $san=sanitize_title($slug);
    if($san!==$slug || preg_match('/[^a-z0-9-]/',$slug) || preg_match($bad_slug,$slug,$m)){$out['bad_slugs'][]=['id'=>$id,'vendor'=>$vendor,'slug'=>$slug,'match'=>$m[0]??'format'];$out['vendors'][$vendor]['bad']++;}
    if(isset($seen[$slug])&&$seen[$slug]!==$id){$out['duplicate_slugs'][]=['slug'=>$slug,'ids'=>[$seen[$slug],$id]];$out['vendors'][$vendor]['bad']++;}else{$seen[$slug]=$id;}
  }
}
foreach($expected as $vendor=>$count){if(($out['vendors'][$vendor]['total']??0)!==$count)$out['ok']=false;}
if($out['total']!==201||$out['ready']!==201||$out['published_en']!==0||$out['missing_fields']||$out['bad_patterns']||$out['bad_slugs']||$out['duplicate_slugs'])$out['ok']=false;
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
exit($out['ok']?0:2);
