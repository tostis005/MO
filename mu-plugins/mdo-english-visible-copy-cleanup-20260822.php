<?php
/**
 * Plugin Name: MDO English Visible Copy Cleanup
 * Description: Removes a small set of confirmed Spanish UI leaks from clean English public routes without altering Spanish content or stored editorial copy.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mdo_evc_public_path_20260822(): string {
    if ( function_exists( 'mdoer_public_path' ) ) {
        return (string) mdoer_public_path();
    }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    return (string) wp_parse_url( $uri, PHP_URL_PATH );
}

function mdo_evc_is_english_20260822(): bool {
    return 1 === preg_match( '#^/en(?:/|$)#i', mdo_evc_public_path_20260822() );
}

/** Producer directory cards contain a literal Spanish CTA in the WCFM markup. */
add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || wp_doing_ajax() || ! mdo_evc_is_english_20260822() ) {
            return;
        }

        $path = '/' . trim( mdo_evc_public_path_20260822(), '/' ) . '/';
        if ( '/en/producers/' !== strtolower( $path ) ) {
            return;
        }

        ob_start(
            static function ( string $html ): string {
                return (string) preg_replace(
                    array(
                        '#>\s*Visitar\s*<#iu',
                        '#>\s*Visitar\s+Tienda\s*<#iu',
                        '#>\s*Visit\s+Store\s*<#iu',
                    ),
                    '>Visit<',
                    $html
                );
            }
        );
    },
    20
);

/** Reading-time labels are dynamic ("6 min de lectura"), so gettext cannot match them. */
add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || wp_doing_ajax() || ! mdo_evc_is_english_20260822() ) {
            return;
        }

        $path = '/' . trim( mdo_evc_public_path_20260822(), '/' ) . '/';
        if ( 1 !== preg_match( '#^/en/journal(?:/page/[1-9][0-9]*)?/$#i', $path ) ) {
            return;
        }

        ob_start(
            static function ( string $html ): string {
                $html = (string) preg_replace( '#(?<![[:alpha:]])([0-9]+)\s+min\s+de\s+lectura(?![[:alpha:]])#iu', '$1 min read', $html );
                return str_replace( array( '>Leer el artículo<', '>Leer el articulo<' ), '>Read the article<', $html );
            }
        );
    },
    21
);

/** Core/WooCommerce product tab title can bypass the persisted UI-copy map. */
add_filter(
    'woocommerce_product_tabs',
    static function ( array $tabs ): array {
        if ( ! mdo_evc_is_english_20260822() ) {
            return $tabs;
        }
        if ( isset( $tabs['description'] ) && is_array( $tabs['description'] ) ) {
            $tabs['description']['title'] = 'Description';
        }
        if ( isset( $tabs['additional_information'] ) && is_array( $tabs['additional_information'] ) ) {
            $tabs['additional_information']['title'] = 'Additional information';
        }
        return $tabs;
    },
    PHP_INT_MAX
);

/** Translate WooCommerce attribute-archive labels using the already persisted UI map. */
function mdo_evc_english_attribute_label_20260822( string $label ): string {
    if ( ! mdo_evc_is_english_20260822() ) {
        return $label;
    }

    $map = function_exists( 'elmercado_manual_english_ui_map_010245' )
        ? elmercado_manual_english_ui_map_010245()
        : array(
            'Alimentación' => 'Feeding',
            'Calidad' => 'Quality',
            'Con DOP' => 'With PDO',
            'Curación' => 'Curing',
            'Denominación de origen' => 'Protected Designation of Origin',
            'Origen' => 'Origin',
            'Peso' => 'Weight',
            'Preparación' => 'Preparation',
            'Productor' => 'Producer',
            'Raza ibérica' => 'Iberian breed',
            'Tamaño' => 'Size',
            'Tipo de pieza' => 'Piece type',
            'Tipo de producto' => 'Product type',
            'Variedad' => 'Variety',
        );

    return isset( $map[ $label ] ) && is_string( $map[ $label ] ) && '' !== trim( $map[ $label ] )
        ? (string) $map[ $label ]
        : $label;
}

function mdo_evc_attribute_title_20260822( string $title ): string {
    if ( ! mdo_evc_is_english_20260822() ) {
        return $title;
    }

    $term = get_queried_object();
    if ( ! $term instanceof WP_Term || 0 !== strpos( (string) $term->taxonomy, 'pa_' ) ) {
        return $title;
    }

    if ( function_exists( 'wc_attribute_label' ) ) {
        $native = wc_attribute_label( (string) $term->taxonomy );
        $english = mdo_evc_english_attribute_label_20260822( (string) $native );
        if ( '' !== $native && '' !== $english && $native !== $english ) {
            $title = preg_replace( '#^' . preg_quote( $native, '#' ) . '\s*:\s*#iu', $english . ': ', $title ) ?: $title;
        }
    }

    return str_replace( array( 'Variedad:', 'Product Variedad' ), array( 'Variety:', 'Variety' ), $title );
}
add_filter( 'woocommerce_page_title', 'mdo_evc_attribute_title_20260822', PHP_INT_MAX );
add_filter( 'get_the_archive_title', 'mdo_evc_attribute_title_20260822', PHP_INT_MAX );
add_filter( 'single_term_title', 'mdo_evc_attribute_title_20260822', PHP_INT_MAX );

add_filter(
    'woocommerce_get_breadcrumb',
    static function ( array $crumbs ): array {
        if ( ! mdo_evc_is_english_20260822() ) {
            return $crumbs;
        }
        foreach ( $crumbs as &$crumb ) {
            if ( ! is_array( $crumb ) || ! isset( $crumb[0] ) ) {
                continue;
            }
            $label = (string) $crumb[0];
            $label = str_replace( 'Product Variedad', 'Variety', $label );
            $label = str_replace( 'Variedad', 'Variety', $label );
            $crumb[0] = $label;
        }
        unset( $crumb );
        return $crumbs;
    },
    PHP_INT_MAX
);

/** Last-resort exact tab-label cleanup, restricted to English product pages. */
add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || wp_doing_ajax() || ! mdo_evc_is_english_20260822() ) {
            return;
        }
        if ( 1 !== preg_match( '#^/en/product/[^/]+/?$#i', mdo_evc_public_path_20260822() ) ) {
            return;
        }
        ob_start(
            static function ( string $html ): string {
                return str_replace(
                    array( '>Descripción<', '>Informaci&oacute;n adicional<', '>Información adicional<' ),
                    array( '>Description<', '>Additional information<', '>Additional information<' ),
                    $html
                );
            }
        );
    },
    22
);
