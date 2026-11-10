<?php
if(!defined('ABSPATH')||!function_exists('wc_get_product')){fwrite(STDERR,"NOT_WP\n");exit(2);}
global $wpdb;
$pid=14305;$bid=110;$t=$wpdb->prefix.'yith_wapo_blocks_assoc';
$p=wc_get_product($pid);
if(!$p||!$p->is_type('variable')){fwrite(STDERR,"NO_PRODUCT\n");exit(3);}
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,rule_id,object,type FROM `$t` WHERE rule_id=%d ORDER BY object",$bid),ARRAY_A);
$children=[];
foreach($p->get_children() as $id){$v=wc_get_product($id);if(!$v)continue;$children[]=['id'=>(int)$id,'size'=>$v->get_attributes()['pa_tamano']??'','status'=>$v->get_status(),'regular'=>$v->get_regular_price('edit')];}
echo 'GREEN_FORMAT_ASSOC_DIAG '.wp_json_encode(['product_id'=>$pid,'block_id'=>$bid,'rows'=>$rows,'children'=>$children,'default'=>$p->get_default_attributes(),'all_products_used'=>array_column($rows,'object')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
