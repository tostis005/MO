<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

$expected = [
  11058 => ['key'=>'carne_picada','title'=>'Carne picada extra','marker'=>'Carne picada elaborada con 100% ternera y sin aditivos','en_title'=>'Extra Ground Beef','en_marker'=>'Ground beef made with 100% beef and no added additives.'],
  11061 => ['key'=>'burger_mixta','title'=>'Burger mixtas - sin gluten (2 uds)','marker'=>'Hamburguesas elaboradas con una mezcla al 50% de carne de ternera y carne de cerdo de la zona','en_title'=>'Gluten-Free Beef & Pork Burgers','en_marker'=>'Burgers made with an equal blend of beef and locally sourced pork.'],
  11064 => ['key'=>'filetes_primera','title'=>'Filetes primera','marker'=>'Filetes de ternera procedentes de piezas especialmente adecuadas para una cocción rápida','en_title'=>'First-Category Beef Steaks','en_marker'=>'Beef steaks cut from tender pieces such as the knuckle or rump'],
  11073 => ['key'=>'ragu','title'=>'Magro o ragú de ternera','marker'=>'Carne de ternera cortada a mano y pensada especialmente para preparaciones','en_title'=>'Diced Beef for Ragout','en_marker'=>'Hand-cut beef designed for dishes where the meat has time to cook slowly'],
  11075 => ['key'=>'entrana','title'=>'Entraña de ternera','marker'=>'La entraña es un corte fino','en_title'=>'Beef Skirt Steak – Entraña','en_marker'=>'Entraña is a thin, flavourful cut taken from the inside of the rib area.'],
];

$trp = $wpdb->prefix . 'trp_dictionary_es_es_en_us';
$trp_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $trp)) === $trp;

echo "==== BATCH01 AFTER ROLLBACK ====\n";
$batch_ok = true;
foreach ($expected as $id => $spec) {
  $p = get_post($id);
  if (!$p || $p->post_type !== 'product') { echo "BATCH01 {$spec['key']} ID={$id} MISSING\n"; $batch_ok=false; continue; }
  $u = get_userdata((int)$p->post_author); $author = $u ? $u->display_name : '';
  $es_marker = strpos((string)$p->post_excerpt, $spec['marker']) !== false;
  $es_producer = strpos((string)$p->post_content, 'Tolecarnes es una ganadería familiar de Menasalbas') !== false;
  $en_title = $en_marker = $en_producer = false;
  if ($trp_exists) {
    $en_title = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated=%s AND status=2", $p->post_title, $spec['en_title'])) > 0;
    $en_marker = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE translated LIKE %s AND status=2", '%'.$wpdb->esc_like($spec['en_marker']).'%')) > 0;
    $en_producer = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated=%s AND status=2", 'Sobre Tolecarnes', 'About Tolecarnes')) > 0;
  }
  $ok = $es_marker && $es_producer && $en_title && $en_marker && $en_producer;
  if (!$ok) $batch_ok=false;
  echo sprintf("BATCH01 %s ID=%d title=%s author=%s ES_MARKER=%s ES_PRODUCER=%s EN_TITLE=%s EN_MARKER=%s EN_PRODUCER=%s STATUS=%s\n",
    $spec['key'],$id,$p->post_title,$author,$es_marker?'yes':'no',$es_producer?'yes':'no',$en_title?'yes':'no',$en_marker?'yes':'no',$en_producer?'yes':'no',$ok?'OK':'NEEDS_REAPPLY');
}
echo 'BATCH01_OVERALL=' . ($batch_ok ? 'OK' : 'NEEDS_REAPPLY') . "\n";

echo "==== NEXT FIVE CANDIDATES (IN-STOCK AFTER BATCH01) ====\n";
$next_ids=[11077,11079,11082,11087,11090];
foreach($next_ids as $id){
  $p=get_post($id); if(!$p||$p->post_type!=='product'){echo "NEXT ID={$id} MISSING\n";continue;}
  $u=get_userdata((int)$p->post_author);$author=$u?$u->display_name:'';
  $sku=get_post_meta($id,'_sku',true);$price=get_post_meta($id,'_price',true);$stock=get_post_meta($id,'_stock_status',true);
  $excerpt=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$p->post_excerpt),ENT_QUOTES|ENT_HTML5,'UTF-8')));
  $content=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$p->post_content),ENT_QUOTES|ENT_HTML5,'UTF-8')));
  echo "NEXT ID={$id} title={$p->post_title} slug={$p->post_name} author={$author} sku={$sku} price={$price} stock={$stock}\n";
  echo "NEXT_EXCERPT ID={$id}: {$excerpt}\n";
  echo "NEXT_CONTENT ID={$id}: {$content}\n";
  $terms=wp_get_post_terms($id,'product_cat',['fields'=>'names']); if(!is_wp_error($terms)) echo "NEXT_CATEGORIES ID={$id}: ".implode(' | ',$terms)."\n";
}

echo "DIAGNOSTIC_DONE\n";
