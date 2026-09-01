<?php
if ( ! defined('ABSPATH') ) { exit; }
$posts = get_posts(array(
  'post_type'=>'post','post_status'=>array('draft','publish','pending','private'),'posts_per_page'=>50,
  'meta_key'=>'_emdo_cheese_batch02','meta_value'=>'2026-09-01.cheese-02.v1','orderby'=>'ID','order'=>'ASC'
));
$out=array();
foreach($posts as $p){
  $es=(string)$p->post_content;
  $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
  $out[]=array(
    'id'=>(int)$p->ID,'status'=>get_post_status($p),'slug'=>$p->post_name,'title'=>$p->post_title,
    'es_words'=>str_word_count(wp_strip_all_tags($es)),'en_words'=>str_word_count(wp_strip_all_tags($en)),
    'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),
    'related_es'=>substr_count($es,'<h2>Guías relacionadas</h2>'),
    'related_en'=>substr_count($en,'<h2>Related guides</h2>'),
    'faq_es'=>substr_count($es,'<h2>Preguntas relacionadas</h2>'),
    'faq_en'=>substr_count($en,'<h2>Related questions</h2>')
  );
}
echo "EMDO_CHEESE02_DIAG_BEGIN\n";
echo wp_json_encode(array('count'=>count($out),'posts'=>$out),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_CHEESE02_DIAG_END\n";
