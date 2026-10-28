<?php
/**
 * Plugin Name: EMDO - WooCommerce ficha normal para packs exclusivos
 * Description: Evita solo en seis fichas exclusivas la busqueda masiva de anterior/siguiente del tema Woostify.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) { exit; }
/**
 * This deliberately does NOT replace the product template, header, gallery,
 * price or variations. It removes only the expensive adjacent-navigation hook
 * for six approved products near the end of a large catalog.
 */
add_action('wp', static function () {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)
        || !function_exists('is_singular') || !is_singular('product')) { return; }
    $id=(int)get_queried_object_id();
    if (!in_array($id,[21814,21818,21820,21904,21906,21908],true)) { return; }
    remove_action('woostify_content_top','woostify_product_navigation',50);
}, 20);
