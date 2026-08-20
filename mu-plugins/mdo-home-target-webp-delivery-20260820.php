<?php
/**
 * Plugin Name: MDO - Home Target WebP Delivery 2026-08-20
 * Description: Final Home-only pass for the three current Lighthouse image findings, after all other output buffers have rendered.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    return;
}

/**
 * Returns exact source/WebP URL pairs only when the WebP exists and is smaller.
 *
 * @return array<string,string>
 */
function mdo_home_target_webp_pairs_20260820(): array {
    static $pairs = null;
    if ( is_array( $pairs ) ) {
        return $pairs;
    }

    $pairs   = array();
    $uploads = wp_get_upload_dir();
    $basedir = trailingslashit( (string) $uploads['basedir'] );
    $baseurl = trailingslashit( (string) $uploads['baseurl'] );

    $targets = array(
        '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-450x300.jpg',
        '2026/08/Tolecarnes-fondo-400x400.jpg',
        '2026/08/Packs-lotes-300x400-1.jpg',
    );

    foreach ( $targets as $relative ) {
        $source = $basedir . $relative;
        $webp   = preg_replace( '~\.jpe?g$~i', '.webp', $source );
        if ( ! is_string( $webp ) || ! is_readable( $source ) || ! is_readable( $webp ) ) {
            continue;
        }

        $source_size = @filesize( $source );
        $webp_size   = @filesize( $webp );
        if ( ! is_int( $source_size ) || ! is_int( $webp_size ) || $webp_size <= 0 || $webp_size >= $source_size ) {
            continue;
        }

        $source_url           = $baseurl . str_replace( DIRECTORY_SEPARATOR, '/', $relative );
        $webp_url             = preg_replace( '~\.jpe?g$~i', '.webp', $source_url );
        if ( is_string( $webp_url ) ) {
            $pairs[ $source_url ] = $webp_url;
        }
    }

    return $pairs;
}

function mdo_home_target_webp_replace_20260820( string $content ): string {
    if ( '' === $content ) {
        return $content;
    }

    $pairs = mdo_home_target_webp_pairs_20260820();
    if ( empty( $pairs ) ) {
        return $content;
    }

    /* Cover normal URLs and HTML-escaped copies inside srcset/style attributes. */
    foreach ( $pairs as $source => $webp ) {
        $content = str_replace( $source, $webp, $content );
        $content = str_replace( htmlspecialchars( $source, ENT_QUOTES, 'UTF-8' ), htmlspecialchars( $webp, ENT_QUOTES, 'UTF-8' ), $content );
    }

    return $content;
}

function mdo_home_target_webp_final_20260820( string $html ): string {
    $optimized = mdo_home_target_webp_replace_20260820( $html );

    /* The category-card image can live in the deferred Home stylesheet. */
    if ( function_exists( 'elmercado_home_deferred_css_file' ) ) {
        $css_file = elmercado_home_deferred_css_file();
        if ( is_readable( $css_file ) ) {
            $css = (string) file_get_contents( $css_file );
            $new = mdo_home_target_webp_replace_20260820( $css );
            if ( $new !== $css ) {
                if ( function_exists( 'elmercado_write_atomic_home_file' ) ) {
                    elmercado_write_atomic_home_file( $css_file, $new );
                } else {
                    file_put_contents( $css_file, $new, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                }
            }
        }
    }

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

/* Start first so this buffer is outermost and therefore receives final HTML. */
add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
            return;
        }
        ob_start( 'mdo_home_target_webp_final_20260820' );
    },
    -1000000
);
