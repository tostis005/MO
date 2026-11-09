<?php
/** Read-only production diagnosis of Montjam green shoulder (14305), 2026-11-09. */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) { fwrite(STDERR,"ABORT_NOT_WORDPRESS\n"); exit(2); }
global $wpdb;
$ids=[14305,14275,14301];
$out=['site'=>home_url('/'),'products'=>[],'yith_tables'=>[]];
foreach ($ids as $id) {
  $p=wc_get_product($id);
  if (!$p || !$p->is_type('variable')) { fwrite(STDERR,"ABORT_PRODUCT_$id\n"); exit(3); }
  $terms=wp_get_object_terms($id,'pa_tamano',['fields'=>'slugs']);
  $attrs=$p->get_attributes();
  $sizes=isset($attrs['pa_tamano'])&&($attrs['pa_tamano'] instanceof WC_Product_Attribute)?array_map('intval',$attrs['pa_tamano']->get_options()):[];
  $row=['id'=>$id,'name'=>$p->get_name(),'status'=>$p->get_status(),'author'=>(int)get_post_field('post_author',$id),'producer'=>wp_get_object_terms($id,'pa_productor',['fields'=>'slugs']),'size_terms'=>is_wp_error($terms)?[]:$terms,'size_attribute_ids'=>$sizes,'default'=>$p->get_default_attributes(),'variations'=>[]];
  foreach ($p->get_children() as $vid) {
    $v=wc_get_product($vid); if (!$v) continue;
    $row['variations'][]=['id'=>$vid,'attrs'=>$v->get_attributes(),'status'=>$v->get_status(),'stock'=>$v->get_stock_status(),'manage_stock'=>$v->get_manage_stock(),'regular'=>$v->get_regular_price('edit'),'sale'=>$v->get_sale_price('edit'),'price'=>$v->get_price('edit'),'sku'=>$v->get_sku()];
  }
  $out['products'][]=$row;
}
$bt=$wpdb->prefix.'yith_wapo_blocks'; $at=$wpdb->prefix.'yith_wapo_addons'; $st=$wpdb->prefix.'yith_wapo_blocks_assoc';
foreach ([$bt,$at,$st] as $t) {
  $ok=$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$t))===$t;
  $out['yith_tables'][$t]=$ok;
}
if ($out['yith_tables'][$bt]&&$out['yith_tables'][$at]) {
  $out['format_blocks']=[];
  foreach ($ids as $pid) {
    $bs=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$bt` WHERE name LIKE %s ORDER BY id","%Formato · ".$pid),ARRAY_A);
    foreach ($bs as $b) {
      $settings=maybe_unserialize($b['settings']);
      $rr=['id'=>(int)$b['id'],'product_id'=>$pid,'name'=>$b['name'],'visibility'=>$b['visibility'],'association'=>$b['product_association']??null,'settings_rules'=>$settings['rules']??null,'addons'=>[]];
      $as=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$at` WHERE block_id=%d ORDER BY id",(int)$b['id']),ARRAY_A);
      foreach ($as as $a) {
        $ss=maybe_unserialize($a['settings']); $opts=maybe_unserialize($a['options']);
        $rr['addons'][]=['id'=>(int)$a['id'],'visibility'=>$a['visibility'],'settings'=>['type'=>$ss['type']??null,'title'=>$ss['title']??null,'enable_rules_variations'=>$ss['enable_rules_variations']??null,'conditional_rule_variations'=>$ss['conditional_rule_variations']??null],'options'=>['label'=>$opts['label']??null,'price'=>$opts['price']??null,'price_method'=>$opts['price_method']??null,'price_type'=>$opts['price_type']??null,'addon_enabled'=>$opts['addon_enabled']??null]];
      }
      $out['format_blocks'][]=$rr;
    }
  }
}
echo "MONTJAM_GREEN_AUDIT ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "MONTJAM_GREEN_AUDIT_OK\n";
