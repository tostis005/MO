<?php
/**
 * Read-only Montjam product inventory, safely bounded to this vendor.
 */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) {fwrite(STDERR,"WP not initialized\n");exit(2);}
$producer=get_term_by('slug','montjam','pa_productor');
if (!$producer || is_wp_error($producer)) {fwrite(STDERR,"Montjam producer term missing\n");exit(3);}
$ids=get_posts(array(
 'post_type'=>'product','post_status'=>array('publish','private','draft','pending'),
 'posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true,
 'tax_query'=>array(array('taxonomy'=>'pa_productor','field'=>'term_id','terms'=>array((int)$producer->term_id))),
));
$rows=array();
foreach ($ids as $pid) {
 $p=wc_get_product((int)$pid);
 if (!$p) {continue;}
 $cat=wp_get_object_terms((int)$pid,'product_cat',array('fields'=>'slugs'));
 $prices=array();
 if ($p->is_type('variable')) {
  foreach ($p->get_children() as $vid) {
   $v=wc_get_product((int)$vid);
   if ($v && $v->get_status()==='publish') {$prices[]=array('id'=>$vid,'price'=>$v->get_regular_price('edit'),'weight'=>$v->get_attributes()['pa_tamano']??'');}
  }
 } else {
  $prices[]=array('id'=>(int)$pid,'regular'=>$p->get_regular_price('edit'),'sale'=>$p->get_sale_price('edit'),'price'=>$p->get_price('edit'));
 }
 $rows[]=array('id'=>(int)$pid,'title'=>$p->get_name(),'slug'=>$p->get_slug(),'status'=>$p->get_status(),
 'type'=>$p->get_type(),'category_slugs'=>is_wp_error($cat)?array():$cat,
 'vendor_author'=>(int)get_post_field('post_author',(int)$pid),'prices'=>$prices);
}
usort($rows,static function($a,$b){return $a['id']<=>$b['id'];});
echo "MONTJAM_INVENTORY ".wp_json_encode(array('count'=>count($rows),'products'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
