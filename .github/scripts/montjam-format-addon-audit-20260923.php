<?php
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT\n"); exit(2); }
global $wpdb;
$blocks=$wpdb->prefix.'yith_wapo_blocks';
$assoc=$wpdb->prefix.'yith_wapo_blocks_assoc';
$addons=$wpdb->prefix.'yith_wapo_addons';
$out=[];
if(!($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$blocks))===$blocks)){ fwrite(STDERR,"NO_BLOCKS\n"); exit(3); }
$rows=$wpdb->get_results("SELECT * FROM `$blocks` WHERE name LIKE 'Mont Jam · Formato · %' OR name LIKE 'Montjam · Formato · %' ORDER BY id",ARRAY_A);
foreach($rows as $b){
  $bid=(int)$b['id'];
  $assoc_rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$assoc` WHERE rule_id=%d ORDER BY id",$bid),ARRAY_A);
  $addon_rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$addons` WHERE block_id=%d ORDER BY id",$bid),ARRAY_A);
  foreach($addon_rows as &$a){
    $a['_settings_unserialized']=maybe_unserialize($a['settings']);
    $a['_options_unserialized']=maybe_unserialize($a['options']);
  }
  unset($a);
  $b['_settings_unserialized']=maybe_unserialize($b['settings']);
  $out[]=['block'=>$b,'assoc'=>$assoc_rows,'addons'=>$addon_rows];
}
echo "MONTJAM_FORMAT_AUDIT: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
