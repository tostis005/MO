<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) { echo "TRP_TABLE_MISSING\n"; return; }
$terms=[
 'Una carne para el día a día',
 'La carne picada es una de esas opciones',
 'Sobre Tolecarnes',
 'Tolecarnes es una ganadería familiar de Menasalbas',
 'Preguntas frecuentes',
 '¿La carne picada lleva aditivos?',
 'Carne 100% Ternera Natural sin aditivos.',
 'Un entrecot tierno y sabroso',
 'El entrecot de lomo bajo procede',
 'Entrecot de ternera seleccionado del lomo bajo',
];
foreach($terms as $term){
 echo "==== TERM: {$term} ====\n";
 $like='%'.$wpdb->esc_like($term).'%';
 $rows=$wpdb->get_results($wpdb->prepare("SELECT id,original,translated,status,block_type FROM `{$table}` WHERE original LIKE %s OR translated LIKE %s ORDER BY id DESC LIMIT 20",$like,$like),ARRAY_A);
 if(!$rows){echo "NO_ROWS\n";continue;}
 foreach($rows as $r){
   $o=preg_replace('/\s+/u',' ',(string)$r['original']);
   $t=preg_replace('/\s+/u',' ',(string)$r['translated']);
   echo "ID={$r['id']} block_type={$r['block_type']} status={$r['status']}\nORIGINAL={$o}\nTRANSLATED={$t}\n";
 }
}

echo "==== BLOCK TYPE COUNTS FOR LONG CONTENT FRAGMENTS ====\n";
$frags=['carne picada','Tolecarnes','entrecot','Preguntas frecuentes'];
foreach($frags as $frag){
 $like='%'.$wpdb->esc_like($frag).'%';
 $rows=$wpdb->get_results($wpdb->prepare("SELECT block_type,status,COUNT(*) c FROM `{$table}` WHERE original LIKE %s GROUP BY block_type,status ORDER BY block_type,status",$like),ARRAY_A);
 echo "FRAG={$frag}\n";
 foreach($rows as $r) echo "block_type={$r['block_type']} status={$r['status']} count={$r['c']}\n";
}
echo "DONE\n";
