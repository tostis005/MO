<?php
/**
 * Montjam: switch 10% supplier-cost markup to 25% for four cured meats + four sliced products.
 * Keep storefront visibility and original product categories; APPEND Mentta only.
 * Explicit whitelisted IDs/slugs and expected old prices, avoiding whole ham/paleta.
 * Idempotent: accepts either audited baseline or already updated target price.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WC_Product_Simple')) {
 fwrite(STDERR,"ABORT: WP-CLI and WooCommerce required\n");exit(2);
}
$specs=array(
 14630=>array('slug'=>'lomo-bellota-100-iberico-montjam','original'=>'68.75','cat'=>'embutidos-y-curados'),
 14632=>array('slug'=>'chorizo-iberico-bellota-montjam','original'=>'24.75','cat'=>'embutidos-y-curados'),
 14634=>array('slug'=>'salchichon-iberico-bellota-montjam','original'=>'24.75','cat'=>'embutidos-y-curados'),
 14636=>array('slug'=>'morcon-iberico-montjam','original'=>'33.00','cat'=>'embutidos-y-curados'),
 16908=>array('slug'=>'jamon-bellota-100-iberico-loncheado-montjam-90g','original'=>'15.15','cat'=>'jamones-paletas'),
 16910=>array('slug'=>'paleta-bellota-100-iberica-loncheada-montjam-90g','original'=>'11.00','cat'=>'jamones-paletas'),
 16912=>array('slug'=>'lomo-bellota-100-iberico-loncheado-montjam-90g','original'=>'8.25','cat'=>'embutidos-y-curados'),
 16914=>array('slug'=>'chorizo-cular-iberico-extra-loncheado-montjam-90g','original'=>'3.45','cat'=>'embutidos-y-curados'),
);
$mentta=get_term_by('slug','mentta','product_cat');
$producer=get_term_by('slug','montjam','pa_productor');
if (!$mentta || is_wp_error($mentta) || !$producer || is_wp_error($producer)) {
 fwrite(STDERR,"ABORT: Montjam producer or Mentta category missing\n");exit(3);
}
$expected_ids=array_map('intval',array_keys($specs));
sort($expected_ids,SORT_NUMERIC);
$vendor=get_user_by('login','montjam');
if (!$vendor) {fwrite(STDERR,"ABORT: Montjam vendor missing\n");exit(4);}
// Ensure scope is complete: no additional Montjam simple products, and no pieces are touched.
$all_montjam=get_posts(array(
 'post_type'=>'product','post_status'=>array('publish','private','draft','pending'),
 'posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true,
 'tax_query'=>array(array('taxonomy'=>'pa_productor','field'=>'term_id','terms'=>array((int)$producer->term_id)))
));
$all_simple=array();
$excluded=array();
$excluded_before=array();
foreach ($all_montjam as $pid) {
 $p=wc_get_product((int)$pid);
 if (!$p) {fwrite(STDERR,"ABORT: Montjam product disappeared #$pid\n");exit(5);}
 if ($p->is_type('simple')) {$all_simple[]=(int)$pid;}
 else {
   if (!$p->is_type('variable')) {fwrite(STDERR,"ABORT: unforeseen Montjam product type #$pid\n");exit(6);}
   $excluded[]=(int)$pid;
   $excluded_before[(int)$pid]=array(
      'cats'=>wp_get_object_terms((int)$pid,'product_cat',array('fields'=>'ids')),
      'variation_prices'=>array(),
   );
   foreach ($p->get_children() as $vid) {
     $v=wc_get_product($vid);
     if ($v) {$excluded_before[(int)$pid]['variation_prices'][(int)$vid]=(string)$v->get_regular_price('edit');}
   }
 }
}
sort($all_simple,SORT_NUMERIC);
if ($all_simple!==$expected_ids || count($expected_ids)!==8) {
 fwrite(STDERR,"ABORT: published/private/draft Montjam simple product IDs differ. Expected "
  .wp_json_encode($expected_ids)." got ".wp_json_encode($all_simple)."\n");exit(7);
}
if (count($excluded)!==8) {
 fwrite(STDERR,"ABORT: expected 7 whole jamon/paleta products + 1 Mentta-only variable product; found ".count($excluded)."\n");exit(8);
}
$planned=array();
$before=array();
foreach ($specs as $pid=>$row) {
 $p=wc_get_product((int)$pid);
 $term_slugs=wp_get_object_terms((int)$pid,'pa_productor',array('fields'=>'slugs'));
 $cats=wp_get_object_terms((int)$pid,'product_cat',array('fields'=>'slugs'));
 if (!$p || !$p->is_type('simple') || $p->get_status()!=='publish'
   || $p->get_slug()!==$row['slug']
   || is_wp_error($term_slugs) || !in_array('montjam',$term_slugs,true)
   || is_wp_error($cats) || !in_array($row['cat'],$cats,true)
   || (int)get_post_field('post_author',$pid)!==(int)$vendor->ID
   || $p->get_sale_price('edit')!=='') {
   fwrite(STDERR,"ABORT: product identity/category/vendor/sale mismatch #$pid\n");exit(9);
 }
 // 25% on original cost, i.e. current price / 1.10 * 1.25.
 // Three decimals retain source precision; storefront displays its configured currency decimals.
 $target=number_format(round(((float)$row['original']) * 1.25 / 1.10,3,PHP_ROUND_HALF_UP),3,'.','');
 $regular=(string)$p->get_regular_price('edit');
 $price=(string)$p->get_price('edit');
 $is_baseline=abs((float)$regular-(float)$row['original'])<0.00001;
 $is_target=abs((float)$regular-(float)$target)<0.00001;
 if ((!$is_baseline && !$is_target) || abs((float)$regular-(float)$price)>0.00001) {
   fwrite(STDERR,"ABORT: unexpected price on #$pid: regular=$regular current=$price expected=".$row['original']." or $target\n");exit(10);
 }
 $before[$pid]=array('price'=>$regular,'categories'=>wc_get_product($pid)->get_category_ids(),
                    'status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility());
 $planned[$pid]=array('id'=>$pid,'title'=>$p->get_name(),'old_price'=>$row['original'],'current_price'=>$regular,
                      'new_price'=>$target,'categories_before'=>$cats,'already_updated'=>$is_target);
}
echo "MONTJAM_MENTTA_PREFLIGHT ".wp_json_encode(array('target_count'=>count($planned),'excluded_variable_ids'=>$excluded,
 'plans'=>array_values($planned)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$updated=array();
try {
 foreach ($planned as $pid=>$plan) {
  $p=wc_get_product($pid);
  $p->set_regular_price($plan['new_price']);
  $p->set_sale_price('');
  if (!$p->save()) {throw new RuntimeException('Product save failed #'.$pid);}
  $updated[]=$pid;
  $term_result=wp_set_object_terms($pid,array((int)$mentta->term_id),'product_cat',true);
  if (is_wp_error($term_result)) {throw new RuntimeException('Mentta category assignment failed #'.$pid);}
  update_post_meta($pid,'_emdo_montjam_mentta_margin_20261008','25_percent_cost_markup');
  $updated[]=$pid;
 }
 $result=array();
 foreach ($planned as $pid=>$plan) {
  $p=wc_get_product($pid);
  $cats=wp_get_object_terms($pid,'product_cat',array('fields'=>'slugs'));
  if (!$p || $p->get_status()!==$before[$pid]['status']
      || $p->get_catalog_visibility()!==$before[$pid]['visibility']
      || $p->get_sale_price('edit')!==''
      || abs((float)$p->get_regular_price('edit')-(float)$plan['new_price'])>0.00001
      || abs((float)$p->get_price('edit')-(float)$plan['new_price'])>0.00001
      || is_wp_error($cats)
      || !in_array('mentta',$cats,true)
      || !in_array($specs[$pid]['cat'],$cats,true)
      || get_post_meta($pid,'_emdo_montjam_mentta_margin_20261008',true)!=='25_percent_cost_markup') {
   throw new RuntimeException('Verification failed for product #'.$pid);
  }
  $result[]=array('id'=>$pid,'title'=>$plan['title'],'price_before'=>$plan['current_price'],
                  'price_after'=>$p->get_regular_price('edit'),'categories'=>$cats,
                  'catalog_visibility'=>$p->get_catalog_visibility());
 }
 foreach ($excluded_before as $pid=>$snapshot) {
  $p=wc_get_product($pid);
  $categories=wp_get_object_terms($pid,'product_cat',array('fields'=>'ids'));
  if (!$p || is_wp_error($categories)) {throw new RuntimeException('Excluded product inaccessible #'.$pid);}
  $a=array_map('intval',$snapshot['cats']);$b=array_map('intval',$categories);sort($a);sort($b);
  if ($a!==$b) {throw new RuntimeException('Excluded product category changed #'.$pid);}
  foreach($snapshot['variation_prices'] as $vid=>$price) {
    $v=wc_get_product($vid);
    if (!$v || (string)$v->get_regular_price('edit')!==$price) {throw new RuntimeException('Excluded variation price changed #'.$vid);}
  }
 }
 foreach($expected_ids as $pid) {
  wc_delete_product_transients($pid);
  clean_post_cache($pid);
  if (function_exists('rocket_clean_post')) {rocket_clean_post($pid);}
 }
 if (class_exists('WC_Cache_Helper')) {WC_Cache_Helper::get_transient_version('product',true);}
 echo "MONTJAM_MENTTA_25_PERCENT_OK ".wp_json_encode(array('count'=>count($result),'updated'=>$result,
   'excluded_variable_ids'=>$excluded,'mentta_term_id'=>(int)$mentta->term_id),
   JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
 fwrite(STDERR,"MONTJAM_MENTTA_UPDATE_FAILED ".$e->getMessage()."\n");
 foreach($updated as $pid) {
  try {
   $p=wc_get_product($pid);
   if ($p) {
    $p->set_regular_price($before[$pid]['price']);
    $p->save();
    $p->set_category_ids($before[$pid]['categories']);
    $p->save();
    delete_post_meta($pid,'_emdo_montjam_mentta_margin_20261008');
    wc_delete_product_transients($pid);
   }
  } catch (Throwable $rollback_error) {fwrite(STDERR,"ROLLBACK_FAILED #$pid ".$rollback_error->getMessage()."\n");}
 }
 exit(20);
}
