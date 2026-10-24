<?php
/**
 * Pajarete cheese-only publication policy, applied to the 2026-10-24
 * audited live selection (not to any already-excluded/draft references).
 * PAJARETE_ONLY_CHEESE_PHASE=preview|apply|verify.
 *
 * Eligible: cheese varieties in Queso de Cabra / Oveja / Mezcla, plus
 * two prize-winning cheese assortments. Only explicit audited non-cheese
 * WC/source ID pairs are changed. Idempotent and restricted to production.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WooCommerce and supplier sync required.');
}
foreach (['home','siteurl'] as $k) {
    $host = strtolower((string)wp_parse_url((string)get_option($k), PHP_URL_HOST));
    if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Production-only change.');
    }
}
global $wpdb;
$st=MDO_Database::table('suppliers');
$pt=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
$v=get_user_by('id',4974);
if (!$s || (int)$s['vendor_user_id']!==4974 || !$v || $v->user_login!=='quesos-pajarete') {
    throw new RuntimeException('Unexpected producer identity.');
}
// Audited 2026-10-24: published and visible non-cheeses only.
// Key: Woo product ID; value: supplier source row ID.
$exclude=[
    21299 => 949,
    21300 => 950,
    21301 => 951,
    21302 => 952,
    21303 => 953,
    21304 => 954,
    21305 => 955,
    21306 => 956,
    21307 => 957,
    21308 => 958,
    21435 => 988,
    21436 => 989,
    21437 => 990,
    21438 => 991,
    21439 => 992,
    21440 => 993,
    21441 => 994,
    21442 => 995,
    21443 => 996,
    21444 => 997,
    21445 => 998,
    21446 => 999,
    21447 => 1000,
    21448 => 1001,
    21449 => 1002,
    21450 => 1003,
    21451 => 1004,
    21452 => 1005,
    21453 => 1006,
    21454 => 1007,
    21455 => 1008,
    21456 => 1009,
    21457 => 1010,
    21458 => 1011,
    21459 => 1012,
    21460 => 1013,
    21461 => 1014,
    21462 => 1015,
    21463 => 1016,
    21464 => 1017,
    21465 => 1018,
    21466 => 1019,
    21467 => 1020,
    21468 => 1021,
    21469 => 1022,
    21470 => 1023,
    21471 => 1024,
    21472 => 1025,
    21473 => 1026,
    21474 => 1027,
    21475 => 1028,
    21476 => 1029,
    21477 => 1030,
    21478 => 1031,
    21479 => 1032,
    21480 => 1033,
    21481 => 1034,
    21482 => 1035,
    21484 => 1037,
    21485 => 1038,
    21486 => 1039,
    21487 => 1040,
    21488 => 1041,
    21489 => 1042,
    21491 => 1044,
    21492 => 1045,
    21493 => 1046,
    21494 => 1047,
    21506 => 1059,
    21507 => 1060,
    21520 => 1073,
    21524 => 1077,
    21525 => 1078,
    21804 => 1081,
    21807 => 1082,
    21809 => 1083,
];
$keep=[21309, 21319, 21326, 21331, 21338, 21345, 21352, 21359, 21364, 21365, 21366, 21367, 21374, 21381, 21385, 21389, 21396, 21403, 21408, 21409, 21410, 21411, 21412, 21413, 21414, 21415, 21421, 21427, 21430, 21503, 21504, 21521, 21522, 21523, 21801];
$original_excluded=[21490, 21495, 21496, 21497, 21498, 21499, 21500, 21501, 21502, 21505, 21508, 21509, 21510, 21511, 21512, 21513, 21514, 21515, 21516, 21517, 21518, 21519];
$original_other=21483; // Already draft/hidden wine: never change.
if(count($exclude)!==76 || count($keep)!==35 || count($original_excluded)!==22) {
    throw new RuntimeException('Invalid fixed inventory policy.');
}
$allIDs=array_merge(array_keys($exclude),$keep,$original_excluded,[$original_other]);
if(count($allIDs)!==count(array_unique($allIDs))) throw new RuntimeException('Overlapping product sets.');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,title,status,source_url FROM {$pt} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)!==134) throw new RuntimeException('Catalogue inventory changed since review, abort.');
$byID=[];
foreach($rows as $r) {
    if($r['wc_product_id']) $byID[(int)$r['wc_product_id']]=$r;
}
$original_before=[];
foreach($original_excluded as $id){
    $r=$byID[$id]??null;$p=wc_get_product($id);
    if(!$r||!$p||(string)$r['status']!=='excluded') throw new RuntimeException('Previously excluded changed: '.$id);
    $original_before[$id]=[$r['status'],$p->get_status(),$p->get_catalog_visibility()];
}
foreach($keep as $id){
    $r=$byID[$id]??null;$p=wc_get_product($id);
    if(!$r||!$p||(string)$r['status']!=='active'||
       $p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible') {
        throw new RuntimeException('Cheese selection changed: '.$id);
    }
}
$other=$byID[$original_other]??null;$otherp=wc_get_product($original_other);
if(!$other||!$otherp||$otherp->get_status()!=='draft'||
   $otherp->get_catalog_visibility()!=='hidden') {
    throw new RuntimeException('Existing hidden product changed.');
}
$to_change=0;$already_done=0;$titles=[];
foreach($exclude as $id=>$source_id){
    $r=$byID[$id]??null;$p=wc_get_product($id);
    if(!$r||(int)$r['id']!==$source_id||!$p||
       (int)get_post_field('post_author',$id)!==4974||
       !in_array(strtolower((string)wp_parse_url((string)$r['source_url'],PHP_URL_HOST)),
       ['quesospajarete.com','www.quesospajarete.com'],true)) {
        throw new RuntimeException('Source/Woo identity mismatch: '.$id);
    }
    $status=(string)$r['status'];
    if($status==='active' && $p->get_status()==='publish' && $p->get_catalog_visibility()==='visible'){
        $to_change++;
    } elseif($status==='excluded') {
        $already_done++;
    } else {
        throw new RuntimeException('Unexpected changed non-cheese state: '.$id.' '.$status);
    }
    $titles[]=['id'=>$id,'title'=>$r['title'],'previous_status'=>$status];
}
$phase=getenv('PAJARETE_ONLY_CHEESE_PHASE')?:'preview';
if(!in_array($phase,['preview','apply','verify'],true))throw new RuntimeException('Unknown phase');
echo 'PAJARETE_ONLY_CHEESE_PREVIEW='.wp_json_encode([
  'phase'=>$phase, 'supplier_id'=>(int)$s['id'],'vendor_id'=>4974,'total'=>count($rows),
  'new_to_exclude'=>$to_change,'already_excluded_in_target'=>$already_done,
  'preserved_previous_exclusions'=>count($original_excluded),
  'preserved_cheeses'=>count($keep),'preserved_other_hidden'=>1,
  'target_products'=>$titles
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($phase==='preview')return;
if($phase==='apply'){
    foreach($exclude as $id=>$source_id){
        $r=$byID[$id];
        $p=wc_get_product($id);
        if($r['status']==='active') {
            // Exclude from future supplier imports before touching Woo visibility.
            $updated=$wpdb->update($pt,['status'=>'excluded'],['id'=>$source_id,'status'=>'active'],['%s'],['%d','%s']);
            if($updated!==1)throw new RuntimeException('Failed to exclude source row '.$source_id);
        }
        if($p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden'){
            $p->set_status('draft');
            $p->set_catalog_visibility('hidden');
            $p->save();
        }
        if(function_exists('wc_delete_product_transients'))wc_delete_product_transients($id);
        clean_post_cache($id);
    }
}
$confirmed=[];$missing=[];
foreach($exclude as $id=>$source_id){
    $r=$wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$pt} WHERE id=%d",$source_id),ARRAY_A);
    $p=wc_get_product($id);
    if($r&&(string)$r['status']==='excluded'&&$p&&$p->get_status()==='draft'&&
       $p->get_catalog_visibility()==='hidden') $confirmed[]=$id;
    else $missing[]=$id;
}
$previous_changed=[];
foreach($original_before as $id=>$state){
    $r=$wpdb->get_row($wpdb->prepare("SELECT status FROM {$pt} WHERE id=%d",(int)$byID[$id]['id']),ARRAY_A);
    $p=wc_get_product($id);
    if(!$r||!$p||[$r['status'],$p->get_status(),$p->get_catalog_visibility()]!==$state)$previous_changed[]=$id;
}
$cheeses_still_visible=0;
foreach($keep as $id) {
    $p=wc_get_product($id);
    if($p&&$p->get_status()==='publish'&&$p->get_catalog_visibility()==='visible')$cheeses_still_visible++;
}
$result=[
  'phase'=>$phase,'excluded_noncheese'=>count($confirmed),
  'excluded_noncheese_ids'=>$confirmed,'failures'=>$missing,
  'previously_excluded_untouched'=>count($original_excluded)-count($previous_changed),
  'original_exclusions_changed'=>$previous_changed,
  'cheese_products_remain_visible'=>$cheeses_still_visible,
  'other_hidden_remains_hidden'=>wc_get_product($original_other)->get_status()==='draft' &&
       wc_get_product($original_other)->get_catalog_visibility()==='hidden'
];
echo 'PAJARETE_ONLY_CHEESE_RESULT='.wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(count($confirmed)!==76||$missing||$previous_changed||$cheeses_still_visible!==35||
   !$result['other_hidden_remains_hidden'])throw new RuntimeException('Cheese-only verification failed.');
