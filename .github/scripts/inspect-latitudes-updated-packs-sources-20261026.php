<?php
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
$terms=['cadera de alce','alce','entrecot de búfalo','búfalo','jabalí','burger meat avestruz','solomillo de avestruz','canguro'];
$ids=get_posts(['post_type'=>'product','post_status'=>['publish'],'author'=>4949,'posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true]);
foreach($ids as $id){
 $p=wc_get_product($id);if(!$p||!$p->is_type('simple'))continue;
 $name=mb_strtolower(remove_accents($p->get_name()));
 foreach($terms as $t) if(str_contains($name,mb_strtolower(remove_accents($t)))){
   echo wp_json_encode(['id'=>$id,'name'=>$p->get_name(),'image_id'=>$p->get_image_id(),
   'weight'=>$p->get_weight(),'price'=>$p->get_price('edit'),'status'=>$p->get_status()],JSON_UNESCAPED_UNICODE)."\n";
   break;
 }
}
