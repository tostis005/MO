<?php
/**
 * One-off, guarded/idempotent 1957 new-campaign (26/27) oil title update.
 * Does not change slugs, prices, attributes, orders, products from 25/26 or visibility.
 */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
$user = get_user_by('slug', '1957');
if (!$user) $user = get_user_by('login', '1957');
if (!$user) { fwrite(STDERR, "1957_ABORT_NO_VENDOR\n"); exit(2); }
$uid = (int) $user->ID;
$map = [
 'SIN FILTRAR NUEVA CAMPAÑA 26/27. Aceite de oliva virgen extra 5L' => 'COSECHA 26/27 · SIN FILTRAR · ACEITE DE OLIVA VIRGEN EXTRA 5L · EXTRACCIÓN EN FRÍO',
 'SIN FILTRAR NUEVA CAMPAÑA 26/27. Aceite de oliva virgen extra 12x500ml' => 'COSECHA 26/27 · SIN FILTRAR · ACEITE DE OLIVA VIRGEN EXTRA 12X500ML · EXTRACCIÓN EN FRÍO',
 'SIN FILTRAR NUEVA CAMPAÑA 26/27 Aceite de oliva virgen extra 15x1L' => 'COSECHA 26/27 · SIN FILTRAR · ACEITE DE OLIVA VIRGEN EXTRA 15X1L · EXTRACCIÓN EN FRÍO',
];
$roots = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title,post_status FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish'", $uid), ARRAY_A);
$plan = [];
$seen = [];
$children_plan = [];
foreach ($roots as $r) {
 $before = (string)$r['post_title'];
 $match = null;
 foreach ($map as $old=>$new) {
   if ($before === $old || $before === $new) { $match = [$old,$new]; break; }
 }
 if (!$match) continue;
 [$old,$new] = $match;
 if (isset($seen[$old])) { fwrite(STDERR,"1957_ABORT_DUPLICATE_PRODUCT\n"); exit(3); }
 $seen[$old] = (int)$r['ID'];
 $plan[]=['id'=>(int)$r['ID'],'old'=>$before,'new'=>$new];
 $vars = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title,post_status FROM {$wpdb->posts} WHERE post_type='product_variation' AND post_parent=%d ORDER BY ID",(int)$r['ID']), ARRAY_A);
 foreach ($vars as $v) {
   $vold = (string)$v['post_title'];
   if (str_starts_with($vold,$new)) { $vnew=$vold; }
   elseif (str_starts_with($vold,$old)) { $vnew=$new . substr($vold,strlen($old)); }
   else { fwrite(STDERR,"1957_ABORT_UNEXPECTED_VARIATION_PREFIX parent={$r['ID']} variation={$v['ID']}\n"); exit(4); }
   $children_plan[]=['id'=>(int)$v['ID'],'old'=>$vold,'new'=>$vnew,'parent'=>(int)$r['ID']];
 }
}
if (count($seen)!==3 || count($plan)!==3) {
 fwrite(STDERR,"1957_ABORT_EXPECTED_THREE_NEW_CAMPAIGN_PRODUCTS_FOUND_".count($plan)."\n"); exit(5);
}
echo '1957_TITLE_PLAN=' . wp_json_encode(array_map(static function($r){return ['id'=>$r['id'],'old'=>$r['old'],'new'=>$r['new']];},$plan),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
echo '1957_VARIATION_COUNT=' . count($children_plan) . "\n";
foreach (array_merge($plan,$children_plan) as $item) {
 if ($item['old']===$item['new']) continue;
 $res=wp_update_post(wp_slash(['ID'=>$item['id'],'post_title'=>$item['new']]),true);
 if (is_wp_error($res) || !$res) {
   fwrite(STDERR,"1957_ABORT_UPDATE_FAILED id={$item['id']}\n");exit(6);
 }
}
$errors=[];
foreach (array_merge($plan,$children_plan) as $item) {
 clean_post_cache($item['id']);
 $actual=get_post_field('post_title',$item['id'],'raw');
 if ($actual!==$item['new']) $errors[]=$item['id'];
}
if ($errors) { fwrite(STDERR,'1957_ABORT_VERIFY_IDS='.implode(',',$errors)."\n");exit(7); }
echo "1957_TITLE_UPDATE_COMPLETE parents=3 variations=".count($children_plan)." verified=1\n";
