<?php
// Read-only audit of live Mentta sync prerequisites. Never exports credentials or product content.
if (!defined('ABSPATH') || !function_exists('wc_get_product')) { exit(2); }
$root=get_term_by('slug','mentta','product_cat');
if (!$root) {echo "ABORT: category not found\n";exit(3);}
$children=get_term_children((int)$root->term_id,'product_cat');
$child_ids=is_wp_error($children)?array():array_map('intval',$children);
$ids=get_objects_in_term((int)$root->term_id,'product_cat');
if (is_wp_error($ids)) {exit(4);}
$items=array();
foreach (array_unique(array_map('intval',$ids)) as $id) {
 if(get_post_type($id)!=='product')continue;
 $p=wc_get_product($id);if(!$p)continue;
 $terms=array_map('intval',$p->get_category_ids());
 $has_child=(bool)array_intersect($terms,$child_ids);
 $items[]=array('id'=>$id,'title'=>$p->get_name(),
  'date_modified_gmt'=>get_post_modified_time('Y-m-d H:i:s',true,$id),
  'status'=>$p->get_status(),'type'=>$p->get_type(),'sku_present'=>((string)$p->get_sku()!==''),
  'has_child'=>$has_child,'child_category_ids'=>array_values(array_intersect($terms,$child_ids)),
  'visible'=>$p->get_catalog_visibility(),'purchasable'=>$p->is_purchasable(),'in_stock'=>$p->is_in_stock());
}
usort($items,static function($a,$b){return $a['id']<=>$b['id'];});
$stats=array('with_child'=>0,'root_only'=>0,'with_child_sku_missing'=>array(),
 'root_only_sku_missing'=>array(),'with_child_published'=>0,'root_only_published'=>0);
foreach($items as $item) {
 $group=$item['has_child']?'with_child':'root_only';++$stats[$group];
 if(!$item['sku_present'])$stats[$group.'_sku_missing'][]=$item['id'];
 if($item['status']==='publish')++$stats[$group.'_published'];
}
$scheduled=array();
if(function_exists('_get_cron_array')) {
 foreach((array)_get_cron_array() as $when=>$hooks) {
  foreach((array)$hooks as $hook=>$events) {
   if(stripos((string)$hook,'mentta')!==false) {
     $scheduled[]=array('hook'=>$hook,'next_run_utc'=>gmdate('Y-m-d H:i:s',(int)$when),'event_count'=>count($events));
   }
  }
 }
}
$routes=array();
if(function_exists('rest_get_server')){
 foreach(array_keys(rest_get_server()->get_routes()) as $route){if(stripos($route,'mentta')!==false)$routes[]=$route;}
}
echo "MENTTA_REQUIREMENTS ".wp_json_encode(array('stats'=>$stats,'products'=>$items,
'scheduled_hooks'=>$scheduled,'rest_routes'=>$routes),
 JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
