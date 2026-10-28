<?php
/**
 * Selector visual de molienda, limitado a productos variables de Ikore Kofi.
 * Mantiene el campo de variación WooCommerce como única fuente de verdad.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_ikore_molienda_product_20261021() {
    if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) { return false; }
    $id = (int) get_queried_object_id();
    $product = $id ? wc_get_product( $id ) : false;
    if ( ! $product || ! $product->is_type( 'variable' ) ) { return false; }
    $attributes = $product->get_variation_attributes();
    $has_grind = false;
    foreach ( array_keys( $attributes ) as $attribute ) {
        if ( false !== strpos( remove_accents( strtolower( (string) $attribute ) ), 'molienda' ) ) { $has_grind = true; break; }
    }
    if ( ! $has_grind ) { return false; }

    $vendor_id = function_exists( 'wcfm_get_vendor_id_by_post' ) ? absint( wcfm_get_vendor_id_by_post( $id ) ) : 0;
    if ( ! $vendor_id ) { $vendor_id = absint( get_post_field( 'post_author', $id ) ); }
    $name = '';
    if ( $vendor_id && function_exists( 'wcfm_get_vendor_store_name' ) ) {
        $name = (string) wcfm_get_vendor_store_name( $vendor_id );
    }
    if ( '' === trim( $name ) && $vendor_id ) {
        $vendor = get_userdata( $vendor_id );
        $name = $vendor ? (string) $vendor->display_name : '';
    }
    if ( preg_match( '/\bikore\s+kofi\b/i', remove_accents( $name ) ) ) { return true; }

    // La convención de slugs de este productor también identifica fichas
    // migradas que aún no tienen la relación explícita de WCFM.
    return 0 === strpos( (string) get_post_field( 'post_name', $id ), 'cafe-ikore-kofi-' );
}

add_action( 'wp_enqueue_scripts', function () {
    if ( ! emdo_ikore_molienda_product_20261021() ) { return; }
    $base = content_url( '/mu-plugins/ikore-molienda-assets/' );
    $version = '20261028.1';
    wp_enqueue_style( 'emdo-ikore-molienda', $base . 'selector.css', array(), $version );
    wp_enqueue_script( 'emdo-ikore-molienda', $base . 'selector.js', array( 'jquery', 'wc-add-to-cart-variation' ), $version, true );
    $is_en = 0 === strpos( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/en/' );
    wp_add_inline_script( 'emdo-ikore-molienda', 'window.EMDOIkoreMolienda=' . wp_json_encode( array(
        'assetBase' => $base,
        'legend' => $is_en ? 'How would you like your coffee?' : '¿Cómo quieres recibir tu café?',
        'help' => $is_en ? 'Choose the type of coffee maker you use.' : 'Selecciona el tipo de cafetera que utilizas.',
        'labels' => $is_en ? array(
            'grano' => 'Whole beans', 'italiana' => 'Moka pot', 'espresso' => 'Espresso',
            'francesa' => 'French press', 'cold-brew' => 'Cold brew', 'aeropress' => 'AeroPress',
            'filtro-v60' => 'Filter / V60',
        ) : array(
            'grano' => 'En grano', 'italiana' => 'Italiana', 'espresso' => 'Espresso',
            'francesa' => 'Prensa francesa', 'cold-brew' => 'Cold brew', 'aeropress' => 'AeroPress',
            'filtro-v60' => 'Filtro y V60',
        ),
    ) ) . ';', 'before' );
}, 20 );
