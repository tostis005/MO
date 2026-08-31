<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
$args = [
  'post_type' => 'product',
  'post_status' => 'publish',
  'posts_per_page' => 30,
  'orderby' => 'ID',
  'order' => 'ASC',
  'fields' => 'ids',
];
$ids = get_posts($args);
$count = 0;
foreach ($ids as $id) {
  if ($id <= 11114) continue;
  $p = get_post($id);
  if (!$p) continue;
  $u = get_userdata((int)$p->post_author);
  $vendor = $u ? (string)$u->display_name : '';
  if (stripos($vendor, 'tolecarnes') === false) continue;
  $stock = (string)get_post_meta($id, '_stock_status', true);
  if ($stock !== 'instock') continue;
  $sku = (string)get_post_meta($id, '_sku', true);
  $type = wp_get_post_terms($id, 'product_type', ['fields'=>'names']);
  $type = $type ? implode(',', $type) : '';
  $excerpt = trim(wp_strip_all_tags((string)$p->post_excerpt));
  $content = trim(wp_strip_all_tags((string)$p->post_content));
  echo "ID={$id}\nTITLE={$p->post_title}\nSLUG={$p->post_name}\nSKU={$sku}\nTYPE={$type}\nSTOCK={$stock}\nEXCERPT={$excerpt}\nCONTENT={$content}\n---\n";
  $count++;
  if ($count >= 10) break;
}
echo "COUNT={$count}\n";
