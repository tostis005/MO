<?php
/**
 * Temporary read-only performance tracing for exactly one native product URL.
 * Never logs query strings, customer data, IP, cookies, headers, post content.
 */
if(!defined('ABSPATH'))return;
$path=(string)wp_parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
if($path!=='/producto/pack-degustacion-pasaporte-salvaje/')return;
$GLOBALS['mdo_native_trace_start']=microtime(true);
function mdo_pack_trace($marker){
 $start=$GLOBALS['mdo_native_trace_start']??microtime(true);
 $line=sprintf("%.3f %s pid=%d mem=%.1fMB\n",microtime(true)-$start,$marker,getmypid(),memory_get_usage(true)/1048576);
 error_log('MDO_NATIVE_TRACE '.$line);
}
mdo_pack_trace('MU_FILE_LOADED');
foreach(['muplugins_loaded','plugins_loaded','init','wp_loaded','parse_request','pre_get_posts','the_posts','wp','template_redirect','template_include','get_header','wp_head','woocommerce_before_main_content','woocommerce_before_single_product','woocommerce_single_product_summary','woocommerce_after_single_product_summary','get_footer','wp_footer'] as $hook){
 if($hook==='pre_get_posts'||$hook==='the_posts')continue;
 add_action($hook,static function()use($hook){mdo_pack_trace('ACTION_'.$hook);},PHP_INT_MAX,0);
}
add_filter('template_include',static function($template){mdo_pack_trace('FILTER_TEMPLATE_INCLUDE');return $template;},PHP_INT_MAX,1);
add_filter('the_posts',static function($posts){mdo_pack_trace('FILTER_THE_POSTS_POSTCOUNT_'.count((array)$posts));return $posts;},PHP_INT_MAX,1);
register_shutdown_function(static function(){ $e=error_get_last();mdo_pack_trace('SHUTDOWN'.($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)?'_FATAL_'.$e['type']:'_NORMAL'));});
