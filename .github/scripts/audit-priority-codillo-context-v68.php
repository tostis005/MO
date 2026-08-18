<?php
if ( ! defined('ABSPATH') ) { fwrite(STDERR,"WordPress required\n"); exit(2); }
global $wpdb;
$targets=[4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$out=['total_hits'=>0,'by_vendor'=>[],'hits'=>[]];
foreach($targets as $aid=>$vendor){
  $out['by_vendor'][$vendor]=0;
  $ids=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$aid)));
  foreach($ids as $id){
    $p=get_post($id); if(!$p)continue;
    foreach([
      '_en_US_post_title'=>'title',
      '_en_US_post_excerpt'=>'excerpt',
      '_en_US_post_content'=>'content',
      '_en_US_post_name'=>'slug'
    ] as $key=>$field){
      $value=(string)get_post_meta($id,$key,true);
      if($value==='' || !preg_match('/\bcodillo\b/iu',$value))continue;
      $plain=html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8');
      if($field==='slug')$plain=$value;
      preg_match_all('/.{0,120}\bcodillo\b.{0,180}/iu',$plain,$m);
      $contexts=array_values(array_unique(array_map(static function($s){return trim(preg_replace('/\s+/u',' ',$s));},$m[0])));
      $out['total_hits']++;
      $out['by_vendor'][$vendor]++;
      $out['hits'][]=[
        'id'=>$id,'vendor'=>$vendor,'native_title'=>$p->post_title,
        'field'=>$field,'key'=>$key,'contexts'=>$contexts ?: [mb_substr(trim(preg_replace('/\s+/u',' ',$plain)),0,500)]
      ];
    }
  }
}
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
