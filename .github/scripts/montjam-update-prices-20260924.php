<?php
/**
 * One-off Montjam price update from supplier table supplied 2026-09-24.
 * Only variation prices are changed. No titles, descriptions, images, stock,
 * SKUs, attributes, categories, producer or format/add-on configuration.
 */
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT: WordPress not loaded\n"); exit(2); }
if (!function_exists('wc_get_product')) { fwrite(STDERR,"ABORT: WooCommerce unavailable\n"); exit(3); }

$prices = [
  14287 => [ // Jamón brida negra DOP
    '6-65-kg'=>'317.625',
    '65-7-kg'=>'343.035',
    '7-75-kg'=>'368.445',
    '75-8-kg'=>'368.445',
    '8-85-kg'=>'419.265',
    '85-9-kg'=>'444.675',
    '9-95-kg'=>'470.085',
  ],
  14264 => [ // Jamón brida negra
    '6-65-kg'=>'245.78125',
    '65-7-kg'=>'265.44375',
    '7-75-kg'=>'315.81',
    '75-8-kg'=>'337.59',
    '8-85-kg'=>'359.37',
    '85-9-kg'=>'381.15',
    '9-95-kg'=>'402.93',
  ],
  14271 => [ // Jamón brida roja
    '7-75-kg'=>'289.4925',
    '75-8-kg'=>'309.4575',
    '8-85-kg'=>'329.4225',
    '85-9-kg'=>'349.3875',
    '9-95-kg'=>'369.3525',
  ],
  14294 => [ // Jamón brida verde
    '75-8-kg'=>'225.06',
    '8-85-kg'=>'239.58',
    '85-9-kg'=>'254.1',
    '9-95-kg'=>'268.62',
  ],
  14301 => [ // Paleta brida negra DOP
    '4-45-kg'=>'143.99',
    '45-5-kg'=>'160.93',
    '5-55-kg'=>'177.87',
    '55-6-kg'=>'194.81',
  ],
  14275 => [ // Paleta brida negra
    '4-45-kg'=>'123.42',
    '45-5-kg'=>'137.94',
    '5-55-kg'=>'152.46',
    '55-6-kg'=>'166.98',
  ],
  14305 => [ // Paleta brida verde
    '45-5-kg'=>'97.7075',
    '5-55-kg'=>'107.9925',
    '55-6-kg'=>'118.2775',
  ],
];

function mj_price_snapshot($v) {
  return [
    'id'=>(int)$v->get_id(),
    'parent_id'=>(int)$v->get_parent_id(),
    'status'=>(string)$v->get_status(),
    'sku'=>(string)$v->get_sku(),
    'attributes'=>$v->get_attributes(),
    'stock_status'=>(string)$v->get_stock_status(),
    'manage_stock'=>(bool)$v->get_manage_stock(),
    'stock_quantity'=>$v->get_stock_quantity(),
    'image_id'=>(int)$v->get_image_id(),
    'description'=>(string)$v->get_description(),
  ];
}

$before=[];
$changes=[];
$total_expected=0;

// Preflight: verify every exact expected variation exists and is unique.
foreach ($prices as $product_id=>$by_size) {
  $p=wc_get_product($product_id);
  if (!$p || !$p->is_type('variable')) {
    fwrite(STDERR,"ABORT: product {$product_id} missing/not variable\n"); exit(4);
  }
  $producer=wp_get_object_terms($product_id,'pa_productor',['fields'=>'slugs']);
  if (is_wp_error($producer) || !in_array('montjam',$producer,true)) {
    fwrite(STDERR,"ABORT: product {$product_id} is not Montjam\n"); exit(5);
  }

  $found=[];
  foreach ($p->get_children() as $vid) {
    $v=wc_get_product((int)$vid);
    if (!$v || !$v->is_type('variation') || $v->get_status()!=='publish') continue;
    $attrs=$v->get_attributes();
    $size=(string)($attrs['pa_tamano']??'');
    if ($size!=='') {
      if (isset($found[$size])) {
        fwrite(STDERR,"ABORT: duplicate published size {$size} on product {$product_id}\n"); exit(6);
      }
      $found[$size]=$v;
    }
  }

  $expected_sizes=array_keys($by_size);
  $actual_sizes=array_keys($found);
  sort($expected_sizes); sort($actual_sizes);
  if ($expected_sizes!==$actual_sizes) {
    fwrite(STDERR,"ABORT: size set mismatch on {$product_id}; expected ".wp_json_encode($expected_sizes)." got ".wp_json_encode($actual_sizes)."\n");
    exit(7);
  }

  foreach ($by_size as $size=>$new_price) {
    $v=$found[$size];
    $before[$v->get_id()]=mj_price_snapshot($v);
    $changes[]=[
      'product_id'=>$product_id,
      'variation_id'=>(int)$v->get_id(),
      'size'=>$size,
      'old_price'=>(string)$v->get_regular_price('edit'),
      'new_price'=>$new_price,
    ];
    $total_expected++;
  }
}

if ($total_expected!==34) {
  fwrite(STDERR,"ABORT: expected 34 prices, got {$total_expected}\n"); exit(8);
}

// Apply only regular/current price. Clear any sale price so displayed price matches table.
foreach ($changes as $row) {
  $v=wc_get_product($row['variation_id']);
  $v->set_regular_price($row['new_price']);
  $v->set_sale_price('');
  $saved=(int)$v->save();
  if (!$saved) {
    fwrite(STDERR,"ABORT: failed saving variation {$row['variation_id']}\n"); exit(9);
  }
}

// Sync parent price caches only.
foreach (array_keys($prices) as $product_id) {
  WC_Product_Variable::sync($product_id,true);
  wc_delete_product_transients($product_id);
  clean_post_cache($product_id);
}
wp_cache_flush();
if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
if (function_exists('rocket_clean_domain')) rocket_clean_domain();
if (function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');

// Verify all prices and prove unrelated variation fields stayed identical.
$verified=[];
foreach ($changes as $row) {
  $v=wc_get_product($row['variation_id']);
  $regular=(string)$v->get_regular_price('edit');
  $current=(string)$v->get_price('edit');
  if (abs((float)$regular-(float)$row['new_price'])>0.000001 || abs((float)$current-(float)$row['new_price'])>0.000001) {
    fwrite(STDERR,"ABORT: price verification failed for {$row['variation_id']}\n"); exit(10);
  }
  $after=mj_price_snapshot($v);
  if ($after!==$before[$row['variation_id']]) {
    fwrite(STDERR,"ABORT: unrelated variation data changed for {$row['variation_id']}\n"); exit(11);
  }
  $verified[]=[
    'product_id'=>$row['product_id'],
    'variation_id'=>$row['variation_id'],
    'size'=>$row['size'],
    'old_price'=>$row['old_price'],
    'new_price'=>$row['new_price'],
  ];
}

echo "MONTJAM_PRICES_20260924_OK: ".wp_json_encode([
  'updated_count'=>count($verified),
  'updated'=>$verified,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
