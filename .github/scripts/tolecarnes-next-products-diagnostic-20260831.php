<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
$ids = $wpdb->get_col($wpdb->prepare(
  "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND ID > %d ORDER BY ID ASC LIMIT 250",
  11114
));
$count = 0;
foreach ($ids as $id) {
  $p = get_post((int)$id);
  if (!$p) continue;
  $u = get_userdata((int)$p->post_author);
  $vendor = $u ? (string)$u->display_name : '';
  if (stripos($vendor, 'tolecarnes') === false) continue;
  $stock = (string)get_post_meta($id, '_stock_status', true);
  if ($stock !== 'instock') continue;
  $sku = (string)get_post_meta($id, '_sku', true);
  $type = wp_get_post_terms($id, 'product_type', ['fields'=>'names']);
  $type = $type ? implode(',', $type) : '';
  $excerpt = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string)$p->post_excerpt)));
  $content = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string)$p->post_content)));
  echo "ID={$id}\nTITLE={$p->post_title}\nSLUG={$p->post_name}\nSKU={$sku}\nTYPE={$type}\nSTOCK={$stock}\nEXCERPT={$excerpt}\nCONTENT={$content}\n---\n";
  $count++;
  if ($count >= 10) break;
}
echo "COUNT={$count}\n";
