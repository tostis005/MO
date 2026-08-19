<?php
/**
 * Plugin Name: MDO - Home WebP Delivery 2026-08-19
 * Description: Sirve en la Home variantes WebP ya generadas de imágenes locales sin alterar la biblioteca multimedia ni el resto de la tienda.
 * Version: 1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    return;
}

add_action(
    'template_redirect',
    static function (): void {
        if (
            ! function_exists( 'elmercado_is_optimized_home' )
            || ! function_exists( 'elmercado_home_cache_key' )
            || ! function_exists( 'elmercado_home_static_cache_file' )
            || ! elmercado_is_optimized_home()
        ) {
            return;
        }

        $version = '1.0.3';
        if ( get_option( 'mdo_home_webp_cache_version', '' ) === $version ) {
            return;
        }

        $uploads = wp_get_upload_dir();
        $probe   = trailingslashit( (string) $uploads['basedir'] ) . '2017/12/Garrafa_5L-600x800.webp';
        if ( ! is_readable( $probe ) ) {
            return;
        }

        delete_transient( elmercado_home_cache_key() );
        $static = elmercado_home_static_cache_file();
        if ( is_file( $static ) ) {
            @unlink( $static );
        }
        update_option( 'mdo_home_webp_cache_version', $version, false );
    },
    -3100
);

function mdo_home_webp_candidate_url( string $url ): ?string {
    static $cache = array();

    $url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    if ( '' === $url ) {
        return null;
    }
    if ( array_key_exists( $url, $cache ) ) {
        return $cache[ $url ];
    }

    $clean_url = preg_replace( '~[?#].*$~', '', $url ) ?? $url;
    if ( ! preg_match( '~\.(?:jpe?g|png)$~i', $clean_url ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $uploads   = wp_get_upload_dir();
    $url_path  = (string) wp_parse_url( $clean_url, PHP_URL_PATH );
    $base_path = (string) wp_parse_url( (string) $uploads['baseurl'], PHP_URL_PATH );
    if ( '' === $url_path || '' === $base_path || ! str_starts_with( $url_path, $base_path . '/' ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $relative = ltrim( rawurldecode( substr( $url_path, strlen( $base_path ) ) ), '/' );
    if ( '' === $relative || str_contains( $relative, '..' ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $source = trailingslashit( (string) $uploads['basedir'] ) . $relative;
    $webp   = preg_replace( '~\.(?:jpe?g|png)$~i', '.webp', $source );
    if ( ! is_string( $webp ) || ! is_readable( $source ) || ! is_readable( $webp ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $source_size = @filesize( $source );
    $webp_size   = @filesize( $webp );
    if ( ! is_int( $source_size ) || ! is_int( $webp_size ) || $webp_size <= 0 || $webp_size >= $source_size ) {
        $cache[ $url ] = null;
        return null;
    }

    $candidate = preg_replace( '~\.(?:jpe?g|png)(?=([?#]|$))~i', '.webp', $url, 1 );
    $cache[ $url ] = is_string( $candidate ) ? $candidate : null;
    return $cache[ $url ];
}

function mdo_home_webp_rewrite_srcset( string $srcset ): string {
    $candidates = array_map( 'trim', explode( ',', html_entity_decode( $srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
    $rewritten  = array();

    foreach ( $candidates as $candidate ) {
        if ( '' === $candidate ) {
            continue;
        }
        if ( ! preg_match( '~^(\S+)(\s+.+)?$~', $candidate, $matches ) ) {
            $rewritten[] = $candidate;
            continue;
        }
        $replacement = mdo_home_webp_candidate_url( $matches[1] );
        $rewritten[] = ( $replacement ?: $matches[1] ) . ( $matches[2] ?? '' );
    }

    return implode( ', ', $rewritten );
}

/**
 * Lighthouse mobile usa DPR 1.75: una tarjeta de 315 px necesita ~551 px.
 * La Home sólo tenía 360/600 para la garrafa y saltaba a 600. Si existe la
 * variante 552x736 WebP, se añade como candidato exacto sin tocar WooCommerce.
 */
