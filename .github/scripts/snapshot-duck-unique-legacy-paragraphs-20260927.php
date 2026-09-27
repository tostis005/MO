<?php
if(!defined('ABSPATH')) exit;
function emdo_duck_snap_paras($html){
 $out=array();
 if(preg_match_all('/<p\\b[^>]*>(.*?)<\\/p>/isu',$html,$m)){
  foreach($m[1] as $inner){
   $text=trim(preg_replace('/\\s+/u',' ',wp_strip_all_tags($inner)));
   if(mb_strlen($text,'UTF-8')<120) continue;
   if(stripos($text,'El Mercado de Origen')!==false) continue;
   $norm=mb_strtolower($text,'UTF-8');
   $out[]=array('html'=>'<p>'.$inner.'</p>','norm'=>$norm);
  }
 }
 return $out;
}
$ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC','emdo_include_hidden_blog_islands'=>true));
if(count($ids)!==60) throw new Exception('Expected 60, found '.count($ids));
$all=array();$freq=array();
foreach($ids as $id){
 $ps=emdo_duck_snap_paras((string)get_post_field('post_content',$id));
 $all[(int)$id]=$ps;
 foreach(array_unique(array_column($ps,'norm')) as $n){$h=sha1($n);$freq[$h]=($freq[$h]??0)+1;}
}
foreach($all as $id=>$ps){
 if(get_post_meta($id,'_emdo_duck_legacy_unique_paras_v2',true)!==''){echo $id.'|existing'.PHP_EOL;continue;}
 if(get_post_meta($id,'_emdo_duck_content_backup_pre_v2',true)===''){
   update_post_meta($id,'_emdo_duck_content_backup_pre_v2',(string)get_post_field('post_content',$id));
 }
 $keep=array();
 foreach($ps as $p){$f=$freq[sha1($p['norm'])]??60;if($f<=5)$keep[]=array('html'=>$p['html'],'freq'=>$f,'hash'=>sha1($p['norm']));}
 usort($keep,function($a,$b){return $a['freq']<=>$b['freq'];});
 update_post_meta($id,'_emdo_duck_legacy_unique_paras_v2',wp_json_encode($keep,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
 echo $id.'|'.count($keep).PHP_EOL;
}
