<?php
/**
 * Montjam 2026-10-09: update ONLY 26 published weight variations on SIX whole hams/shoulders
 * from current 10% markup to 25% markup on the same underlying cost.
 * Source prices checked against production 2026-10-09 inventory / 2026-09-24 baseline.
 * Excluded without exception: black-label non-DOP whole ham #14264 and its Mentta copy #17129.
 *
 * Price rule: existing price / 1.10 * 1.25. Six decimal digits preserve supplier precision.
 * No changes to product categories, Mentta, publication state, variations, stock or content.
 */
if ( ! defined('ABSPATH') || ! defined('WP_CLI') || ! WP_CLI || ! class_exists('WC_Product_Variable') ) {
    fwrite(STDERR,"ABORT: WooCommerce via WP-CLI required\n"); exit(2);
}
$baseline = array(
  14271 => array( // Jamón bellota 50% ibérico, brida roja
    '7-75-kg'=>'289.4925','75-8-kg'=>'309.4575','8-85-kg'=>'329.4225','85-9-kg'=>'349.3875','9-95-kg'=>'369.3525'
  ),
  14275 => array( // Paleta bellota 100% ibérica, brida negra (sin DOP)
    '4-45-kg'=>'123.42','45-5-kg'=>'137.94','5-55-kg'=>'152.46','55-6-kg'=>'166.98'
  ),
  14287 => array( // Jamón bellota 100% ibérico D.O.P. Jabugo, brida negra (INCLUIDO)
    '6-65-kg'=>'317.625','65-7-kg'=>'343.035','7-75-kg'=>'368.445','75-8-kg'=>'368.445',
    '8-85-kg'=>'419.265','85-9-kg'=>'444.675','9-95-kg'=>'470.085'
  ),
  14294 => array( // Jamón cebo de campo, brida verde
    '75-8-kg'=>'225.06','8-85-kg'=>'239.58','85-9-kg'=>'254.1','9-95-kg'=>'268.62'
  ),
  14301 => array( // Paleta bellota 100% ibérica D.O.P. Jabugo, brida negra
    '4-45-kg'=>'143.99','45-5-kg'=>'160.93','5-55-kg'=>'177.87','55-6-kg'=>'194.81'
  ),
  14305 => array( // Paleta cebo de campo, brida verde. 4-4.5 legacy size not published.
    '5-55-kg'=>'107.9925','55-6-kg'=>'118.2775'
  )
);
$expected_names = array(
  14271=>'Jamón de bellota ibérico 50% raza ibérica Montjam (brida roja)',
  14275=>'Paleta de bellota 100% ibérica Montjam (brida negra)',
  14287=>'Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam (brida negra)',
  14294=>'Jamón de cebo de campo ibérico 50% raza ibérica Montjam (brida verde)',
  14301=>'Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam (brida negra)',
  14305=>'Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)'
);
$excluded = array(14264,17129);
$vendor = get_user_by('login','montjam');
if (!$vendor) {fwrite(STDERR,"ABORT: Montjam vendor unavailable\n");exit(3);}
function mj26_variation_snapshot($v) {
  return array(
   'sku'=>(string)$v->get_sku(),
   'status'=>(string)$v->get_status(),
   'attrs'=>$v->get_attributes(),
   'stock'=>(string)$v->get_stock_status(),
   'manage_stock'=>(bool)$v->get_manage_stock(),
   'quantity'=>$v->get_stock_quantity(),
   'image'=>(int)$v->get_image_id(),
   'description'=>(string)$v->get_description(),
   'regular'=>(string)$v->get_regular_price('edit'),
   'price'=>(string)$v->get_price('edit'),
   'sale'=>(string)$v->get_sale_price('edit')
  );
}
function mj26_target($old) {
  return rtrim(rtrim(sprintf('%.6F',round((float)$old * 125 / 110,6,PHP_ROUND_HALF_UP)),'0'),'.');
}
$excluded_before=array();
foreach($excluded as $id) {
  $p=wc_get_product($id);
  if (!$p || !$p->is_type('variable') || $p->get_status()!=='publish') {
    fwrite(STDERR,"ABORT: protected source/Mentta copy unexpectedly missing #$id\n");exit(4);
  }
  $rows=array();
  foreach($p->get_children() as $vid) {
    $v=wc_get_product($vid);
    if ($v) $rows[$vid]=mj26_variation_snapshot($v);
  }
  $excluded_before[$id]=array('categories'=>$p->get_category_ids(),
    'visibility'=>$p->get_catalog_visibility(),'status'=>$p->get_status(),'variations'=>$rows);
}
$plans=array();$parents=array();
foreach($baseline as $id=>$sizes) {
  if(in_array($id,$excluded,true)) {fwrite(STDERR,"ABORT: excluded id in plan\n");exit(5);}
  $p=wc_get_product($id);
  $producer=wp_get_object_terms($id,'pa_productor',array('fields'=>'slugs'));
  if (!$p || !$p->is_type('variable') || $p->get_status()!=='publish'
      || $p->get_name()!==$expected_names[$id]
      || (int)get_post_field('post_author',$id)!==(int)$vendor->ID
      || is_wp_error($producer) || !in_array('montjam',$producer,true)) {
    fwrite(STDERR,"ABORT: unexpected Montjam product identity #$id\n");exit(6);
  }
  $parents[$id]=array('category_ids'=>$p->get_category_ids(),'visibility'=>$p->get_catalog_visibility(),
    'name'=>$p->get_name(),'status'=>$p->get_status(),'sku'=>$p->get_sku(),'image'=>$p->get_image_id());
  $found=array();
  foreach($p->get_children() as $vid) {
    $v=wc_get_product($vid);
    if (!$v || !$v->is_type('variation') || (int)$v->get_parent_id()!==$id) {
      fwrite(STDERR,"ABORT: unexpected product variation #$vid\n");exit(7);
    }
    if ($v->get_status()!=='publish')continue; // leave disabled/legacy weight variations untouched
    $size=(string)($v->get_attributes()['pa_tamano']??'');
    if (!$size || isset($found[$size]) || !array_key_exists($size,$sizes)) {
      fwrite(STDERR,"ABORT: unexpected/duplicate published weight $size on #$id\n");exit(8);
    }
    if ($v->get_sale_price('edit')!=='') {
      fwrite(STDERR,"ABORT: unexpected sale price on variation #$vid\n");exit(9);
    }
    $target=mj26_target($sizes[$size]);
    $current=(string)$v->get_regular_price('edit');
    $active=(string)$v->get_price('edit');
    if ((abs((float)$current-(float)$sizes[$size])>0.000001
        && abs((float)$current-(float)$target)>0.000001)
        || abs((float)$active-(float)$current)>0.000001) {
      fwrite(STDERR,"ABORT: unexpected current price #$vid weight $size (current=$current, expected={$sizes[$size]} or $target)\n");exit(10);
    }
    $found[$size]=(int)$vid;
    $plans[]=array('parent'=>$id,'variation'=>$vid,'size'=>$size,'baseline'=>$sizes[$size],
       'before'=>mj26_variation_snapshot($v),'target'=>$target);
  }
  $wanted=array_keys($sizes);$actual=array_keys($found);sort($wanted);sort($actual);
  if($wanted!==$actual) {
    fwrite(STDERR,"ABORT: published size mismatch on #$id\n");exit(11);
  }
}
if(count($plans)!==26 || count($parents)!==6) {
 fwrite(STDERR,"ABORT: expected exactly 26 weight prices for 6 whole products\n");exit(12);
}
echo 'MONTJAM_WHOLE_25_PREFLIGHT '.wp_json_encode(array(
  'parents'=>array_keys($parents),'weight_count'=>count($plans),'excluded'=>$excluded,
  'prices'=>array_map(static function($r){return array('id'=>$r['variation'],'weight'=>$r['size'],
     'old'=>$r['before']['regular'],'new'=>$r['target']);},$plans)
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$modified=array();
try {
  foreach($plans as $row) {
    $v=wc_get_product($row['variation']);
    if(abs((float)$row['before']['regular']-(float)$row['target'])<0.000001) continue;
    $v->set_regular_price($row['target']);
    $v->save();
    $modified[]=$row; // idempotent: only track actual writes for rollback
  }
  foreach(array_keys($parents) as $id) {
    WC_Product_Variable::sync($id,true);
    wc_delete_product_transients($id);
    clean_post_cache($id);
  }
  $verified=array();
  foreach($plans as $row) {
    $v=wc_get_product($row['variation']);
    $snapshot=mj26_variation_snapshot($v);
    $unrelated=$snapshot;$before=$row['before'];
    unset($unrelated['regular'],$unrelated['price']);unset($before['regular'],$before['price']);
    if($unrelated!==$before
       || abs((float)$snapshot['regular']-(float)$row['target'])>0.000001
       || abs((float)$snapshot['price']-(float)$row['target'])>0.000001) {
      throw new RuntimeException("Post-change variation verification mismatch #{$row['variation']}");
    }
    $verified[]=array('parent'=>$row['parent'],'variation'=>$row['variation'],
      'size'=>$row['size'],'before'=>$row['before']['regular'],'after'=>$snapshot['regular']);
  }
  foreach($parents as $id=>$before) {
    $p=wc_get_product($id);
    $snapshot=array('category_ids'=>$p->get_category_ids(),'visibility'=>$p->get_catalog_visibility(),
      'name'=>$p->get_name(),'status'=>$p->get_status(),'sku'=>$p->get_sku(),'image'=>$p->get_image_id());
    $a=$snapshot['category_ids'];$b=$before['category_ids'];sort($a);sort($b);
    $snapshot['category_ids']=$a;$before['category_ids']=$b;
    if($snapshot!==$before) throw new RuntimeException("Parent product unexpectedly changed #$id");
  }
  foreach($excluded_before as $id=>$before) {
    $p=wc_get_product($id);
    $cats=$p->get_category_ids();$expected=$before['categories'];
    sort($cats);sort($expected);
    if($cats!==$expected || $p->get_catalog_visibility()!==$before['visibility']
      || $p->get_status()!==$before['status']) {
      throw new RuntimeException("Excluded product metadata changed #$id");
    }
    $after=array();
    foreach($p->get_children() as $vid) {
      $v=wc_get_product($vid);
      if($v) $after[$vid]=mj26_variation_snapshot($v);
    }
    if($after!==$before['variations']) throw new RuntimeException("Excluded product variation price changed #$id");
  }
  if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
  if(function_exists('rocket_clean_domain')) rocket_clean_domain();
  echo 'MONTJAM_WHOLE_25_OK '.wp_json_encode(array(
    'products_updated'=>count($parents),'weights_verified'=>count($verified),
    'prices_changed'=>count($modified),'excluded_untouched'=>$excluded,'variations'=>$verified
  ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch(Throwable $e) {
  fwrite(STDERR,'MONTJAM_WHOLE_25_ERROR '.$e->getMessage()."\n");
  foreach($modified as $row) {
    try {
      $v=wc_get_product($row['variation']);
      if(!$v)continue;
      $v->set_regular_price($row['before']['regular']);
      $v->save();
    } catch(Throwable $inner) {
      fwrite(STDERR,"ROLLBACK_FAILED #{$row['variation']}: ".$inner->getMessage()."\n");
    }
  }
  foreach(array_keys($parents) as $id) {
    WC_Product_Variable::sync($id,true);wc_delete_product_transients($id);
  }
  exit(20);
}
