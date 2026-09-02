<?php
if (!defined('ABSPATH')) exit;
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);$rows=[];
foreach($posts as $p){$rows[]=['id'=>$p->ID,'es_title'=>$p->post_title,'es_slug'=>$p->post_name,'en_title'=>(string)get_post_meta($p->ID,'_en_US_post_title',true),'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),'cats'=>wp_get_post_categories($p->ID)];}
echo "EMDO_LINK_TARGET_INVENTORY_BEGIN\n";echo wp_json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_LINK_TARGET_INVENTORY_END\n";
