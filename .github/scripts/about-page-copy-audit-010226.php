<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function emdo_cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
function emdo_callbacks($hook){global $wp_filter; if(empty($wp_filter[$hook])) return []; $out=[]; foreach($wp_filter[$hook]->callbacks as $pri=>$callbacks){foreach($callbacks as $cb){$f=$cb['function']; if(is_string($f))$name=$f; elseif(is_array($f)){$name=(is_object($f[0])?get_class($f[0]):(string)$f[0]).'::'.$f[1];} elseif($f instanceof Closure)$name='Closure'; else $name=gettype($f); $out[]=$pri.':'.$name;}} return $out;}
$root=ABSPATH;
$theme=wp_get_theme('woostify');
echo "=== WOOSTIFY VERSION ===\n".$theme->get('Version')."\n";
$files=[
$root.'wp-content/themes/woostify/inc/woocommerce/class-woostify-adjacent-products.php',
$root.'wp-content/themes/woostify/inc/woocommerce/woostify-woocommerce-single-product-functions.php'
];
foreach($files as $f){echo "=== FILE ".basename($f)." ===\n"; if(is_readable($f)) echo emdo_cmd('nl -ba '.escapeshellarg($f).' | head -n 220')."\n"; else echo "NOT_READABLE\n";}
$hooks=['get_previous_post_where','get_next_post_where','get_previous_post_join','get_next_post_join','get_previous_post_sort','get_next_post_sort','get_object_terms','wp_get_object_terms_args','terms_clauses'];
echo "=== RELEVANT FILTER CALLBACKS ===\n"; foreach($hooks as $h){echo $h.' '.json_encode(emdo_callbacks($h),JSON_UNESCAPED_SLASHES)."\n";}
global $wpdb;
echo "=== TABLE COUNTS ===\n";
foreach(['posts','postmeta','term_relationships','term_taxonomy','terms'] as $t){$tn=$wpdb->$t; $n=$wpdb->get_var("SELECT COUNT(*) FROM $tn"); echo "$t $n\n";}
echo 'published_products '.$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'")."\n";
echo "=== INDEXES ===\n";
foreach([$wpdb->posts,$wpdb->term_relationships,$wpdb->term_taxonomy,$wpdb->postmeta] as $tn){echo "-- $tn --\n"; $rows=$wpdb->get_results("SHOW INDEX FROM $tn",ARRAY_A); foreach($rows as $r){echo implode('|',[$r['Key_name'],$r['Seq_in_index'],$r['Column_name'],$r['Cardinality']])."\n";}}
$ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY post_date DESC LIMIT 3");
echo "=== SAMPLE TIMINGS ===\n";
foreach($ids as $id){$GLOBALS['post']=get_post($id); setup_postdata($GLOBALS['post']); $t=microtime(true); $cats=wp_get_object_terms($id,'product_cat',['fields'=>'ids']); $dt=microtime(true)-$t; echo "PRODUCT $id cats=".count((array)$cats)." terms_s=".sprintf('%.4f',$dt)."\n"; foreach([[false,''],[true,'product_cat']] as $mode){$same=$mode[0];$tax=$mode[1]?:'category'; $capt=[]; $fw=function($where)use(&$capt){$capt['where']=$where;return $where;};$fj=function($join)use(&$capt){$capt['join']=$join;return $join;};$fs=function($sort)use(&$capt){$capt['sort']=$sort;return $sort;}; add_filter('get_previous_post_where',$fw,PHP_INT_MAX);add_filter('get_previous_post_join',$fj,PHP_INT_MAX);add_filter('get_previous_post_sort',$fs,PHP_INT_MAX);$t=microtime(true);$p=get_adjacent_post($same,'',$tax,true);$dt=microtime(true)-$t;remove_filter('get_previous_post_where',$fw,PHP_INT_MAX);remove_filter('get_previous_post_join',$fj,PHP_INT_MAX);remove_filter('get_previous_post_sort',$fs,PHP_INT_MAX);echo ' prev same='.(int)$same.' tax='.$tax.' s='.sprintf('%.4f',$dt).' result='.(is_object($p)?$p->ID:0)."\n";echo ' SQL '.preg_replace('/\s+/',' ',($capt['join']??'').' '.($capt['where']??'').' '.($capt['sort']??''))."\n";}}
wp_reset_postdata();
echo "=== SLOWLOG TAIL ===\n";
$slow='/var/www/vhosts/system/elmercadodeorigen.com/logs/php-fpm_slow.log'; if(is_readable($slow)) echo emdo_cmd('tail -n 100 '.escapeshellarg($slow))."\n";
