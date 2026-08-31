<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

$expected = [
  11058 => ['key'=>'carne_picada','title'=>'Carne picada extra','slug'=>'carne-picada-extra','marker'=>'Carne picada elaborada con 100% ternera y sin aditivos','en_title'=>'Extra Ground Beef'],
  11061 => ['key'=>'burger_mixta','title'=>'Burger mixtas - sin gluten (2 uds)','slug'=>'burger-mixtas-sin-gluten-2-uds','marker'=>'Hamburguesas elaboradas con una mezcla al 50% de carne de ternera y carne de cerdo de la zona','en_title'=>'Gluten-Free Beef & Pork Burgers'],
  11064 => ['key'=>'filetes_primera','title'=>'Filetes primera','slug'=>'filetes-primera','marker'=>'Filetes de ternera procedentes de piezas especialmente adecuadas para una cocción rápida','en_title'=>'First-Category Beef Steaks'],
  11073 => ['key'=>'ragu','title'=>'Magro o ragú de ternera','slug'=>'magro-o-ragu-de-ternera','marker'=>'Carne de ternera cortada a mano y pensada especialmente para preparaciones','en_title'=>'Diced Beef for Ragout'],
  11075 => ['key'=>'entrana','title'=>'Entraña de ternera','slug'=>'entrana-de-ternera','marker'=>'La entraña es un corte fino','en_title'=>'Beef Skirt Steak – Entraña'],
];

$trp = $wpdb->prefix . 'trp_dictionary_es_es_en_us';
$trp_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $trp)) === $trp;

echo "==== BATCH01 AFTER ROLLBACK ====\n";
$batch_ok = true;
foreach ($expected as $id => $spec) {
  $p = get_post($id);
  if (!$p || $p->post_type !== 'product') {
    echo "BATCH01 {$spec['key']} ID={$id} MISSING\n";
    $batch_ok = false;
    continue;
  }
  $u = get_userdata((int)$p->post_author);
  $author = $u ? $u->display_name : '';
  $es_marker = strpos((string)$p->post_excerpt, $spec['marker']) !== false;
  $es_producer = strpos((string)$p->post_content, 'Tolecarnes es una ganadería familiar de Menasalbas') !== false;
  $en_title = false;
  $en_producer = false;
  if ($trp_exists) {
    $en_title = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated=%s", $p->post_title, $spec['en_title'])) > 0;
    $en_producer = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated LIKE %s", '<h2>Sobre Tolecarnes</h2>', '%About Tolecarnes%')) > 0;
  }
  $ok = $es_marker && $es_producer && $en_title && $en_producer;
  if (!$ok) $batch_ok = false;
  echo sprintf(
    "BATCH01 %s ID=%d title=%s author=%s ES_MARKER=%s ES_PRODUCER=%s EN_TITLE=%s EN_PRODUCER=%s STATUS=%s\n",
    $spec['key'], $id, $p->post_title, $author,
    $es_marker?'yes':'no', $es_producer?'yes':'no', $en_title?'yes':'no', $en_producer?'yes':'no', $ok?'OK':'NEEDS_REAPPLY'
  );
}
echo 'BATCH01_OVERALL=' . ($batch_ok ? 'OK' : 'NEEDS_REAPPLY') . "\n";

echo "==== ALL TOLECARNES PRODUCTS ====\n";
$users = get_users(['fields'=>['ID','display_name']]);
$vendor_ids = [];
foreach ($users as $u) {
  if (stripos((string)$u->display_name, 'tolecarnes') !== false) $vendor_ids[] = (int)$u->ID;
}
if (!$vendor_ids) {
  echo "NO_TOLECARNES_USER_FOUND\n";
} else {
  $ids_sql = implode(',', array_map('intval', $vendor_ids));
  $rows = $wpdb->get_results("SELECT ID,post_title,post_name,post_status,post_author FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN ('trash','auto-draft') AND post_author IN ({$ids_sql}) ORDER BY ID ASC");
  foreach ($rows as $p) {
    $sku = get_post_meta($p->ID, '_sku', true);
    $price = get_post_meta($p->ID, '_price', true);
    $stock = get_post_meta($p->ID, '_stock_status', true);
    $excerpt_len = strlen(wp_strip_all_tags((string)get_post_field('post_excerpt',$p->ID)));
    $content_len = strlen(wp_strip_all_tags((string)get_post_field('post_content',$p->ID)));
    echo "PRODUCT ID={$p->ID} title={$p->post_title} slug={$p->post_name} sku={$sku} price={$price} stock={$stock} excerpt_len={$excerpt_len} content_len={$content_len}\n";
  }
}

echo "DIAGNOSTIC_DONE\n";
