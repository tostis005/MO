<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
$default_id=(int)get_option('default_product_cat');
$terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
if(is_wp_error($terms)){exit(2);}
$out=array('checked_at'=>gmdate('c'),'visible_roots'=>array(),'issues'=>array(),'routes'=>array());
foreach($terms as $term){
 if((int)$term->parent!==0||(int)$term->term_id===$default_id||in_array($term->slug,array('mentta','menta'),true)||(int)$term->count<=0){continue;}
 $id=(int)$term->term_id;
 $row=array('id'=>$id,'name'=>(string)$term->name,'slug'=>(string)$term->slug,'count'=>(int)$term->count,'en_published'=>(string)get_term_meta($id,'_en_US_published',true),'en_name'=>trim((string)get_term_meta($id,'_en_US_name',true)),'en_slug'=>sanitize_title((string)get_term_meta($id,'_en_US_slug',true)),'en_description'=>trim(wp_strip_all_tags((string)get_term_meta($id,'_en_US_description',true))),'en_hub_summary'=>trim(wp_strip_all_tags((string)get_term_meta($id,'_emdo_en_hub_summary',true))));
 if($row['en_published']!=='1'||$row['en_name']===''||$row['en_slug']===''||$row['en_hub_summary']===''){$out['issues'][]=array('id'=>$id,'slug'=>$term->slug,'reason'=>'incomplete_visible_english_category');}
 $out['visible_roots'][]=$row;
}
$page=get_page_by_path('categorias',OBJECT,'page');
if($page instanceof WP_Post){$out['routes']['categories_es']=get_permalink($page);$out['routes']['categories_en']=function_exists('mdo_en_page_url')?mdo_en_page_url((int)$page->ID):'';}
$shop_id=function_exists('wc_get_page_id')?(int)wc_get_page_id('shop'):(int)get_option('woocommerce_shop_page_id');
$out['routes']['shop_en']=$shop_id>0&&function_exists('mdo_en_page_url')?mdo_en_page_url($shop_id):home_url('/en/shop/');
$out['routes']['huerta_store_en']=home_url('/en/store/la-huerta-de-ana-mary/');
foreach($out['visible_roots'] as &$row){$term=get_term((int)$row['id'],'product_cat');$row['en_url']=$term instanceof WP_Term&&function_exists('mdo_en_term_url')?mdo_en_term_url($term):home_url('/en/product-category/'.$row['en_slug'].'/');}unset($row);
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
if($out['issues'])exit(3);
