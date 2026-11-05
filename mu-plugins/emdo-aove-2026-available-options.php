<?php
/**
 * Plugin Name: EMDO AOVE 2026/27 - only purchasable variation selectors
 * Description: Narrow frontend-only correction; does not mutate variations or global terms.
 * Version: 2026.11.05.1
 */
if ( ! defined('ABSPATH') ) { exit; }

function emdo_aove_2627_target_product_20261105( $product ): bool {
    if ( ! $product instanceof WC_Product_Variable ) { return false; }
    $slug = (string) get_post_field('post_name', $product->get_id());
    return in_array($slug, array(
        'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio',
        'sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l',
        'aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27',
    ), true);
}

/** Return only attributes from active and purchasable variations, by slug. */
function emdo_aove_2627_active_options_20261105( $product ): array {
    if ( ! emdo_aove_2627_target_product_20261105($product) ) { return array(); }
    $allowed = array();
    foreach ($product->get_children() as $child_id) {
        $variation = wc_get_product($child_id);
        if ( ! $variation || $variation->get_status()!=='publish'
             || ! $variation->is_purchasable() || ! $variation->is_in_stock() ) { continue; }
        foreach ($variation->get_attributes() as $name=>$slug) {
            if (!in_array($name,array('pa_tamano','pa_variedad'),true) || !is_string($slug) || $slug==='') { continue; }
            $allowed[$name][$slug] = true;
        }
    }
    return array_map('array_keys', $allowed);
}

/** Keep WooCommerce variable form and JS intact; reduce only unrelated choices. */
add_filter('woocommerce_dropdown_variation_attribute_options_args', static function ($args) {
    if ( is_admin() || ! is_product() || empty($args['product']) || empty($args['attribute']) ) { return $args; }
    $product = $args['product'];
    if ( ! emdo_aove_2627_target_product_20261105($product) ) { return $args; }
    $attr = (string)$args['attribute'];
    if (!in_array($attr,array('pa_tamano','pa_variedad'),true)) { return $args; }
    $allowed = emdo_aove_2627_active_options_20261105($product);
    if (empty($allowed[$attr])) { return $args; } // Fail open: never empty valid buying options.
    $options = !empty($args['options']) ? (array)$args['options'] :
        (array)($product->get_variation_attributes()[$attr] ?? array());
    $filtered = array_values(array_filter($options, static fn($v) => in_array((string)$v, $allowed[$attr], true)));
    if ($filtered) { $args['options']=$filtered; }
    return $args;
}, 20, 1);

/** Fix the publicly displayed 'Información adicional' too, without editing any product term. */
add_filter('woocommerce_display_product_attributes', static function($rows, $product) {
    if (is_admin() || !emdo_aove_2627_target_product_20261105($product)) { return $rows; }
    $allowed = emdo_aove_2627_active_options_20261105($product);
    foreach (array('pa_tamano','pa_variedad') as $attr) {
        $key='attribute_'.$attr;
        if (empty($rows[$key]) || empty($allowed[$attr])) {continue;}
        $labels=array();
        foreach($allowed[$attr] as $slug) {
            $term=get_term_by('slug',$slug,$attr);
            $labels[]= $term instanceof WP_Term ? $term->name : $slug;
        }
        $rows[$key]['value']=esc_html(implode(', ',array_unique($labels)));
    }
    return $rows;
}, 20, 2);
