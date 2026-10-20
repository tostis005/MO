<?php
/** Read-only export: currently published products missing real English fields. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
$ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID ASC");
$out=['site'=>get_option('siteurl'),'generated_at'=>gmdate('c'),'published_total'=>count($ids),'products'=>[]];
foreach($ids as $raw){
  $id=(int)$raw; $p=get_post($id);
  if(!$p || $p->post_status!=='publish')continue;
  $en=[
   'published'=>(string)get_post_meta($id,'_en_US_published',true),
   'title'=>(string)get_post_meta($id,'_en_US_post_title',true),
   'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
   'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),
   'content'=>(string)get_post_meta($id,'_en_US_post_content',true),
  ];
  $plain=static function($s){return trim(preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$s)));};
  $missing=$en['published']!=='1'||$plain($en['title'])===''||$en['slug']===''||($plain($p->post_excerpt)!==''&&$plain($en['excerpt'])==='')||($plain($p->post_content)!==''&&$plain($en['content'])==='');
  if(!$missing)continue;
  $out['products'][]=[
    'id'=>$id,'author_id'=>(int)$p->post_author,'status'=>(string)$p->post_status,
    'title'=>(string)$p->post_title,'excerpt'=>(string)$p->post_excerpt,'content'=>(string)$p->post_content,
    'existing_english'=>$en
  ];
}
$out['count']=count($out['products']);
echo wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n";
