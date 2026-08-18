<?php
/**
 * Plugin Name: MDO Footer Legal Integration
 * Description: Integrates legal/trust links into the existing footer menu and removes the separate Merchant transparency footer block.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The Merchant Transparency MU-plugin originally added a second standalone footer
 * section containing the operator's identity. Keep the identity in the relevant
 * legal/contact pages, but remove that extra visible footer block.
 */
add_action( 'plugins_loaded', static function() {
    global $wp_filter;

    if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
        return;
    }

    foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback ) {
            $function = $callback['function'] ?? null;
            if ( ! $function instanceof Closure ) {
                continue;
            }

            try {
                $reflection = new ReflectionFunction( $function );
                $file = (string) $reflection->getFileName();
            } catch ( Throwable $e ) {
                continue;
            }

            if ( basename( $file ) === 'mdo-merchant-transparency-20260818.php' && (int) $priority === 50 ) {
                remove_action( 'wp_footer', $function, (int) $priority );
            }
        }
    }
}, PHP_INT_MAX );

function mdo_fli_is_english(): bool {
    if ( function_exists( 'mdo_mt_is_english' ) ) {
        return (bool) mdo_mt_is_english();
    }
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    return $path === '/en' || strpos( $path, '/en/' ) === 0;
}

function mdo_fli_url( string $es_slug, string $en_slug, bool $en ): string {
    if ( $en ) {
        return home_url( '/en/' . trim( $en_slug, '/' ) . '/' );
    }
    return home_url( '/' . trim( $es_slug, '/' ) . '/' );
}

/**
 * Detect the site's existing legal footer menu by its current Terms + Cookies links,
 * then append the missing trust links using the same <li> structure. This avoids a
 * second footer bar and keeps the visual layout owned by the theme.
 */
add_filter( 'wp_nav_menu_items', static function( $items, $args ) {
    if ( is_admin() || ! is_string( $items ) || $items === '' ) {
        return $items;
    }

    $has_cookies = (bool) preg_match( '~(?:politica-de-cookies|cookie-policy|>\s*(?:Política de cookies|Cookie Policy|Cookies)\s*<)~iu', $items );
    $has_terms   = (bool) preg_match( '~(?:/politica/|terms-and-conditions|>\s*(?:Términos(?: y condiciones)?|Terms(?: and Conditions)?)\s*<)~iu', $items );

    if ( ! $has_cookies || ! $has_terms ) {
        return $items;
    }

    $en = mdo_fli_is_english();
    $links = $en ? array(
        'Contact'           => mdo_fli_url( 'contacto', 'contact', true ),
        'Legal Notice'      => mdo_fli_url( 'aviso-legal', 'legal-notice', true ),
        'Shipping'          => mdo_fli_url( 'envios', 'shipping', true ),
        'Returns & Refunds' => mdo_fli_url( 'devoluciones-y-reembolsos', 'returns-refunds', true ),
        'Privacy Policy'    => mdo_fli_url( 'politica-de-privacidad', 'privacy-policy', true ),
    ) : array(
        'Contacto'                   => mdo_fli_url( 'contacto', 'contact', false ),
        'Aviso legal'                => mdo_fli_url( 'aviso-legal', 'legal-notice', false ),
        'Envíos'                     => mdo_fli_url( 'envios', 'shipping', false ),
        'Devoluciones y reembolsos'  => mdo_fli_url( 'devoluciones-y-reembolsos', 'returns-refunds', false ),
        'Política de privacidad'     => mdo_fli_url( 'politica-de-privacidad', 'privacy-policy', false ),
    );

    foreach ( $links as $label => $url ) {
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( $path !== '' && strpos( $items, $path ) !== false ) {
            continue;
        }

        $items .= '<li class="menu-item menu-item-type-custom mdo-footer-legal-link"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
    }

    return $items;
}, PHP_INT_MAX, 2 );
