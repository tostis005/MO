<?php
/**
 * Plugin Name: EMDO - WooCommerce ficha normal para packs exclusivos
 * Description: Evita solo en seis fichas exclusivas la busqueda masiva de anterior/siguiente del tema Woostify.
 * Version: 1.1.0
 */
if (!defined('ABSPATH')) { exit; }
/**
 * This deliberately does NOT replace the product template, header, gallery,
 * price or variations. It removes only the expensive adjacent-navigation hook
 * for six approved products near the end of a large catalog.
 */
function emdo_disable_costly_adjacent_navigation_for_pack_20261028() {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)
        || !function_exists('is_singular') || !is_singular('product')) { return; }
    $id=(int)get_queried_object_id();
    if (!in_array($id,[21814,21818,21820,21904,21906,21908],true)) { return; }
    remove_action('woostify_content_top','woostify_product_navigation',50);
}
add_action('wp','emdo_disable_costly_adjacent_navigation_for_pack_20261028',20);
add_action('template_redirect','emdo_disable_costly_adjacent_navigation_for_pack_20261028',2);

/**
 * Keep internal marketplace-specific SKUs intact in WooCommerce/API, but do
 * not display them to visitors reviewing the normal product detail page.
 * Product identifiers, export mapping and variations remain unchanged.
 */
add_filter('wc_product_sku_enabled',static function($enabled){
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)
        || (defined('WP_CLI') && WP_CLI)
        || !function_exists('is_singular') || !is_singular('product')) {return $enabled;}
    return in_array((int)get_queried_object_id(),[21814,21818,21820,21904,21906,21908],true) ? false : $enabled;
},1000);
