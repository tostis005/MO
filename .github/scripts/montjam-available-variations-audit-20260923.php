<?php
if(!defined('ABSPATH')) exit(2);
$ids=[14264,14271,14287,14294,14275,14301,14305];
$out=[];
foreach($ids as $pid){
 $p=wc_get_product($pid);
 $children=$p?array_map('intval',$p->get_children()):[];
 $available=[];
 if($p && $p->is_type('variable')){
   foreach($p->get_available_variations('objects') as $v){
     $available[]=['id'=>(int)$v->get_id(),'attrs'=>$v->get_attributes(),'price'=>$v->get_price(),'status'=>$v->get_status(),'visible'=>$v->variation_is_visible(),'active'=>$v->variation_is_active()];
   }
 }
 $out[$pid]=['children'=>$children,'available'=>$available,'count_children'=>count($children),'count_available'=>count($available)];
}
echo "MONTJAM_AVAILABLE_VARIATIONS_AUDIT: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
