<?php
if (!defined('ABSPATH')) exit(1);
global $wp_post_types,$wpdb;
$out=['plugins'=>[],'post_types'=>[],'posts'=>[],'tables'=>[]];
foreach((array)get_option('active_plugins',[]) as $p){
 if(preg_match('/emdo|mercado|special|promo|oferta/i',$p)) $out['plugins'][]=$p;
}
foreach(get_post_types([], 'objects') as $name=>$obj){
 $label=(string)($obj->labels->name??$obj->label??''); $sing=(string)($obj->labels->singular_name??'');
 if(preg_match('/especial|promo|oferta|destacad|special/i',$name.' '.$label.' '.$sing)){
  $out['post_types'][$name]=['label'=>$label,'singular'=>$sing,'public'=>(bool)$obj->public,'show_ui'=>(bool)$obj->show_ui];
  foreach(get_posts(['post_type'=>$name,'post_status'=>'any','posts_per_page'=>8,'orderby'=>'ID','order'=>'DESC']) as $r){
   $meta=[]; foreach(get_post_meta($r->ID) as $k=>$vals){ if(preg_match('/image|product|price|link|url|cta|button|home|active|order|date|special|promo/i',$k)) $meta[$k]=array_map('maybe_unserialize',$vals); }
   $out['posts'][]=['id'=>$r->ID,'type'=>$name,'title'=>$r->post_title,'status'=>$r->post_status,'excerpt'=>$r->post_excerpt,'thumb'=>(int)get_post_thumbnail_id($r->ID),'meta'=>$meta];
  }
 }
}
foreach($wpdb->get_col('SHOW TABLES') as $t){ if(preg_match('/emdo|special|promo|oferta/i',$t)) $out['tables'][]=$t; }
echo 'EMDO_COMPACT:'.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
