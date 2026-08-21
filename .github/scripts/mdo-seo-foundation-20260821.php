<?php
/**
 * Plugin Name: EMDO SEO Foundation
 * Description: Stable SEO titles, descriptions, canonicals and legacy redirects for El Mercado de Origen.
 * Version: 2026.08.21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_seo_path(): string {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return $path !== '' ? '/' . ltrim( $path, '/' ) : '/';
}

function emdo_seo_is_en(): bool {
    return (bool) preg_match( '#^/en(?:/|$)#i', emdo_seo_path() );
}

function emdo_seo_term_name(): string {
    $term = get_queried_object();
    return $term instanceof WP_Term ? trim( wp_strip_all_tags( (string) $term->name ) ) : '';
}

function emdo_seo_clean_term_name( string $name ): string {
    $name = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $name ) ) );
    return $name;
}

function emdo_seo_title_for_request( string $current ): string {
    $path = emdo_seo_path();
    $en   = emdo_seo_is_en();

    if ( $en ) {
        if ( $path === '/en/' || $path === '/en' ) {
            return 'Spanish Food from Selected Producers | El Mercado de Origen';
        }
        if ( preg_match( '#^/en/(?:shop|tienda)/?$#i', $path ) ) {
            return 'Shop Spanish Food Online | El Mercado de Origen';
        }
        if ( preg_match( '#^/en/(?:journal|blog)/?$#i', $path ) ) {
            return 'Spanish Food Journal | El Mercado de Origen';
        }
        if ( preg_match( '#^/en/(?:producers|productores)/?$#i', $path ) ) {
            return 'Spanish Producers | El Mercado de Origen';
        }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $name = emdo_seo_clean_term_name( emdo_seo_term_name() );
            if ( $name !== '' ) {
                return sprintf( 'Shop %s Online | El Mercado de Origen', $name );
            }
        }
        return $current;
    }

    if ( is_front_page() ) {
        return 'Productos españoles de origen | El Mercado de Origen';
    }
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return 'Comprar productos españoles online | El Mercado de Origen';
    }
    if ( is_page( 'productores' ) ) {
        return 'Productores españoles | El Mercado de Origen';
    }
    if ( is_home() || is_page( 'blog' ) ) {
        return 'Guías de productos y productores | El Mercado de Origen';
    }
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $name = emdo_seo_clean_term_name( emdo_seo_term_name() );
        if ( $name !== '' ) {
            return sprintf( 'Comprar %s online | El Mercado de Origen', $name );
        }
    }
    return $current;
}

function emdo_seo_description_for_request( string $current ): string {
    $path = emdo_seo_path();
    $en   = emdo_seo_is_en();

    if ( $en ) {
        if ( $path === '/en/' || $path === '/en' ) {
            return 'Discover selected Spanish food chosen for its origin, quality and producer. Buy directly from trusted producers at El Mercado de Origen.';
        }
        if ( preg_match( '#^/en/(?:shop|tienda)/?$#i', $path ) ) {
            return 'Shop Spanish food directly from selected producers: meat, Iberian ham, cured meats, olive oil, pulses, vegetables and more.';
        }
        if ( preg_match( '#^/en/(?:journal|blog)/?$#i', $path ) ) {
            return 'Guides and stories about Spanish food, producers, origin and craftsmanship to help you choose products with better information.';
        }
        if ( preg_match( '#^/en/(?:producers|productores)/?$#i', $path ) ) {
            return 'Meet the Spanish producers behind the products at El Mercado de Origen and discover what they make, where they work and their products.';
        }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $name = emdo_seo_clean_term_name( emdo_seo_term_name() );
            if ( $name !== '' ) {
                return sprintf( 'Shop %s directly from selected producers. Discover their origin, who makes them and order online at El Mercado de Origen.', $name );
            }
        }
        return $current;
    }

    if ( is_front_page() ) {
        return 'Descubre productos españoles seleccionados por su origen, calidad y productor. Compra directamente a productores de confianza en El Mercado de Origen.';
    }
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return 'Compra alimentos y productos españoles directamente a productores seleccionados: carnes, jamones, embutidos, aceites, legumbres, verduras y más.';
    }
    if ( is_page( 'productores' ) ) {
        return 'Conoce a los productores de El Mercado de Origen, descubre dónde elaboran, qué hace especial su trabajo y compra sus productos directamente online.';
    }
    if ( is_home() || is_page( 'blog' ) ) {
        return 'Guías y artículos sobre productos españoles, origen, productores y elaboración para ayudarte a elegir mejor antes de comprar.';
    }
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $name = emdo_seo_clean_term_name( emdo_seo_term_name() );
        if ( $name !== '' ) {
            return sprintf( 'Compra %s online directamente a productores seleccionados. Conoce su origen, quién los elabora y recibe tu pedido en casa.', mb_strtolower( $name, 'UTF-8' ) );
        }
    }
    return $current;
}

// AIOSEO is the active SEO plugin in production. These documented filters keep the
// rules persistent and avoid duplicating SEO tags in wp_head.
add_filter( 'aioseo_title', static function ( $title ) {
    return emdo_seo_title_for_request( (string) $title );
}, PHP_INT_MAX );

add_filter( 'aioseo_description', static function ( $description ) {
    return emdo_seo_description_for_request( (string) $description );
}, PHP_INT_MAX );

// Correct the English home canonical. TranslatePress serves the public home at /en/
// while AIOSEO can otherwise resolve the translated backing page (/en/home-2/).
add_filter( 'aioseo_canonical_url', static function ( $url ) {
    $path = emdo_seo_path();
    if ( $path === '/en/' || $path === '/en' ) {
        return untrailingslashit( home_url( '/' ) ) . '/en/';
    }
    return $url;
}, PHP_INT_MAX );

// Google still knows an obsolete Black Friday homepage URL. Keep the old content in
// WordPress untouched, but consolidate any residual signals into the current homepage.
add_action( 'template_redirect', static function (): void {
    $path = emdo_seo_path();
    if ( $path === '/inicio-bf/' || $path === '/inicio-bf' ) {
        wp_safe_redirect( home_url( '/' ), 301, 'EMDO SEO' );
        exit;
    }
    if ( $path === '/en/inicio-bf/' || $path === '/en/inicio-bf' ) {
        wp_safe_redirect( untrailingslashit( home_url( '/' ) ) . '/en/', 301, 'EMDO SEO' );
        exit;
    }
}, -1000 );

// If a theme/plugin asks WordPress for a document title outside AIOSEO, keep the key
// landing pages consistent without changing the visible H1/page names.
add_filter( 'pre_get_document_title', static function ( $title ) {
    if ( ! is_string( $title ) ) { $title = ''; }
    $seo = emdo_seo_title_for_request( $title );
    return $seo !== $title ? $seo : $title;
}, PHP_INT_MAX );
