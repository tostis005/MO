<?php
/**
 * Read-only product and variation title audit for seller 1957.
 * No customer data or writes.
 */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
$u = get_user_by('slug', '1957');
if (!$u) { $u = get_user_by('login', '1957'); }
if (!$u) { echo "AUDIT_1957_VENDOR_NOT_FOUND\n"; exit(2); }
$uid = (int) $u->ID;
echo 'AUDIT_1957_VENDOR_ID=' . $uid . "\n";
$roots = $wpdb->get_results($wpdb->prepare(
  "SELECT ID, post_title, post_status, post_author FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d ORDER BY ID DESC LIMIT 200",
  $uid
), ARRAY_A);
echo 'AUDIT_1957_PRODUCTS_COUNT=' . count($roots) . "\n";
$ids = array_map(static function($r){ return (int)$r['ID']; }, $roots);
foreach ($roots as $r) {
  $attrs = get_post_meta((int)$r['ID'], '_product_attributes', true);
  $simple = [];
  if (is_array($attrs)) {
    foreach ($attrs as $k=>$val) {
      $simple[(string)$k] = [
        'name' => isset($val['name']) ? (string)$val['name'] : '',
        'value' => isset($val['value']) ? mb_substr((string)$val['value'],0,1400) : '',
        'is_variation' => isset($val['is_variation']) ? (int)$val['is_variation'] : 0
      ];
    }
  }
  echo 'PRODUCT=' . wp_json_encode(['id'=>(int)$r['ID'], 'title'=>$r['post_title'], 'status'=>$r['post_status'], 'attributes'=>$simple],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
}
if ($ids) {
  $in = implode(',',array_map('intval',$ids));
  $rows = $wpdb->get_results("SELECT ID,post_parent,post_title,post_status FROM {$wpdb->posts} WHERE post_type='product_variation' AND post_parent IN ($in) ORDER BY post_parent,ID", ARRAY_A);
  echo 'AUDIT_1957_VARIATIONS_COUNT=' . count($rows) . "\n";
  foreach ($rows as $r) {
    $meta = get_post_meta((int)$r['ID']);
    $attrs = [];
    foreach ((array)$meta as $k=>$v) {
      if (str_starts_with((string)$k, 'attribute_')) $attrs[$k] = isset($v[0]) ? maybe_unserialize($v[0]) : '';
    }
    echo 'VARIATION=' . wp_json_encode(['id'=>(int)$r['ID'], 'parent'=>(int)$r['post_parent'], 'title'=>$r['post_title'], 'status'=>$r['post_status'], 'attributes'=>$attrs],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
  }
}
echo "AUDIT_1957_READ_ONLY_COMPLETE\n";
