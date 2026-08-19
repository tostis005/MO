<?php
/**
 * Plugin Name: MDO - Home Performance Hardening 2026-08-19
 * Description: Autorrepara la caché estática de Home, saca Meta de la ruta crítica, elimina CSS muerto y reduce JS/imagen en la portada.
 * Version: 1.4.0
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
            || ! function_exists( 'elmercado_can_cache_home_request' )
            || ! function_exists( 'elmercado_home_cache_key' )
            || ! function_exists( 'elmercado_home_static_cache_file' )
            || ! function_exists( 'elmercado_home_deferred_css_file' )
            || ! elmercado_is_optimized_home()
            || ! elmercado_can_cache_home_request()
        ) {
            return;
        }

        $deferred_css = elmercado_home_deferred_css_file();
        if ( is_readable( $deferred_css ) && filesize( $deferred_css ) > 1000 ) {
            return;
        }

        delete_transient( elmercado_home_cache_key() );
        $static_html = elmercado_home_static_cache_file();
        if ( is_file( $static_html ) ) {
            @unlink( $static_html );
        }
    },
    -3000
);

function mdo_home_perf_local_image_dimensions( string $url ): ?array {
    static $cache = array();

    $url = html_entity_decode( trim( $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    if ( '' === $url ) {
        return null;
    }
    if ( array_key_exists( $url, $cache ) ) {
        return $cache[ $url ];
    }

    $uploads   = wp_get_upload_dir();
    $url_path  = (string) wp_parse_url( $url, PHP_URL_PATH );
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

    $file = trailingslashit( (string) $uploads['basedir'] ) . $relative;
    if ( ! is_readable( $file ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $size = @getimagesize( $file );
    if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
        $cache[ $url ] = null;
        return null;
    }

    $cache[ $url ] = array( (int) $size[0], (int) $size[1] );
    return $cache[ $url ];
}

function mdo_home_perf_add_img_attributes( string $tag, string $attributes ): string {
    if ( '' === $attributes ) {
        return $tag;
    }
    $updated = preg_replace( '~\s*(/?)>$~', ' ' . trim( $attributes ) . ' $1>', $tag, 1 );
    return is_string( $updated ) ? $updated : $tag;
}

/**
 * Sustituye o añade un atributo HTML simple en una etiqueta ya generada.
 */
function mdo_home_perf_set_tag_attribute( string $tag, string $name, string $value ): string {
    $pattern = '~\b' . preg_quote( $name, '~' ) . '\s*=\s*(["\']).*?\1~i';
    $escaped = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

    if ( preg_match( $pattern, $tag ) ) {
        $updated = preg_replace( $pattern, $name . '="' . $escaped . '"', $tag, 1 );
        return is_string( $updated ) ? $updated : $tag;
    }

    return mdo_home_perf_add_img_attributes( $tag, $name . '="' . $escaped . '"' );
}

/**
 * Convierte una imagen diferida por Smush en lazy loading nativo del navegador.
 * En la Home actual Smush sólo difiere IMG (sin iframe, background ni SOURCE),
 * de modo que podemos eliminar su runtime sin perder funcionalidad.
 */
