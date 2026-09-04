<?php
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
function outj($label,$v){ echo $label.': '.wp_json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"; }
$blocks=$wpdb->prefix.'yith_wapo_blocks';
$assoc=$wpdb->prefix.'yith_wapo_blocks_assoc';
$addons=$wpdb->prefix.'yith_wapo_addons';

$slugs=[
 'montjam_jamon'=>'jamon-de-bellota-100-iberico-montjam',
 'montjam_jamon50'=>'jamon-de-bellota-iberico-50-montjam',
 'montjam_paleta'=>'paleta-de-bellota-100-iberica-montjam',
 'hidalgo_jamon'=>'jamon-de-bellota-100-iberico',
];

foreach($slugs as $label=>$slug){
  $p=get_page_by_path($slug,OBJECT,'product');
  if(!$p){ outj('MISSING_'.$label,['slug'=>$slug]); continue; }
  $id=(int)$p->ID;
  $rels=$wpdb->get_results($wpdb->prepare("SELECT a.*,b.name,b.settings AS block_settings,b.product_association,b.vendor_id,b.user_id,b.priority AS block_priority,b.visibility,b.creation_date,b.last_update,b.exclude_products,b.user_association,b.exclude_users FROM `$assoc` a JOIN `$blocks` b ON b.id=a.rule_id WHERE a.object=%s ORDER BY a.rule_id",(string)$id),ARRAY_A);
  $rows=[];
  foreach($rels as $r){
    $ads=$wpdb->get_results($wpdb->prepare("SELECT id,block_id,settings,options,priority,visibility,creation_date,last_update FROM `$addons` WHERE block_id=%d ORDER BY priority,id",(int)$r['rule_id']),ARRAY_A);
    foreach($ads as &$a){ $a['settings']=maybe_unserialize($a['settings']); $a['options']=maybe_unserialize($a['options']); }
    unset($a);
    $r['block_settings']=maybe_unserialize($r['block_settings']);
    $r['addons']=$ads;
    $rows[]=$r;
  }
  outj('PRODUCT_'.$label,['id'=>$id,'title'=>$p->post_title,'slug'=>$p->post_name,'blocks'=>$rows]);
}
