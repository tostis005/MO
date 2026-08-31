<?php
/**
 * Plugin Name: MDO Home Performance 0.10.284
 * Description: Conservative Home-only performance refinements for El Mercado de Origen.
 * Version: 0.10.284.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_HOME_PERF_010284_VERSION = '0.10.284.2';

/**
 * Keep the special Home cache, but invalidate it once when this patch changes.
 */
add_action( 'init', static function () {
    $option = 'mdo_home_performance_coherence_version';

    if ( get_option( $option, '' ) === MDO_HOME_PERF_010284_VERSION ) {
        return;
    }

    if ( function_exists( 'elmercado_flush_home_cache' ) ) {
        elmercado_flush_home_cache();
    } else {
        $static_file = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
        if ( is_file( $static_file ) ) {
            @unlink( $static_file );
        }
    }

    update_option( $option, MDO_HOME_PERF_010284_VERSION, false );
}, -9999 );

/**
 * The coupon plugin stylesheet is not used on Home. Keep it everywhere else.
 */
add_action( 'wp_enqueue_scripts', static function () {
    if ( ! is_front_page() ) {
        return;
    }

    $styles = wp_styles();
    if ( ! $styles || empty( $styles->registered ) ) {
        return;
    }

    foreach ( $styles->registered as $handle => $style ) {
        $src = isset( $style->src ) ? (string) $style->src : '';
        if ( false !== stripos( $src, 'woo-coupon-usage' ) ) {
            wp_dequeue_style( $handle );
        }
    }
}, 99999 );

