<?php
// Read-only diagnostic: identify the next five unprocessed Tolecarnes products.
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

$done = [11058,11077,11079,11082,11087,11090];
// Also treat any product already containing the new producer block as completed,
// which safely catches the remaining IDs from batch 01 without hard-coding them.
$rows = $wpdb->get_results("SELECT ID,post_title,post_name,post_author,post_status,post_excerpt,post_content FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN ('trash','auto-draft') ORDER BY ID ASC");
$candidates=[];
foreach($rows as $p){
    $u=get_userdata((int)$p->post_author);
    $vendor=$u ? (string)$u->display_name : '';
    if(stripos($vendor,'tolecarnes')===false) continue;
    $completed = in_array((int)$p->ID,$done,true) || stripos((string)$p->post_content,'Sobre Tolecarnes')!==false;
    $sku=(string)get_post_meta($p->ID,'_sku',true);
    $stock=(string)get_post_meta($p->ID,'_stock_status',true);
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    $type=wp_get_post_terms($p->ID,'product_type',['fields'=>'names']);
    $type=is_wp_error($type)?'':implode(',',$type);
    echo "PRODUCT ID={$p->ID} completed=".($completed?'yes':'no')." status={$p->post_status} stock={$stock} type={$type} vendor={$vendor}\n";
    echo "TITLE={$p->post_title}\nSLUG={$p->post_name}\nSKU={$sku}\n";
    echo "ES_EXCERPT=".preg_replace('/\s+/u',' ',trim(wp_strip_all_tags((string)$p->post_excerpt)))."\n";
    echo "ES_CONTENT=".preg_replace('/\s+/u',' ',trim(wp_strip_all_tags((string)$p->post_content)))."\n";
    echo "EN_CONTENT=".preg_replace('/\s+/u',' ',trim(wp_strip_all_tags($en)))."\n---\n";
    if(!$completed) $candidates[]=(int)$p->ID;
}
echo 'NEXT_FIVE='.implode(',',array_slice($candidates,0,5))."\n";
