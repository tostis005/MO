<?php
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT\n"); exit(2); }
global $wpdb;
$product_ids=[14264,14271,14275,14287,14294,14301,14305];
$out=['terms'=>[],'products'=>[],'yith'=>[]];
foreach(['4-45-kg','45-5-kg','5-55-kg','55-6-kg','6-65-kg','65-7-kg','7-75-kg','75-8-kg','8-85-kg','85-9-kg','9-95-kg'] as $slug){
  $t=get_term_by('slug',$slug,'pa_tamano');
  $out['terms'][$slug]=$t?['id'=>(int)$t->term_id,'name'=>$t->name]:null;
}
foreach($product_ids as $id){
  $p=wc_get_product($id);
  $out['products'][$id]=[
    'title'=>$p?$p->get_name():'',
    'children'=>$p?array_map('intval',$p->get_children()):[],
    'terms'=>wp_get_object_terms($id,'pa_tamano',['fields'=>'slugs']),
    'attrs'=>$p?array_map(function($a){return $a instanceof WC_Product_Attribute?['name'=>$a->get_name(),'options'=>$a->get_options(),'variation'=>$a->get_variation(),'visible'=>$a->get_visible()]:$a;},$p->get_attributes()):[],
    'defaults'=>$p?$p->get_default_attributes():[],
  ];
}
$tables=$wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}yith_wapo%'");
foreach($tables as $table){
  $cols=$wpdb->get_results("SHOW COLUMNS FROM `$table`",ARRAY_A);
  $colnames=array_column($cols,'Field');
  $rows=[];
  foreach($colnames as $col){
    if (in_array($col,['product_id','product_ids','products','rule_products','exclude_products','conditional_rule_variations','variations','variation_ids'],true)){
      foreach($product_ids as $pid){
        $found=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE CAST(`$col` AS CHAR) LIKE %s LIMIT 20",'%'.$pid.'%'),ARRAY_A);
        foreach($found as $r) $rows[]=$r;
      }
    }
  }
  if($rows) $out['yith'][$table]=array_values($rows);
}
echo "MONTJAM_PREP_AUDIT: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