add_action( 'template_redirect', static function () {
    if ( is_admin() || wp_doing_ajax() || ! is_front_page() ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    if ( 'GET' !== $method ) {
        return;
    }

    ob_start( 'mdo_home_perf_transform_html_010284' );
}, -9999 );

function mdo_home_perf_set_attr_010284( $tag, $name, $value ) {
    $pattern = preg_quote( $name, '#' );
    $escaped = esc_attr( $value );

    if ( preg_match( '#\s' . $pattern . '\s*=\s*(["\']).*?\1#is', $tag ) ) {
        return preg_replace(
            '#\s' . $pattern . '\s*=\s*(["\']).*?\1#is',
            ' ' . $name . '="' . $escaped . '"',
            $tag,
            1
        );
    }

    return preg_replace( '#>$#', ' ' . $name . '="' . $escaped . '">', $tag, 1 );
}

function mdo_home_perf_add_class_010284( $tag, $class_name ) {
    if ( preg_match( '#\sclass\s*=\s*(["\'])(.*?)\1#is', $tag, $match ) ) {
        $classes = trim( $match[2] . ' ' . $class_name );
        return preg_replace(
            '#\sclass\s*=\s*(["\'])(.*?)\1#is',
            ' class="' . esc_attr( $classes ) . '"',
            $tag,
            1
        );
    }

    return preg_replace( '#>$#', ' class="' . esc_attr( $class_name ) . '">', $tag, 1 );
}

/**
 * Final Home HTML pass. It is deliberately narrow and idempotent.
 */
function mdo_home_perf_transform_html_010284( $html ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    // If this is a previously transformed cached document, do not transform twice.
    if ( false !== strpos( $html, '<!-- mdo-home-performance-010284 -->' ) ) {
        return $html;
    }

    // Remove the unused coupon stylesheet from Home, including cached markup.
    $html = preg_replace(
        '#<link\b[^>]*href\s*=\s*(["\'])[^"\']*woo-coupon-usage[^"\']*\1[^>]*>\s*#is',
        '',
        $html
    );

    // The current LCP text is the first paragraph inside the hero copy.
    $html = preg_replace_callback(
        '#(<div\b[^>]*class\s*=\s*(["\'])[^"\']*\bemo-hero__copy\b[^"\']*\2[^>]*>.*?)(<p\b[^>]*>)#is',
        static function ( $match ) {
            return $match[1] . mdo_home_perf_add_class_010284( $match[3], 'mdo-lcp-copy' );
        },
        $html,
        1
    );

    // Replace the oversized La Huerta hero-card source with real upload URLs.
    $html = preg_replace_callback(
        '#<img\b[^>]*(?:src|data-src)\s*=\s*(["\'])[^"\']*La-huerta-de-ana-mary-fondo[^"\']*\1[^>]*>#i',
        static function ( $match ) {
            $tag     = $match[0];
            $uploads = wp_get_upload_dir();

            if ( empty( $uploads['baseurl'] ) ) {
                return $tag;
            }

            $base_url = rtrim( (string) $uploads['baseurl'], '/' ) . '/2026/08';
            $src_480  = $base_url . '/La-huerta-de-ana-mary-fondo-mdo-480.webp';
            $src_960  = $base_url . '/La-huerta-de-ana-mary-fondo-mdo-960.webp';

            $tag = mdo_home_perf_set_attr_010284( $tag, 'src', $src_480 );
            $tag = mdo_home_perf_set_attr_010284( $tag, 'srcset', $src_480 . ' 480w, ' . $src_960 . ' 960w' );
            $tag = mdo_home_perf_set_attr_010284( $tag, 'sizes', '(max-width: 767px) 46vw, (max-width: 1199px) 31vw, 360px' );
            $tag = mdo_home_perf_set_attr_010284( $tag, 'loading', 'lazy' );
            $tag = mdo_home_perf_set_attr_010284( $tag, 'decoding', 'async' );
            $tag = mdo_home_perf_add_class_010284( $tag, 'mdo-home-producer-webp' );

            if ( ! empty( $uploads['basedir'] ) ) {
                $file = rtrim( (string) $uploads['basedir'], '/\\' ) . '/2026/08/La-huerta-de-ana-mary-fondo-mdo-480.webp';
                $dim  = @getimagesize( $file );
                if ( is_array( $dim ) && ! empty( $dim[0] ) && ! empty( $dim[1] ) ) {
                    $tag = mdo_home_perf_set_attr_010284( $tag, 'width', (string) (int) $dim[0] );
                    $tag = mdo_home_perf_set_attr_010284( $tag, 'height', (string) (int) $dim[1] );
                }
            }

            return $tag;
        },
        $html,
        1
    );

    // Delay direct Facebook/Pinterest external tags when present. Existing GTM/gtag is untouched.
    $social_delayed = 0;
    $html = preg_replace_callback(
        '#<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>\s*</script>#is',
        static function ( $match ) use ( &$social_delayed ) {
            $src = html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' );
            if (
                false === stripos( $src, 'connect.facebook.net' ) &&
                false === stripos( $src, 'assets.pinterest.com' ) &&
                false === stripos( $src, 's.pinimg.com' )
            ) {
                return $match[0];
            }

            $social_delayed++;
            $tag = preg_replace(
                '#\ssrc\s*=\s*(["\'])([^"\']+)\1#i',
                ' data-mdo-delayed-social-src="$2"',
                $match[0],
                1
            );
            $tag = preg_replace( '#\stype\s*=\s*(["\']).*?\1#is', '', $tag );
            return preg_replace( '#<script\b#i', '<script type="text/plain"', $tag, 1 );
        },
        $html
    );

    $critical = '<style id="mdo-home-performance-010284-css">'
        . '.emo-home .emo-hero__copy>p,.mdo-lcp-copy,.mdo-lcp-copy *{opacity:1!important;visibility:visible!important;transform:none!important;animation:none!important;transition:none!important}'
        . '.emo-home .emo-hero__copy>p,.mdo-lcp-copy{content-visibility:visible!important}'
        . '</style>';

    if ( $social_delayed > 0 ) {
        $critical .= <<<'HTML'
<script id="mdo-home-performance-010284-loader">(function(){var done=false;function load(){if(done)return;done=true;document.querySelectorAll('script[data-mdo-delayed-social-src]').forEach(function(p){var s=document.createElement('script');Array.prototype.slice.call(p.attributes).forEach(function(a){if(a.name==='type'||a.name==='data-mdo-delayed-social-src')return;try{s.setAttribute(a.name,a.value)}catch(e){}});s.src=p.getAttribute('data-mdo-delayed-social-src');s.async=true;p.parentNode.insertBefore(s,p.nextSibling);p.remove();});['pointerdown','keydown','touchstart','scroll'].forEach(function(n){window.removeEventListener(n,load);});}['pointerdown','keydown','touchstart','scroll'].forEach(function(n){window.addEventListener(n,load,{once:true,passive:true});});window.addEventListener('load',function(){if('requestIdleCallback'in window){requestIdleCallback(load,{timeout:3000});}else{setTimeout(load,1200);}},{once:true});})();</script>
HTML;
    }

    $critical .= "\n<!-- mdo-home-performance-010284 -->";

    if ( false !== stripos( $html, '</head>' ) ) {
        return preg_replace( '#</head>#i', $critical . "\n</head>", $html, 1 );
    }

    return $critical . "\n" . $html;
}
