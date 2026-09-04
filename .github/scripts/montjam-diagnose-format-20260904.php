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

// Find a whole-piece Hidalgo paleta that uses a format selector.
$hid_vendor=get_user_by('slug','hidalgo-de-la-jara');
if(!$hid_vendor){
  $users=get_users(['search'=>'*Hidalgo de la Jara*','search_columns'=>['display_name']]);
  $hid_vendor=$users?$users[0]:null;
}
$paleta_ref=null;
if($hid_vendor){
  $qs=get_posts(['post_type'=>'product','post_status'=>['publish','private','draft'],'author'=>$hid_vendor->ID,'s'=>'Paleta','posts_per_page'=>50]);
  foreach($qs as $p){
    $prod=wc_get_product($p->ID);
    if(!$prod) continue;
    $tax=wp_get_post_terms($p->ID,'pa_tipo-pieza',['fields'=>'slugs']);
    $prep=wp_get_post_terms($p->ID,'pa_preparacion',['fields'=>'slugs']);
    if(in_array('paleta',$tax,true) && (in_array('pieza-entera',$prep,true)||in_array('cortado-a-cuchillo',$prep,true)||in_array('deshuesado',$prep,true))){
      $paleta_ref=$p; break;
    }
  }
}
if($paleta_ref) $slugs['hidalgo_paleta']=$paleta_ref->post_name;

foreach($slugs as $label=>$slug){
  $p=get_page_by_path($slug,OBJECT,'product');
  if(!$p){ outj('MISSING_'.$label,['slug'=>$slug]); continue; }
  $id=(int)$p->ID;
  $rels=$wpdb->get_results($wpdb->prepare("SELECT a.*,b.name,b.settings AS block_settings,b.product_association,b.vendor_id,b.visibility FROM `$assoc` a JOIN `$blocks` b ON b.id=a.rule_id WHERE a.object=%s ORDER BY a.rule_id",(string)$id),ARRAY_A);
  $rows=[];
  foreach($rels as $r){
    $ads=$wpdb->get_results($wpdb->prepare("SELECT id,block_id,settings,options,priority,visibility FROM `$addons` WHERE block_id=%d ORDER BY priority,id",(int)$r['rule_id']),ARRAY_A);
    foreach($ads as &$a){ $a['settings']=maybe_unserialize($a['settings']); $a['options']=maybe_unserialize($a['options']); }
    unset($a);
    $r['block_settings']=maybe_unserialize($r['block_settings']);
    $r['addons']=$ads;
    $rows[]=$r;
  }
  outj('PRODUCT_'.$label,['id'=>$id,'title'=>$p->post_title,'slug'=>$p->post_name,'blocks'=>$rows]);
}