function mdo_home_perf_native_lazy_image( string $tag ): string {
    if ( ! preg_match( '~\bdata-src\s*=\s*(["\'])(.*?)\1~i', $tag, $src_match ) ) {
        return $tag;
    }

    $src = html_entity_decode( $src_match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    if ( '' === trim( $src ) ) {
        return $tag;
    }

    $tag = mdo_home_perf_set_tag_attribute( $tag, 'src', $src );
    $tag = mdo_home_perf_set_tag_attribute( $tag, 'loading', 'lazy' );

    if ( preg_match( '~\bdata-srcset\s*=\s*(["\'])(.*?)\1~i', $tag, $srcset_match ) ) {
        $tag = mdo_home_perf_set_tag_attribute(
            $tag,
            'srcset',
            html_entity_decode( $srcset_match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' )
        );
    }

    if ( preg_match( '~\bdata-sizes\s*=\s*(["\'])(.*?)\1~i', $tag, $sizes_match ) ) {
        $tag = mdo_home_perf_set_tag_attribute(
            $tag,
            'sizes',
            html_entity_decode( $sizes_match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' )
        );
    }

    $tag = preg_replace( '~\s+data-(?:src|srcset|sizes)\s*=\s*(["\']).*?\1~i', '', $tag ) ?? $tag;
    $tag = preg_replace( '~\s+data-ll-status\s*=\s*(["\']).*?\1~i', '', $tag ) ?? $tag;

    $tag = preg_replace_callback(
        '~\bclass\s*=\s*(["\'])(.*?)\1~i',
        static function ( array $matches ): string {
            $classes = preg_split( '/\s+/', trim( $matches[2] ) ) ?: array();
            $classes = array_values(
                array_filter(
                    $classes,
                    static fn ( string $class ): bool => ! in_array( $class, array( 'lazyload', 'lazyloaded', 'lazyloading' ), true )
                )
            );
            return 'class=' . $matches[1] . implode( ' ', $classes ) . $matches[1];
        },
        $tag,
        1
    ) ?? $tag;

    return $tag;
}

function mdo_home_perf_transform_html( string $html ): string {
    if ( '' === $html ) {
        return $html;
    }

    /*
     * Hustle no monta ningún módulo ni script en la Home actual, pero su bloque
     * inline seguía entrando en la hoja diferida. Se elimina sólo en la Home.
     */
    $html = preg_replace(
        '~<style\b[^>]*\bid=["\']hustle_inline_styles_front-inline-css["\'][^>]*>.*?</style\s*>~is',
        '',
        $html,
        1
    ) ?? $html;

    /*
     * La Home sólo contiene IMG diferidas por Smush. Se convierten a loading
     * nativo y se evita ~236 ms de CPU observados en su runtime de lazy load.
     */
    $html = preg_replace(
        '~<script\b[^>]*(?:\bid=["\']smush-lazy-load-js["\']|\bsrc=["\'][^"\']*smush-lazy-load-native\.min\.js[^"\']*["\'])[^>]*>.*?</script\s*>~is',
        '',
        $html,
        1
    ) ?? $html;

    /* Meta CAPI deja de formar una cadena crítica HTML -> unpkg. */
    $html = preg_replace_callback(
        '~<script\b[^>]*\bid=["\']facebook-capi-param-builder-js["\'][^>]*\bsrc=["\']([^"\']+)["\'][^>]*>\s*</script\s*>~i',
        static function ( array $matches ): string {
            $src = htmlspecialchars( html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_QUOTES, 'UTF-8' );
            return '<script id="facebook-capi-param-builder-js" type="application/x-mdo-deferred" data-mdo-src="' . $src . '"></script>';
        },
        $html,
        1
    ) ?? $html;

    $meta_loader = <<<'HTML'
<script id="facebook-capi-param-builder-js-after">
(() => {
    'use strict';
    let started = false;

    const collect = () => {
        if (typeof clientParamBuilder !== 'undefined' && !/(?:^|;\s*)wc_facebook_signals_state=held(?:;|$)/.test(document.cookie)) {
            clientParamBuilder.processAndCollectAllParams(window.location.href);
        }
    };

    const load = () => {
        if (started) return;
        started = true;
        const placeholder = document.getElementById('facebook-capi-param-builder-js');
        const src = placeholder?.dataset?.mdoSrc || '';
        if (!src) return;
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = collect;
        document.head.appendChild(script);
    };

    const schedule = () => {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(load, { timeout: 1500 });
        } else {
            setTimeout(load, 500);
        }
    };

    window.addEventListener('pointerdown', load, { once: true, passive: true });
    window.addEventListener('touchstart', load, { once: true, passive: true });
    window.addEventListener('keydown', load, { once: true });
    if (document.readyState === 'complete') schedule();
    else window.addEventListener('load', schedule, { once: true });
})();
</script>
HTML;

    $html = preg_replace(
        '~<script\b[^>]*\bid=["\']facebook-capi-param-builder-js-after["\'][^>]*>.*?</script\s*>~is',
        $meta_loader,
        $html,
        1
    ) ?? $html;

    $html = preg_replace_callback(
        '~<script\b[^>]*\bsrc=["\'][^"\']*(?:facebook-for-woocommerce-signals\.js|/facebook-for-woocommerce/[^"\']*/pixel-events\.js)[^"\']*["\'][^>]*>~i',
        static function ( array $matches ): string {
            $tag = $matches[0];
            if ( preg_match( '/\s(?:defer|async)(?:\s|=|>)/i', $tag ) ) {
                return $tag;
            }
            $updated = preg_replace( '/^<script\b/i', '<script defer', $tag, 1 );
            return is_string( $updated ) ? $updated : $tag;
        },
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '~<img\b[^>]*>~i',
        static function ( array $matches ): string {
            $tag = mdo_home_perf_native_lazy_image( $matches[0] );

            /*
             * En móvil estas tarjetas ocupan ~78vw (315 px en viewport 412), no
             * calc(100vw - 32px). La medida anterior hacía elegir 492/600 px
             * cuando ya existe una variante de 295/320/360 px.
             */
            if ( preg_match( '~\bclass=["\'][^"\']*\belmercado-catalog-card-image-010241\b[^"\']*["\']~i', $tag ) ) {
                $sizes = '(max-width: 767px) 78vw, (max-width: 1100px) calc(50vw - 32px), 280px';
                $tag   = mdo_home_perf_set_tag_attribute( $tag, 'sizes', $sizes );
            }

            if ( ! preg_match( '~\bclass=["\'][^"\']*\bwcfmmp_sold_by_logo\b[^"\']*["\']~i', $tag ) ) {
                return $tag;
            }

            $attrs = '';
            if ( ! preg_match( '~\balt\s*=~i', $tag ) ) {
                $attrs .= ' alt=""';
            }

            $has_width  = (bool) preg_match( '~\bwidth\s*=~i', $tag );
            $has_height = (bool) preg_match( '~\bheight\s*=~i', $tag );
            if ( ! $has_width || ! $has_height ) {
                $src = '';
                if ( preg_match( '~\bsrc=["\']([^"\']+)["\']~i', $tag, $src_match ) && '' !== trim( $src_match[1] ) ) {
                    $src = $src_match[1];
                }

                $dimensions = mdo_home_perf_local_image_dimensions( $src );
                if ( is_array( $dimensions ) ) {
                    if ( ! $has_width ) {
                        $attrs .= ' width="' . $dimensions[0] . '"';
                    }
                    if ( ! $has_height ) {
                        $attrs .= ' height="' . $dimensions[1] . '"';
                    }
                }
            }

            return mdo_home_perf_add_img_attributes( $tag, $attrs );
        },
        $html
    ) ?? $html;

    return $html;
}

add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
            return;
        }
        ob_start( 'mdo_home_perf_transform_html' );
    },
    -1000
);
