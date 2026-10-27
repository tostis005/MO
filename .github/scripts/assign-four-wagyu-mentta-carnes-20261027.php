<?php
/**
 * Add only four preselected Latitudes Wagyu whole-piece products to Mentta > Carnes.
 * Retains their existing public categories, sale prices, visibility, vendor, and stock.
 * Idempotent; fails closed if product identity, prices, or category hierarchy differ.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR, "ABORT: production WooCommerce WP-CLI required\n"); exit(2);
}
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) {
 fwrite(STDERR, "ABORT: unexpected host\n"); exit(2);
}
$root = get_term_by('slug', 'mentta', 'product_cat');
$child = get_term_by('slug', 'mentta-carnes', 'product_cat');
$public = get_term_by('slug', 'carnes', 'product_cat');
$wagyu = get_term_by('slug', 'wagyu', 'product_cat');
$vendor = get_userdata(4949);
if (!$root || !$child || !$public || !$wagyu || !$vendor ||
    !in_array('wcfm_vendor', (array)$vendor->roles, true) ||
    (int)$child->parent !== (int)$root->term_id) {
 fwrite(STDERR, "ABORT: Mentta->Carnes taxonomy or Latitudes vendor mismatch\n"); exit(3);
}
$specs = [
 'kagoshima_a5_lomo_bajo' => ['pattern' => '/^lomo bajo de wagyu japon[eé]s kagoshima a5\b/ui', 'price' => 465.00],
 'kagoshima_a4_lomo_bajo' => ['pattern' => '/^lomo bajo de wagyu japon[eé]s a4 kagoshima\b/ui', 'price' => 385.00],
 'japones_a4_lomo_alto' => ['pattern' => '/^lomo alto de wagyu japon[eé]s a4\b/ui', 'price' => 229.90],
 'japones_a5_lomo_alto' => ['pattern' => '/^lomo alto de wagyu japon[eé]s a5 bms 9-11\b/ui', 'price' => 243.00],
];
$ids = get_posts([
 'post_type' => 'product', 'post_status' => 'publish',
 'author' => 4949, 'posts_per_page' => -1, 'fields' => 'ids',
 'suppress_filters' => true
]);
$before = []; $resolved = []; $errors = [];
foreach ($specs as $key => $spec) {
 $hits = [];
 foreach ($ids as $id) {
  $p = wc_get_product($id);
  if ($p && preg_match($spec['pattern'], $p->get_name())) $hits[] = (int)$id;
 }
 if (count($hits) !== 1) {
  $errors[] = "$key expected 1 unique Latitudes product, got ".count($hits);
  continue;
 }
 $id = $hits[0]; $p = wc_get_product($id);
 $cats = array_map('intval', $p->get_category_ids());
 $price = (float)$p->get_price('edit');
 if (!$p->is_type('simple') || $p->get_status() !== 'publish' ||
     $p->get_catalog_visibility() !== 'visible' ||
     !in_array((int)$public->term_id, $cats, true) ||
     !in_array((int)$wagyu->term_id, $cats, true) ||
     abs($price - $spec['price']) > 0.009 ||
     (int)get_post_field('post_author', $id) !== 4949) {
  $errors[] = "Unexpected identity, type, category, visibility, or price for $key #$id"; continue;
 }
 $before[$id] = [
  'key' => $key, 'name' => $p->get_name(), 'slug' => $p->get_slug(),
  'categories' => $cats, 'price' => (string)$p->get_price('edit'),
  'regular' => (string)$p->get_regular_price('edit'),
  'sale' => (string)$p->get_sale_price('edit'),
  'status' => $p->get_status(), 'visibility' => $p->get_catalog_visibility(),
  'sku' => $p->get_sku(), 'stock' => $p->get_stock_quantity(),
  'stock_status' => $p->get_stock_status(), 'weight' => $p->get_weight('edit'),
  'image' => $p->get_image_id(), 'parent' => $p->get_parent_id()
 ];
 $resolved[$key] = $id;
}
if ($errors || count($resolved) !== 4 || count(array_unique(array_values($resolved))) !== 4) {
 fwrite(STDERR, "PRECHECK_FAILED ".wp_json_encode([
  'matched' => $resolved, 'errors' => $errors
 ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
 exit(4);
}
echo "PREFLIGHT_OK ".wp_json_encode([
 'products' => $resolved, 'root' => (int)$root->term_id,
 'child' => (int)$child->term_id,
 'prior' => array_map(static fn($x) => ['name' => $x['name'], 'price' => $x['price'], 'categories' => $x['categories']], $before)
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";

$modified = []; $report = [];
try {
 foreach ($before as $id => $snapshot) {
  $missing = array_values(array_diff(
   [(int)$root->term_id, (int)$child->term_id], $snapshot['categories']
  ));
  if ($missing) {
   $res = wp_set_object_terms($id, $missing, 'product_cat', true);
   if (is_wp_error($res)) throw new RuntimeException("Failed category addition #$id ".$res->get_error_message());
   $modified[] = $id;
  }
 }
 foreach ($before as $id => $snapshot) {
  clean_post_cache($id); wp_cache_delete($id, 'posts');
  $p = wc_get_product($id);
  if (!$p) throw new RuntimeException("Lost product #$id");
  $expected = array_values(array_unique(array_merge(
   $snapshot['categories'], [(int)$root->term_id, (int)$child->term_id]
  )));
  $actual = array_map('intval', $p->get_category_ids());
  sort($expected, SORT_NUMERIC); sort($actual, SORT_NUMERIC);
  if ($actual !== $expected || $p->get_name() !== $snapshot['name'] ||
      $p->get_slug() !== $snapshot['slug'] ||
      (string)$p->get_price('edit') !== $snapshot['price'] ||
      (string)$p->get_regular_price('edit') !== $snapshot['regular'] ||
      (string)$p->get_sale_price('edit') !== $snapshot['sale'] ||
      $p->get_status() !== $snapshot['status'] ||
      $p->get_catalog_visibility() !== $snapshot['visibility'] ||
      $p->get_sku() !== $snapshot['sku'] ||
      $p->get_stock_quantity() !== $snapshot['stock'] ||
      $p->get_stock_status() !== $snapshot['stock_status'] ||
      $p->get_weight('edit') !== $snapshot['weight'] ||
      $p->get_image_id() !== $snapshot['image'] ||
      $p->get_parent_id() !== $snapshot['parent']) {
   throw new RuntimeException("Post-write verification mismatch #$id");
  }
  $report[] = [
   'id' => $id, 'key' => $snapshot['key'], 'name' => $p->get_name(),
   'price' => $p->get_price('edit'), 'slug' => $p->get_slug(),
   'category_ids' => $actual, 'visibility' => $p->get_catalog_visibility(),
   'changed_now' => in_array($id, $modified, true)
  ];
 }
 foreach (array_keys($before) as $id) {
  wc_delete_product_transients($id); clean_post_cache($id);
  if (function_exists('rocket_clean_post')) rocket_clean_post($id);
 }
 if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product', true);
 echo "FOUR_WAGYU_MENTTA_CARNES_VERIFIED ".wp_json_encode([
  'count' => count($report), 'assigned_now' => count($modified), 'products' => $report
 ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
 fwrite(STDERR, "MENTTA_ASSIGNMENT_FAILED ".$e->getMessage()."\n");
 foreach ($modified as $id) {
  $restore = wp_set_object_terms($id, $before[$id]['categories'], 'product_cat', false);
  if (is_wp_error($restore)) fwrite(STDERR, "ROLLBACK_FAILED #$id ".$restore->get_error_message()."\n");
  wc_delete_product_transients($id); clean_post_cache($id);
 }
 exit(20);
}
