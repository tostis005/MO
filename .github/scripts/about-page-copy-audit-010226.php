<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function emdo_cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
global $wpdb;
echo "=== NAV HOTFIX STATE ===\n";
$module=get_stylesheet_directory().'/inc/product-navigation-performance-010237.php';
$functions=get_stylesheet_directory().'/functions.php';
echo 'MODULE '.(is_file($module)?'present':'missing')."\n";
echo 'INCLUDE '.(strpos((string)file_get_contents($functions),"'inc/product-navigation-performance-010237.php'")!==false?'present':'missing')."\n";
echo 'FILTER_PREV '.(has_filter('get_previous_post_where','elmercado_product_navigation_adjacent_where_010237')!==false?'active':'missing')."\n";
echo 'FILTER_NEXT '.(has_filter('get_next_post_where','elmercado_product_navigation_adjacent_where_010237')!==false?'active':'missing')."\n";
$disabled=function_exists('elmercado_wcfm_disabled_vendor_ids_010210')?elmercado_wcfm_disabled_vendor_ids_010210():[];$disabled_sql=$disabled?implode(',',array_map('intval',$disabled)):'0';
$ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author NOT IN ($disabled_sql) ORDER BY post_date DESC LIMIT 5");
echo "=== FIVE ACTIVE PRODUCT NAV BENCHMARKS ===\n";
$first_url='';
foreach($ids as $id){$GLOBALS['post']=get_post((int)$id);setup_postdata($GLOBALS['post']);$t=microtime(true);$prev=woostify_get_prev_product();$p=microtime(true)-$t;$t=microtime(true);$next=woostify_get_next_product();$n=microtime(true)-$t;$t=microtime(true);ob_start();woostify_product_navigation();$html=ob_get_clean();$render=microtime(true)-$t;$url=get_permalink((int)$id);if($first_url==='')$first_url=$url;echo 'ID='.$id.' prev_ms='.round($p*1000,2).' next_ms='.round($n*1000,2).' render_ms='.round($render*1000,2).' html_bytes='.strlen($html).' prev_id='.($prev?$prev->get_id():0).' next_id='.($next?$next->get_id():0)."\n";}
wp_reset_postdata();
if($first_url!==''){
 echo "=== HTTP PRODUCT CHECKS ===\n";
 for($i=1;$i<=3;$i++){echo 'REQ'.$i.' '.emdo_cmd("curl -sS -L -o /tmp/emdo-nav-check.html --max-time 25 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' ".escapeshellarg($first_url.'?emdo-nav-verify='.$i))."\n";}
 $html=@file_get_contents('/tmp/emdo-nav-check.html');echo 'NAV_MARKUP '.(is_string($html)&&strpos($html,'woostify-product-navigation')!==false?'present':'missing')."\n";@unlink('/tmp/emdo-nav-check.html');
}
$slow='/var/www/vhosts/system/elmercadodeorigen.com/logs/php-fpm_slow.log';
echo "=== SLOWLOG RECENT NAV REFERENCES ===\n";
if(is_readable($slow)) echo emdo_cmd("tail -n 240 ".escapeshellarg($slow)." | grep -E '^[[]14-Aug-2026 09:(4[9]|5[0-9])|woostify_product_navigation|class-woostify-adjacent-products' | tail -n 80")."\n";
echo "VERIFY_OK\n";
