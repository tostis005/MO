<?php
/**
 * Plugin Name: EMDO SEO Foundation
 * Description: Stable SEO titles, descriptions, canonicals, index controls and legacy redirects for El Mercado de Origen.
 * Version: 2026.08.21.3
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_seo_path(): string {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return $path !== '' ? '/' . ltrim( $path, '/' ) : '/';
}

function emdo_seo_root(): string {
    $root = untrailingslashit( (string) get_option( 'home' ) );
    // TranslatePress can expose an /en-suffixed home inside an English request.
    return untrailingslashit( (string) preg_replace( '#/en/?$#i', '', $root ) );
}

function emdo_seo_is_en(): bool {
    return (bool) preg_match( '#^/en(?:/|$)#i', emdo_seo_path() );
}

function emdo_seo_term_name(): string {
    $term = get_queried_object();
    return $term instanceof WP_Term ? trim( wp_strip_all_tags( (string) $term->name ) ) : '';
}

function emdo_seo_clean_term_name( string $name ): string {
    return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $name ) ) );
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

add_filter( 'aioseo_title', static function ( $title ) {
    return emdo_seo_title_for_request( (string) $title );
}, PHP_INT_MAX );

add_filter( 'aioseo_description', static function ( $description ) {
    return emdo_seo_description_for_request( (string) $description );
}, PHP_INT_MAX );

add_filter( 'aioseo_robots_meta', static function ( $attributes ) {
    if ( ! is_array( $attributes ) ) { return $attributes; }

    $noindex = false;
    $queried = get_queried_object();
    if ( $queried instanceof WP_Term && str_starts_with( (string) $queried->taxonomy, 'pa_' ) ) {
        $noindex = true;
    }
    if ( is_search() ) { $noindex = true; }
    if ( function_exists( 'is_cart' ) && is_cart() ) { $noindex = true; }
    if ( function_exists( 'is_checkout' ) && is_checkout() ) { $noindex = true; }
    if ( function_exists( 'is_account_page' ) && is_account_page() ) { $noindex = true; }

    if ( $noindex ) {
        $attributes['noindex'] = 'noindex';
        $attributes['nofollow'] = '';
    }
    return $attributes;
}, PHP_INT_MAX );

add_filter( 'aioseo_canonical_url', static function ( $url ) {
    $path = emdo_seo_path();
    if ( $path === '/en/' || $path === '/en' ) {
        return emdo_seo_root() . '/en/';
    }
    return $url;
}, PHP_INT_MAX );

add_filter( 'get_the_excerpt', static function ( $excerpt, $post ) {
    if ( ! emdo_seo_is_en() || ! preg_match( '#^/en/(?:journal|blog)/?$#i', emdo_seo_path() ) ) {
        return $excerpt;
    }
    $post_id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
    if ( $post_id < 1 ) { return $excerpt; }

    $translated = (string) get_post_meta( $post_id, '_en_US_post_excerpt', true );
    if ( $translated === '' ) {
        $translated = (string) get_post_meta( $post_id, '_en_US_post_content', true );
    }
    if ( $translated === '' ) { return $excerpt; }

    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $translated ) ) ) );
    return $text !== '' ? wp_trim_words( $text, 32, '…' ) : $excerpt;
}, PHP_INT_MAX, 2 );

add_action( 'template_redirect', static function (): void {
    $path = emdo_seo_path();
    if ( $path === '/inicio-bf/' || $path === '/inicio-bf' ) {
        wp_safe_redirect( emdo_seo_root() . '/', 301, 'EMDO SEO' );
        exit;
    }
    if ( $path === '/en/inicio-bf/' || $path === '/en/inicio-bf' ) {
        wp_safe_redirect( emdo_seo_root() . '/en/', 301, 'EMDO SEO' );
        exit;
    }
}, -1000 );

add_filter( 'pre_get_document_title', static function ( $title ) {
    if ( ! is_string( $title ) ) { $title = ''; }
    $seo = emdo_seo_title_for_request( $title );
    return $seo !== $title ? $seo : $title;
}, PHP_INT_MAX );
