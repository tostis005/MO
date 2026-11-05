<?php
/**
 * Read-only B02 SEO inventory: legacy 25/26 oil references from producer 1957.
 * No posts/terms/options/prices are modified.
 */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) {exit(2);}
$slugs=array(
  'aceite-de-oliva-virgen-extra-5l',
  'aceite-de-oliva-virgen-extra-1l',
  'aceite-de-oliva-virgen-extra-500ml-2'
);
$out=array('audit'=>'aove-1957-legacy-20261105','products'=>array());
foreach($slugs as $slug){
  $post=get_page_by_path($slug,OBJECT,'product');
  if(!$post instanceof WP_Post){$out['products'][]=array('slug'=>$slug,'issue'=>'not_found');continue;}
  $p=wc_get_product($post->ID);
  if(!$p){$out['products'][]=array('slug'=>$slug,'issue'=>'not_a_product');continue;}
  $row=array('id'=>$post->ID,'slug'=>$slug,'title'=>$p->get_name(),'status'=>$p->get_status(),
    'type'=>$p->get_type(),'permalink'=>get_permalink($post->ID),
    'visible'=>$p->is_visible(),'stock_status'=>$p->get_stock_status(),
    'price'=>$p->get_price(),'price_html'=>wp_strip_all_tags($p->get_price_html()),
    'categories'=>wp_get_post_terms($post->ID,'product_cat',array('fields'=>'slugs')),
    'summary'=>wp_strip_all_tags($p->get_short_description()),'variations'=>array());
  if($p->is_type('variable')){
    foreach($p->get_children() as $id){
      $v=wc_get_product($id);if(!$v)continue;
      $row['variations'][]=array('id'=>$id,'status'=>$v->get_status(),
        'purchasable'=>$v->is_purchasable(),'in_stock'=>$v->is_in_stock(),
        'attributes'=>$v->get_attributes(),'price'=>$v->get_price());
    }
  }
  $row['live_variants']=count(array_filter($row['variations'],static fn($v)=>$v['status']==='publish' && $v['purchasable'] && $v['in_stock']));
  $out['products'][]=$row;
}
echo 'LEGACY_1957_AUDIT='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(count($out['products'])!==3 || count(array_filter($out['products'],static fn($p)=>isset($p['issue'])))>0)exit(3);
