<?php
/**
 * Plugin Name: EMDO Commercial Vegetables Description Deduplication
 * Description: Retains the bespoke persisted category copy but suppresses WooCommerce's second rendering on the vegetables archive.
 * Version: 2026.10.15.1
 */
if (!defined('ABSPATH')) exit;
add_action('wp',static function():void{
    if (!function_exists('is_product_category') || !is_product_category('hortalizas-verduras')) return;
    if (!function_exists('emdo_category_seo_current_description') ||
        emdo_category_seo_current_description()==='') return;
    remove_action('woocommerce_archive_description','woocommerce_taxonomy_archive_description',10);
},20);