function mdo_home_webp_add_garrafa_552_candidate( string $tag ): string {
    if ( false === strpos( $tag, 'Garrafa_5L' ) || false === strpos( $tag, 'srcset=' ) ) {
        return $tag;
    }

    $uploads = wp_get_upload_dir();
    $file    = trailingslashit( (string) $uploads['basedir'] ) . '2017/12/Garrafa_5L-552x736.webp';
    if ( ! is_readable( $file ) || filesize( $file ) <= 0 ) {
        return $tag;
    }

    $url = trailingslashit( (string) $uploads['baseurl'] ) . '2017/12/Garrafa_5L-552x736.webp';
    $tag = preg_replace_callback(
        '~(\ssrcset\s*=\s*)(["\'])(.*?)\2~i',
        static function ( array $matches ) use ( $url ): string {
            $srcset = html_entity_decode( $matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            if ( false === strpos( $srcset, 'Garrafa_5L-552x736.webp' ) ) {
                $srcset = rtrim( $srcset, " \t\n\r\0\x0B," ) . ', ' . $url . ' 552w';
            }
            return $matches[1] . $matches[2] . htmlspecialchars( $srcset, ENT_QUOTES, 'UTF-8' ) . $matches[2];
        },
        $tag,
        1
    ) ?? $tag;

    return $tag;
}

function mdo_home_webp_rewrite_img_tag( string $tag ): string {
    foreach ( array( 'src', 'data-src' ) as $attribute ) {
        $pattern = '~(\s' . preg_quote( $attribute, '~' ) . '\s*=\s*)(["\'])(.*?)\2~i';
        $tag = preg_replace_callback(
            $pattern,
            static function ( array $matches ): string {
                $replacement = mdo_home_webp_candidate_url( $matches[3] );
                $value       = $replacement ?: $matches[3];
                return $matches[1] . $matches[2] . htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) . $matches[2];
            },
            $tag,
            1
        ) ?? $tag;
    }

    foreach ( array( 'srcset', 'data-srcset' ) as $attribute ) {
        $pattern = '~(\s' . preg_quote( $attribute, '~' ) . '\s*=\s*)(["\'])(.*?)\2~i';
        $tag = preg_replace_callback(
            $pattern,
            static function ( array $matches ): string {
                return $matches[1] . $matches[2] . htmlspecialchars( mdo_home_webp_rewrite_srcset( $matches[3] ), ENT_QUOTES, 'UTF-8' ) . $matches[2];
            },
            $tag,
            1
        ) ?? $tag;
    }

    return mdo_home_webp_add_garrafa_552_candidate( $tag );
}

function mdo_home_webp_transform_html( string $html ): string {
    if ( '' === $html ) {
        return $html;
    }

    $html = preg_replace_callback(
        '~<img\b[^>]*>~i',
        static fn ( array $matches ): string => mdo_home_webp_rewrite_img_tag( $matches[0] ),
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '~url\(\s*(["\']?)([^)"\']+/wp-content/uploads/[^)"\']+\.(?:jpe?g|png)(?:\?[^)"\']*)?)\1\s*\)~i',
        static function ( array $matches ): string {
            $replacement = mdo_home_webp_candidate_url( $matches[2] );
            if ( ! $replacement ) {
                return $matches[0];
            }
            return 'url(' . $matches[1] . htmlspecialchars( $replacement, ENT_QUOTES, 'UTF-8' ) . $matches[1] . ')';
        },
        $html
    ) ?? $html;

    return $html;
}

function mdo_home_webp_finalize_document( string $html ): string {
    $optimized = mdo_home_webp_transform_html( $html );

    if (
        $optimized !== $html
        && function_exists( 'elmercado_can_cache_home_request' )
        && function_exists( 'elmercado_home_cache_key' )
        && function_exists( 'elmercado_home_static_cache_file' )
        && elmercado_can_cache_home_request()
        && false !== stripos( $optimized, '</html>' )
        && false === stripos( $optimized, 'wp-die-message' )
    ) {
        set_transient( elmercado_home_cache_key(), $optimized, 10 * MINUTE_IN_SECONDS );
        $file = elmercado_home_static_cache_file();
        if ( function_exists( 'elmercado_write_atomic_home_file' ) ) {
            elmercado_write_atomic_home_file( $file, $optimized );
        }
    }

    return $optimized;
}

add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
            return;
        }
        ob_start( 'mdo_home_webp_finalize_document' );
    },
    -3300
);
