<?php
/**
 * Plugin Name: MDO GSC Commerce SEO 2026-09-29
 * Description: Data-led SERP titles and descriptions for high-impression commerce URLs from Google Search Console.
 * Version: 2026.09.30.2
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
        '/categoria-producto/aceites/' => array(
            'title' => 'Comprar Aceite de Oliva Virgen Extra | El Mercado de Origen',
            'description' => 'Compra aceite de oliva virgen extra directamente a productores seleccionados. Compara formatos, precios y opciones disponibles con origen claro.',
        ),
        '/producto/sobrasada-de-bellota-100-iberica/' => array(
            'title' => 'Comprar Sobrasada de Bellota 100% Ibérica',
            'description' => 'Compra sobrasada de bellota 100% ibérica directamente al productor. Consulta precio, formato, características y disponibilidad actual.',
        ),
        '/producto/filetes-aguja-de-ternera/' => array(
            'title' => 'Filetes de Aguja de Ternera | 1 kg | Tolecarnes',
            'description' => 'Filetes de aguja de ternera jugosos y sabrosos, en bandeja de 1 kg y envasados al vacío. Ideales para sartén, plancha o parrilla.',
        ),
        '/producto/pito-de-vacuno-fileteado/' => array(
            'title' => 'Comprar Pito de Vacuno Fileteado | Tolecarnes',
            'description' => 'Pito de vacuno fileteado, un corte próximo a la entraña, sabroso y jugoso. Se entrega en filetes listos para sartén, plancha o parrilla.',
        ),
        '/producto/jamon-de-bellota-100-iberico/' => array(
            'title' => 'Comprar Jamón de Bellota 100% Ibérico | Brida Negra',
            'description' => 'Jamón de bellota 100% ibérico de brida negra, criado en libertad y alimentado en montanera. Consulta pesos, formatos y precio disponible.',
        ),
        '/producto/jamon-de-bellota-50-iberico-brida-roja/' => array(
            'title' => 'Comprar Jamón de Bellota 50% Ibérico | Brida Roja',
            'description' => 'Jamón de bellota 50% ibérico de brida roja, criado en libertad y alimentado durante la montanera. Consulta pesos, formatos y precio disponible.',
        ),
        '/producto/tapilla-o-picana-de-ternera/' => array(
            'title' => 'Comprar Picaña de Ternera | Tapilla de Vacuno',
            'description' => 'Compra tapilla o picaña de ternera directamente a Tolecarnes. Consulta el formato, precio, disponibilidad y la información del corte antes de pedir.',
        ),
        '/producto/jamon-cebo-de-campo-iberico-50-montjam/' => array(
            'title' => 'Comprar Jamón de Cebo de Campo Ibérico 50% | Montjam',
            'description' => 'Jamón de cebo de campo ibérico 50% Montjam, brida verde y curación mínima de 24 meses. Consulta peso, formato, precio y disponibilidad.',
        ),
        '/en/product/beef-tenderloin/' => array(
            'title' => 'Buy Beef Tenderloin | Price per Kg | Tolecarnes',
        ),
        '/en/product/extra-virgin-olive-oil-15-x-1l/' => array(
            'title' => 'Buy Extra Virgin Olive Oil 15 × 1L | Free Shipping',
        ),
        '/en/product/mature-beef-entrecote/' => array(
            'title' => 'Buy Mature Beef Entrecôte | 20+ Day Aged Beef',
        ),
        '/en/product/padron-peppers-kg/' => array(
            'title' => 'Buy Fresh Padrón Peppers by the Kg',
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


/**
 * Recover authority from high-impression legacy commerce URLs that currently
 * resolve as 404. Only explicit, verified one-to-one replacements are listed.
 */
function mdo_gsc_commerce_legacy_redirects_20260929(): void {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    $path = mdo_gsc_commerce_path_20260929();
    $redirects = array(
        '/en/product/bag-of-approximately-300-gr-padron-peppers/' => '/en/product/padron-peppers-kg/',
    );

    if ( ! isset( $redirects[ $path ] ) ) {
        return;
    }

    wp_safe_redirect( home_url( $redirects[ $path ] ), 301, 'MDO GSC commerce legacy redirect' );
    exit;
}
add_action( 'template_redirect', 'mdo_gsc_commerce_legacy_redirects_20260929', -5000 );
