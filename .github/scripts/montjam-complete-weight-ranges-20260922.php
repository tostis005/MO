<?php
/**
 * Complete all Montjam ham/paleta weight variations from supplier table supplied 2026-09-22.
 * Creates the 18 missing published variations and makes parent pa_tamano options match the table.
 * Does not alter product copy, images, preparation add-ons, producer assignment, or unrelated attributes.
 */
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT: WordPress not loaded\n"); exit(2); }
if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) { fwrite(STDERR,"ABORT: WooCommerce unavailable\n"); exit(3); }

$specs = [
  14287 => [
    'key'=>'jamon_negra_dop','prefix'=>'MONTJAM-JDOP',
    'prices'=>[
      '6-65-kg'=>'311.85','65-7-kg'=>'336.798','7-75-kg'=>'361.746','75-8-kg'=>'386.694',
      '8-85-kg'=>'411.642','85-9-kg'=>'436.59','9-95-kg'=>'461.538',
    ],
  ],
  14264 => [
    'key'=>'jamon_negra','prefix'=>'MONTJAM-JN',
    'prices'=>[
      '6-65-kg'=>'227.91','65-7-kg'=>'246.14','7-75-kg'=>'310.07','75-8-kg'=>'331.45',
      '8-85-kg'=>'352.84','85-9-kg'=>'374.22','9-95-kg'=>'395.60',
    ],
  ],
  14271 => [
    'key'=>'jamon_roja','prefix'=>'MONTJAM-JR',
    'prices'=>[
      '7-75-kg'=>'284.229','75-8-kg'=>'303.831','8-85-kg'=>'323.433','85-9-kg'=>'343.035','9-95-kg'=>'362.637',
    ],
  ],
  14294 => [
    'key'=>'jamon_verde','prefix'=>'MONTJAM-JV',
    'prices'=>[
      '75-8-kg'=>'220.97','8-85-kg'=>'235.22','85-9-kg'=>'249.48','9-95-kg'=>'263.74',
    ],
  ],
  14301 => [
    'key'=>'paleta_negra_dop','prefix'=>'MONTJAM-PDOP',
    'prices'=>[
      '4-45-kg'=>'141.372','45-5-kg'=>'158.004','5-55-kg'=>'174.636','55-6-kg'=>'191.268',
    ],
  ],
  14275 => [
    'key'=>'paleta_negra','prefix'=>'MONTJAM-PN',
    'prices'=>[
      '4-45-kg'=>'121.176','45-5-kg'=>'135.432','5-55-kg'=>'149.688','55-6-kg'=>'163.944',
    ],
  ],
  14305 => [
    'key'=>'paleta_verde','prefix'=>'MONTJAM-PV',
    'prices'=>[
      '45-5-kg'=>'95.931','5-55-kg'=>'106.029','55-6-kg'=>'116.127',
    ],
  ],
];

$suffixes = [
  '4-45-kg'=>'400-450','45-5-kg'=>'450-500','5-55-kg'=>'500-550','55-6-kg'=>'550-600',
  '6-65-kg'=>'600-650','65-7-kg'=>'650-700','7-75-kg'=>'700-750','75-8-kg'=>'750-800',
  '8-85-kg'=>'800-850','85-9-kg'=>'850-900','9-95-kg'=>'900-950',
];

function mjwr_parent_snapshot($id){
  return [
    'title'=>(string)get_post_field('post_title',$id),
    'slug'=>(string)get_post_field('post_name',$id),
    'content'=>(string)get_post_field('post_content',$id),
    'excerpt'=>(string)get_post_field('post_excerpt',$id),
    'thumbnail'=>(int)get_post_thumbnail_id($id),
    'gallery'=>(string)get_post_meta($id,'_product_image_gallery',true),
    'producer'=>wp_get_object_terms($id,'pa_productor',['fields'=>'slugs']),
    'preparation'=>wp_get_object_terms($id,'pa_preparacion',['fields'=>'slugs']),
  ];
}

