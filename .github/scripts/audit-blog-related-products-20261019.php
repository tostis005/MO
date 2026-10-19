<?php
/** Read-only full live editorial/product inventory for topical recommendations. */
if (!defined('ABSPATH')) { exit; }
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC','suppress_filters'=>true]);
$ids=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','suppress_filters'=>true]);
$report=['generated_at'=>gmdate('c'),'post_count'=>count($posts),'product_count'=>count($ids),'categories'=>[],'topics'=>[],'posts_without_category'=>[],'products'=>[],'all_post_topics'=>[],'missing_product_images'=>[]];
$terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
foreach($terms as $t){if($t instanceof WP_Term) $report['categories'][]=['slug'=>$t->slug,'parent'=>(int)$t->parent,'count'=>(int)$t->count];}
foreach($ids as $id){
 $p=wc_get_product((int)$id);if(!$p||!$p->is_visible())continue;
 $cats=wp_get_post_terms((int)$id,'product_cat',['fields'=>'slugs']);if(is_wp_error($cats))$cats=[];
 $image_id=(int)$p->get_image_id();
 $row=['id'=>(int)$id,'title'=>$p->get_name(),'cats'=>$cats,'stock'=>$p->is_in_stock(),'image_id'=>$image_id,'image_url'=>$image_id?(string)wp_get_attachment_image_url($image_id,'woocommerce_thumbnail'):''];
 $report['products'][]=$row;
 if(!$row['image_url'])$report['missing_product_images'][]=$row;
}
foreach($posts as $post){
 $post_id=(int)$post->ID;
 $cats=wp_get_post_terms($post_id,'category',['fields'=>'slugs']);if(is_wp_error($cats))$cats=[];
 $topic=implode(' ',array_merge([$post->post_name,$post->post_title],$cats));
 $bucket='other';
 if(preg_match('/wagyu|kobe|miyazaki|kagoshima|marmoleo|bms|kuroge|yakiniku|teppanyaki|sukiyaki/i',$topic))$bucket='wagyu';
 elseif(preg_match('/chocolate|cacao|bombon|praline|ganache|trufa|tableta|bean.to.bar|turron/i',$topic))$bucket='chocolate';
 elseif(preg_match('/pato|foie|confit|magret|rillettes|mousse|micuit|mi.cuit|parfait|manchon/i',$topic))$bucket='pato';
 $selected=function_exists('elmercado_blog_product_category_slug_010277')?elmercado_blog_product_category_slug_010277($post_id):'';
 $row=['id'=>$post_id,'slug'=>$post->post_name,'title'=>$post->post_title,'categories'=>$cats,'product_category'=>$selected,'has_explicit_shortcode'=>(bool)preg_match('/\\[(products|product_category|elmercado_dynamic_products)\\b/i',(string)$post->post_content),'excerpt'=>mb_substr(wp_strip_all_tags((string)$post->post_content),0,90)];
 if($bucket!=='other'){$report['topics'][$bucket][]=$row;}else{$report['all_post_topics'][]=['id'=>$post_id,'cats'=>$cats,'product_category'=>$selected,'title'=>$post->post_title];}
 if(!$selected)$report['posts_without_category'][]=$row;
}
echo 'EMDO_BLOG_PRODUCTS_INVENTORY='.wp_json_encode($report,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
