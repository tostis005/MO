<?php
/**
 * Plugin Name: MDO GSC Commerce SEO 2026-09-29
 * Description: Data-led SERP titles and descriptions for high-impression commerce URLs from Google Search Console.
 * Version: 2026.09.29.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public request path, preserving the original English route when available.
 */
function mdo_gsc_commerce_path_20260929(): string {
    if ( function_exists( 'mdoer_public_uri' ) ) {
        $uri = (string) mdoer_public_uri();
    } elseif ( isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
        $uri = (string) $GLOBALS['mdoer_public_request_uri'];
    } else {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    }

    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( '' === $path ) {
        return '/';
    }

    return '/' . ltrim( trailingslashit( untrailingslashit( rawurldecode( $path ) ) ), '/' );
}

/**
 * Targeted commerce copy. Paths are canonical/current URLs only; historical
 * aliases keep their existing redirects and are deliberately not reintroduced.
 */
function mdo_gsc_commerce_map_20260929(): array {
    return array(
        '/producto/sobrasada-de-bellota-100-iberica/' => array(
            'title' => 'Comprar Sobrasada de Bellota 100% Ibérica',
            'description' => 'Compra sobrasada de bellota 100% ibérica directamente al productor. Consulta precio, formato, características y disponibilidad actual.',
        ),
        '/producto/filetes-aguja-de-ternera/' => array(
            'title' => 'Filetes de Aguja de Ternera | 1 kg | Tolecarnes',
            'description' => 'Filetes de aguja de ternera jugosos y sabrosos, en bandeja de 1 kg y envasados al vacío. Ideales para sartén, plancha o parrilla.',
        ),
        '/producto/jamon-de-bellota-100-iberico/' => array(
            'title' => 'Comprar Jamón de Bellota 100% Ibérico | Brida Negra',
            'description' => 'Jamón de bellota 100% ibérico de brida negra, criado en libertad y alimentado en montanera. Consulta pesos, formatos y precio disponible.',
        ),
        '/producto/jamon-de-bellota-iberico-50-montjam/' => array(
            'title' => 'Jamón de Bellota Ibérico 50% Montjam | Brida Roja',
            'description' => 'Jamón de bellota ibérico 50% raza ibérica Montjam, brida roja y curación mínima de 32 meses en El Repilado, Huelva. Consulta formato y precio.',
        ),
        '/producto/tapilla-o-picana-de-ternera/' => array(
            'title' => 'Comprar Picaña de Ternera | Tapilla de Vacuno',
            'description' => 'Compra tapilla o picaña de ternera directamente a Tolecarnes. Consulta el formato, precio, disponibilidad y la información del corte antes de pedir.',
        ),
        '/en/product/mature-beef-entrecote/' => array(
            'title' => 'Buy Mature Beef Entrecôte | 20+ Day Aged Beef',
            'description' => 'Mature beef entrecôte aged for more than 20 days, supplied as vacuum-packed steaks. About four to five pieces per kilogram as a guide.',
        ),
        '/en/product/padron-peppers-kg/' => array(
            'title' => 'Buy Fresh Padrón Peppers by the Kg',
            'description' => 'Fresh Padrón peppers sold by the kilogram. Small, green peppers for frying or griddling; some may be hot while others are mild.',
        ),
    );
}

function mdo_gsc_commerce_target_20260929(): array {
    $map  = mdo_gsc_commerce_map_20260929();
    $path = mdo_gsc_commerce_path_20260929();

    return isset( $map[ $path ] ) ? $map[ $path ] : array();
}

function mdo_gsc_commerce_title_20260929( $current ): string {
    if ( is_admin() ) {
        return (string) $current;
    }

    $target = mdo_gsc_commerce_target_20260929();
    return ! empty( $target['title'] ) ? (string) $target['title'] : (string) $current;
}

function mdo_gsc_commerce_description_20260929( $current ): string {
    if ( is_admin() ) {
        return (string) $current;
    }

    $target = mdo_gsc_commerce_target_20260929();
    return ! empty( $target['description'] ) ? (string) $target['description'] : (string) $current;
}

/**
 * Register after normal plugins have loaded so these very narrow GSC overrides
 * run after generic AIOSEO/SEO-plugin callbacks.
 */
function mdo_gsc_commerce_register_20260929(): void {
    if ( is_admin() ) {
        return;
    }

    add_filter( 'aioseo_title', 'mdo_gsc_commerce_title_20260929', PHP_INT_MAX );
    add_filter( 'aioseo_description', 'mdo_gsc_commerce_description_20260929', PHP_INT_MAX );
    add_filter( 'wpseo_title', 'mdo_gsc_commerce_title_20260929', PHP_INT_MAX );
    add_filter( 'wpseo_metadesc', 'mdo_gsc_commerce_description_20260929', PHP_INT_MAX );
    add_filter( 'rank_math/frontend/title', 'mdo_gsc_commerce_title_20260929', PHP_INT_MAX );
    add_filter( 'rank_math/frontend/description', 'mdo_gsc_commerce_description_20260929', PHP_INT_MAX );
    add_filter( 'seopress_titles_title', 'mdo_gsc_commerce_title_20260929', PHP_INT_MAX );
    add_filter( 'seopress_titles_desc', 'mdo_gsc_commerce_description_20260929', PHP_INT_MAX );
    add_filter( 'pre_get_document_title', 'mdo_gsc_commerce_title_20260929', PHP_INT_MAX );
}
add_action( 'plugins_loaded', 'mdo_gsc_commerce_register_20260929', PHP_INT_MAX );