function mjwr_children_by_size($product){
  $out=[];
  foreach($product->get_children() as $vid){
    $v=wc_get_product((int)$vid);
    if(!$v || !$v->is_type('variation')) continue;
    $attrs=$v->get_attributes();
    $size=(string)($attrs['pa_tamano']??'');
    if($size!=='') $out[$size]=$v;
  }
  return $out;
}

$before=[];
$created=[];
$updated=[];
$term_ids=[];

// Preflight all parent identities, terms and SKUs before any writes.
foreach($specs as $pid=>$spec){
  $p=wc_get_product($pid);
  if(!$p || !$p->is_type('variable')) {
    fwrite(STDERR,"ABORT: product {$pid} missing/not variable\n"); exit(4);
  }
  $producer=wp_get_object_terms($pid,'pa_productor',['fields'=>'slugs']);
  if(is_wp_error($producer) || !in_array('montjam',$producer,true)){
    fwrite(STDERR,"ABORT: product {$pid} not Montjam\n"); exit(5);
  }
  $before[$pid]=mjwr_parent_snapshot($pid);
  foreach(array_keys($spec['prices']) as $size){
    if(!isset($suffixes[$size])) { fwrite(STDERR,"ABORT: suffix missing for {$size}\n"); exit(6); }
    $t=get_term_by('slug',$size,'pa_tamano');
    if(!$t || is_wp_error($t)){ fwrite(STDERR,"ABORT: pa_tamano term missing: {$size}\n"); exit(7); }
    $term_ids[$size]=(int)$t->term_id;
  }
}

// Write all expected variations; existing ones keep their IDs.
foreach($specs as $pid=>$spec){
  $p=wc_get_product($pid);
  $by_size=mjwr_children_by_size($p);
  $expected_sizes=array_keys($spec['prices']);

  foreach($expected_sizes as $index=>$size){
    $price=$spec['prices'][$size];
    $sku=$spec['prefix'].'-'.$suffixes[$size];

    if(isset($by_size[$size])){
      $v=$by_size[$size];
      $is_new=false;
    } else {
      $v=new WC_Product_Variation();
      $v->set_parent_id($pid);
      $v->set_attributes(['pa_tamano'=>$size]);
      $is_new=true;
    }

    $existing_sku_owner=wc_get_product_id_by_sku($sku);
    if($existing_sku_owner && (int)$existing_sku_owner !== (int)$v->get_id()){
      fwrite(STDERR,"ABORT: SKU {$sku} already belongs to {$existing_sku_owner}\n"); exit(8);
    }

    $v->set_status('publish');
    $v->set_attributes(['pa_tamano'=>$size]);
    $v->set_sku($sku);
    $v->set_regular_price($price);
    $v->set_sale_price('');
    $v->set_manage_stock(false);
    $v->set_stock_status('instock');
    $v->set_menu_order((int)$index);
    $vid=(int)$v->save();
    if(!$vid){ fwrite(STDERR,"ABORT: failed saving {$pid}/{$size}\n"); exit(9); }

    $row=['product_id'=>$pid,'variation_id'=>$vid,'size'=>$size,'price'=>$price,'sku'=>$sku];
    if($is_new) $created[]=$row; else $updated[]=$row;
  }

  // Expose exactly the supplier-table sizes in the parent selector, in weight order.
  $ordered_term_ids=array_map(fn($s)=>(int)$term_ids[$s],$expected_sizes);
  wp_set_object_terms($pid,$ordered_term_ids,'pa_tamano',false);

  $attrs=$p->get_attributes();
  if(!isset($attrs['pa_tamano']) || !($attrs['pa_tamano'] instanceof WC_Product_Attribute)){
    fwrite(STDERR,"ABORT: parent {$pid} lacks pa_tamano product attribute\n"); exit(10);
  }
  $attrs['pa_tamano']->set_options($ordered_term_ids);
  $attrs['pa_tamano']->set_visible(true);
  $attrs['pa_tamano']->set_variation(true);
  $p->set_attributes($attrs);

  $defaults=$p->get_default_attributes();
  if(isset($defaults['pa_tamano']) && !in_array($defaults['pa_tamano'],$expected_sizes,true)){
    $defaults['pa_tamano']=$expected_sizes[0];
    $p->set_default_attributes($defaults);
  }
  $p->save();

  WC_Product_Variable::sync($pid,true);
  wc_delete_product_transients($pid);
  clean_post_cache($pid);
}
wp_cache_flush();

