<?php
if(!defined('ABSPATH')) exit;
function emdo_duck_fill_words($html){$t=trim(preg_replace('/\\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));if($t==='')return 0;preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u',$t,$m);return count($m[0]);}
$ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC','emdo_include_hidden_blog_islands'=>true));
foreach($ids as $id){
 $id=(int)$id;$c=(string)get_post_field('post_content',$id);$before=emdo_duck_fill_words($c);
 $raw=(string)get_post_meta($id,'_emdo_duck_legacy_unique_paras_v2',true);$paras=json_decode($raw,true);if(!is_array($paras))$paras=array();
 $added=array();$seen=array();
 foreach($paras as $p){
   if(emdo_duck_fill_words($c)>=950) break;
   if(!is_array($p)||empty($p['html'])||empty($p['hash']))continue;
   if(isset($seen[$p['hash']]))continue;
   $text=wp_strip_all_tags($p['html']);
   if(mb_strlen($text,'UTF-8')<120)continue;
   if(stripos(wp_strip_all_tags($c),$text)!==false)continue;
   $added[]=$p['html'];$seen[$p['hash']]=true;
 }
 if($added){
   $c.="\n<h2>Detalles prácticos para completar la guía</h2>\n".implode("\n",$added);
   wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$c)));
 }
 update_post_meta($id,'_emdo_duck_content_rewrite','20260927-v2-complete');
 echo $id.'|'.$before.'|'.emdo_duck_fill_words($c).'|'.count($added).PHP_EOL;
}
