<?php
if ( ! defined('ABSPATH') ) { exit(1); }
$ids = [11097,11154];
function mdo_num_contexts($s){
    $text=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $text=preg_replace('/\s+/u',' ',$text);
    preg_match_all('/\d+(?:[.,]\d+)?/u',$text,$m,PREG_OFFSET_CAPTURE);
    $out=[];
    foreach($m[0] as $hit){
        [$num,$pos]=$hit;
        $start=max(0,$pos-75);$len=min(strlen($text)-$start,strlen($num)+150);
        $out[]=['num'=>$num,'context'=>substr($text,$start,$len)];
    }
    return $out;
}
foreach($ids as $id){
    $p=get_post($id);
    if(!$p){echo "MISSING $id\n";continue;}
    $en_title=(string)get_post_meta($id,'_en_US_post_title',true);
    $en_content=(string)get_post_meta($id,'_en_US_post_content',true);
    $en_excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);
    $src=$p->post_title.' '.$p->post_content.' '.$p->post_excerpt;
    $dst=$en_title.' '.$en_content.' '.$en_excerpt;
    echo 'PRODUCT '.wp_json_encode([
      'id'=>$id,'source_title'=>$p->post_title,'english_title'=>$en_title,
      'source_contexts'=>mdo_num_contexts($src),'english_contexts'=>mdo_num_contexts($dst)
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
