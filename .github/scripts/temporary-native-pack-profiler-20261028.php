<?php
/**
 * Temporary per-request profiler for one approved pack on a native product URL.
 * Log file stores only lifecycle markers/elapsed milliseconds, no visitor data.
 * The deployment workflow must remove this MU plugin and its log.
 */
if ( ! defined('ABSPATH') ) { return; }
if ((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) !== '/producto/pack-degustacion-pasaporte-salvaje/') { return; }
if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { return; }
$GLOBALS['mdo_pack_profile_start_20261028'] = microtime(true);
$GLOBALS['mdo_pack_profile_file_20261028'] = WP_CONTENT_DIR . '/uploads/mdo-pack-native-profile-20261028.log';
function mdo_pack_native_profile_20261028($step) {
    $delta = round(1000 * (microtime(true)-($GLOBALS['mdo_pack_profile_start_20261028']??microtime(true))));
    $entry = sprintf("%06dms %s\n", $delta, preg_replace('/[^A-Za-z0-9_-]/','',(string)$step));
    @file_put_contents($GLOBALS['mdo_pack_profile_file_20261028'], $entry, FILE_APPEND | LOCK_EX);
}
mdo_pack_native_profile_20261028('MU_START');
foreach(['muplugins_loaded','plugins_loaded','after_setup_theme','init','wp_loaded','parse_request','parse_query','wp','template_redirect','get_header','wp_head','woocommerce_before_main_content','woocommerce_before_single_product','woocommerce_before_single_product_summary','woocommerce_single_product_summary','woocommerce_after_single_product_summary','woocommerce_after_single_product','get_footer','wp_footer','shutdown'] as $hook) {
    add_action($hook, static function ()use($hook) {mdo_pack_native_profile_20261028('BEFORE_'.$hook);}, -100000, 0);
    add_action($hook, static function ()use($hook) {mdo_pack_native_profile_20261028('AFTER_'.$hook);}, 100000, 0);
}
foreach(['the_posts','template_include','woocommerce_product_is_visible','woocommerce_product_get_stock_status','woocommerce_product_is_in_stock','get_the_terms','wpseo_sitemap_entry'] as $hook) {
    add_filter($hook, static function ($value)use($hook) {
       static $times = [];
       $times[$hook] = ($times[$hook]??0)+1;
       if (in_array($times[$hook],[1,10,100,1000,10000],true))mdo_pack_native_profile_20261028('FILTER_'.$hook.'_'.$times[$hook]);
       return $value;
    }, 100000, 1);
}
register_shutdown_function(static function() {
    $err = error_get_last();
    $fatal = $err && in_array((int)$err['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true);
    mdo_pack_native_profile_20261028($fatal?'SHUTDOWN_FATAL':'SHUTDOWN_NORMAL');
});
