<?php
/**
 * Fix only missing Mentta child-category taxonomy assignments for Montjam,
 * copying proven Hidalgo product mapping without altering visibility/prices.
 * Idempotent. Strictly 9 products previously added to the Mentta root.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"ABORT: run in WooCommerce WP-CLI\n"); exit(2);
}
$root=get_term_by('slug','mentta','product_cat');
$emb=get_term_by('slug','mentta-embutidos-y-curados','product_cat');
$ham=get_term_by('slug','mentta-jamones-paletas','product_cat');
if (!$root || !$emb || !$ham || (int)$emb->parent!==(int)$root->term_id || (int)$ham->parent!==(int)$root->term_id) {
 fwrite(STDERR,"ABORT: Mentta child taxonomy structure mismatch\n");exit(3);
}
$specs=array(
  14630=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'lomo-bellota-100-iberico-montjam','type'=>'simple'),
  14632=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'chorizo-iberico-bellota-montjam','type'=>'simple'),
  14634=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'salchichon-iberico-bellota-montjam','type'=>'simple'),
  14636=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'morcon-iberico-montjam','type'=>'simple'),
  16908=>array('child'=>$ham,'normal'=>'jamones-paletas','slug'=>'jamon-bellota-100-iberico-loncheado-montjam-90g','type'=>'simple'),
  16910=>array('child'=>$ham,'normal'=>'jamones-paletas','slug'=>'paleta-bellota-100-iberica-loncheada-montjam-90g','type'=>'simple'),
  16912=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'lomo-bellota-100-iberico-loncheado-montjam-90g','type'=>'simple'),
  16914=>array('child'=>$emb,'normal'=>'embutidos-y-curados','slug'=>'chorizo-cular-iberico-extra-loncheado-montjam-90g','type'=>'simple'),
  17129=>array('child'=>$ham,'normal'=>'jamones-paletas','slug'=>'jamon-de-bellota-100-iberico-montjam-mentta','type'=>'variable')
);
$before=array();
foreach($specs as $id=>$spec) {
 $p=wc_get_product($id);
 if (!$p || $p->get_status()!=='publish' || $p->get_type()!==$spec['type'] || $p->get_slug()!==$spec['slug']) {
  fwrite(STDERR,"ABORT: unexpected product identity/status #$id\n"); exit(4);
 }
 $cats=wp_get_object_terms($id,'product_cat',array('fields'=>'slugs'));
 $producer=wp_get_object_terms($id,'pa_productor',array('fields'=>'slugs'));
 if (is_wp_error($cats) || !in_array('mentta',$cats,true) || !in_array($spec['normal'],$cats,true)
  || is_wp_error($producer) || !in_array('montjam',$producer,true)) {
  fwrite(STDERR,"ABORT: unexpected categories or producer #$id\n");exit(5);
 }
 $before[$id]=array('categories'=>$p->get_category_ids(),'price'=>$p->get_price('edit'),
    'status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility(),'sku'=>$p->get_sku(),
    'image'=>$p->get_image_id(),'description'=>$p->get_description());
 if ($id===17129 && $p->get_catalog_visibility()!=='hidden') {
    fwrite(STDERR,"ABORT: exclusive Mentta copy must remain hidden\n");exit(6);
 }
 if ($id!==17129 && $p->get_catalog_visibility()!=='visible') {
    fwrite(STDERR,"ABORT: storefront product must remain visible\n");exit(7);
 }
}
$changed=array();$report=array();
try {
 foreach($specs as $id=>$spec) {
  $cid=(int)$spec['child']->term_id;
  if (!in_array($cid,$before[$id]['categories'],true)) {
   $res=wp_set_object_terms($id,array($cid),'product_cat',true);
   if (is_wp_error($res)) {throw new RuntimeException("Error assigning child term #$id");}
   $changed[]=$id;
  }
 }
 foreach($specs as $id=>$spec) {
  $p=wc_get_product($id);
  $cats=$p->get_category_ids();
  $child=(int)$spec['child']->term_id;
  $expected=array_unique(array_merge($before[$id]['categories'],array($child)));
  $actual=array_unique($cats);
  sort($expected,SORT_NUMERIC);sort($actual,SORT_NUMERIC);
  if ($actual!==$expected || $p->get_price('edit')!==$before[$id]['price']
      || $p->get_status()!==$before[$id]['status']
      || $p->get_catalog_visibility()!==$before[$id]['visibility']
      || $p->get_sku()!==$before[$id]['sku']
      || $p->get_image_id()!==$before[$id]['image']
      || $p->get_description()!==$before[$id]['description']) {
    throw new RuntimeException("Postchange verification failed #$id");
  }
  $report[]=array('id'=>$id,'name'=>$p->get_name(),'new_child_id'=>$child,
    'new_child_slug'=>$spec['child']->slug,'all_category_ids'=>$cats,
    'visibility'=>$p->get_catalog_visibility(),'price'=>$p->get_price('edit'),
    'changed'=>in_array($id,$changed,true));
 }
 $remaining=array();
 $all=get_objects_in_term((int)$root->term_id,'product_cat');
 foreach ((array)$all as $id) {
  if(get_post_type($id)!=='product')continue;
  $p=wc_get_product($id);
  if(!$p || $p->get_status()!=='publish')continue;
  $cats=$p->get_category_ids();
  if(!array_intersect($cats,array((int)$emb->term_id,(int)$ham->term_id,430,433))){
    $remaining[]=(int)$id;
  }
 }
 if ($remaining) {throw new RuntimeException('Some published Mentta products remain without child assignment: '.wp_json_encode($remaining));}
 foreach(array_keys($specs) as $id) {
    wc_delete_product_transients($id); clean_post_cache($id);
    if(function_exists('rocket_clean_post')) rocket_clean_post($id);
 }
 if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
 if(function_exists('rocket_clean_domain'))rocket_clean_domain();
 echo "MONTJAM_MENTTA_CHILDREN_OK ".wp_json_encode(array(
  'affected_product_count'=>count($report),'assigned_now'=>count($changed),'root_id'=>(int)$root->term_id,
  'products'=>$report,'root_only_published_after'=>$remaining),
 JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
 fwrite(STDERR,"MONTJAM_MENTTA_CHILDREN_FAILED ".$e->getMessage()."\n");
 foreach($changed as $id) {
    $p=wc_get_product($id);
    if($p){$p->set_category_ids($before[$id]['categories']);$p->save();wc_delete_product_transients($id);}
 }
 exit(20);
}