// Verify all 34 expected variations and all requested prices.
$verification=[];
$total_published=0;
foreach($specs as $pid=>$spec){
  $p=wc_get_product($pid);
  $rows=[];
  foreach($p->get_children() as $vid){
    $v=wc_get_product((int)$vid);
    if(!$v || !$v->is_type('variation') || $v->get_status()!=='publish') continue;
    $attrs=$v->get_attributes();
    $size=(string)($attrs['pa_tamano']??'');
    $rows[$size]=[
      'id'=>(int)$vid,
      'price'=>(string)$v->get_price('edit'),
      'regular_price'=>(string)$v->get_regular_price('edit'),
      'sku'=>(string)$v->get_sku(),
      'stock_status'=>(string)$v->get_stock_status(),
    ];
  }
  $expected_sizes=array_keys($spec['prices']);
  if(array_keys($rows)!==$expected_sizes){
    fwrite(STDERR,"ABORT: published sizes/order mismatch for {$pid}: ".wp_json_encode(array_keys($rows))."\n"); exit(11);
  }
  foreach($expected_sizes as $size){
    $expected_price=(float)$spec['prices'][$size];
    if(abs((float)$rows[$size]['regular_price']-$expected_price)>0.0005 || abs((float)$rows[$size]['price']-$expected_price)>0.0005){
      fwrite(STDERR,"ABORT: price mismatch {$pid}/{$size}\n"); exit(12);
    }
    if($rows[$size]['stock_status']!=='instock'){
      fwrite(STDERR,"ABORT: stock mismatch {$pid}/{$size}\n"); exit(13);
    }
  }
  $parent_terms=wp_get_object_terms($pid,'pa_tamano',['fields'=>'slugs']);
  if(is_wp_error($parent_terms) || array_values($parent_terms)!==$expected_sizes){
    // Taxonomy retrieval order can differ; compare as sets too, while product attribute below preserves display order.
    $a=$parent_terms; $b=$expected_sizes; if(is_array($a)){sort($a);} sort($b);
    if(is_wp_error($parent_terms) || $a!==$b){
      fwrite(STDERR,"ABORT: parent terms mismatch {$pid}\n"); exit(14);
    }
  }

  // Ensure product copy/images/producer/preparation were not changed.
  $after=mjwr_parent_snapshot($pid);
  foreach(['title','slug','content','excerpt','thumbnail','gallery','producer','preparation'] as $field){
    if($after[$field]!==$before[$pid][$field]){
      fwrite(STDERR,"ABORT: unrelated parent field changed {$pid}/{$field}\n"); exit(15);
    }
  }

  $attrs=$p->get_attributes();
  $option_ids=isset($attrs['pa_tamano']) && $attrs['pa_tamano'] instanceof WC_Product_Attribute
    ? array_map('intval',$attrs['pa_tamano']->get_options()) : [];
  $expected_ids=array_map(fn($s)=>(int)$term_ids[$s],$expected_sizes);
  if($option_ids!==$expected_ids){
    fwrite(STDERR,"ABORT: selector order mismatch {$pid}\n"); exit(16);
  }

  $total_published+=count($rows);
  $verification[]=[
    'product_id'=>$pid,
    'title'=>$p->get_name(),
    'published_sizes'=>$expected_sizes,
    'variation_count'=>count($rows),
    'prices'=>array_map(fn($s)=>$spec['prices'][$s],$expected_sizes),
  ];
}

if(count($created)!==18 || $total_published!==34){
  fwrite(STDERR,"ABORT: expected 18 new and 34 total published; got ".count($created)." new / {$total_published} total\n");
  exit(17);
}

echo "MONTJAM_WEIGHT_RANGES_FIXED: ".wp_json_encode([
  'created_count'=>count($created),
  'created'=>$created,
  'updated_existing_count'=>count($updated),
  'total_published'=>$total_published,
  'products'=>$verification,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
