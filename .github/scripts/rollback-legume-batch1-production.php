<?php
if (!defined('ABSPATH')) { exit(1); }
$rollback_path = getenv('EMDO_LEGUME_ROLLBACK');
if (!$rollback_path || !is_readable($rollback_path)) { exit(2); }
$data = json_decode((string)file_get_contents($rollback_path), true);
$ids = array_map('intval', (array)($data['created_ids'] ?? []));
$allowed = [
  'que-nutrientes-aportan-legumbres-proteinas-fibra-hierro-vitaminas-minerales',
  'que-legumbre-tiene-mas-proteina-comparativa-legumbres-espana',
  'que-legumbre-tiene-mas-hierro-comparativa-legumbres-espana',
  'que-legumbre-tiene-mas-fibra-comparativa-nutricional',
  'garbanzos-lentejas-alubias-cual-es-mas-nutritiva',
];
$deleted = [];
foreach ($ids as $id) {
  $post = get_post($id);
  if (!$post instanceof WP_Post || $post->post_type !== 'post' || !in_array((string)$post->post_name, $allowed, true)) {
    continue;
  }
  if (wp_delete_post($id, true)) { $deleted[] = $id; }
}
flush_rewrite_rules(false);
file_put_contents($rollback_path, wp_json_encode(['created_ids' => []]));
echo wp_json_encode(['rolled_back' => true, 'deleted_ids' => $deleted]) . "\n";
