<?php
/**
 * Plugin Name: MDO Home Performance 0.10.284
 * Description: Conservative Home-only performance refinements for El Mercado de Origen.
 * Version: 0.10.284
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_HOME_PERF_VERSION = '0.10.284';

/**
 * Flush the special Home cache exactly once per plugin version.
 */
add_action( 'init', static function () {
    $option = 'mdo_home_performance_coherence_version';

    if ( get_option( $option, '' ) === MDO_HOME_PERF_VERSION ) {
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

    update_option( $option, MDO_HOME_PERF_VERSION, false );
}, -9999 );

/**
 * Avoid loading the coupon plugin stylesheet on the front page only.
 * It remains available on shop/cart/checkout/account and every internal page.
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

/**
 * Start a lightweight HTML transform early enough to also cover cached Home output.
 */
add_action( 'template_redirect', static function () {
    if ( is_admin() || wp_doing_ajax() || ! is_front_page() ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    if ( 'GET' !== $method ) {
        return;
    }

    ob_start( 'mdo_home_perf_transform_html' );
}, -9999 );

/**
 * Add or replace an HTML attribute without changing the rest of the element.
 */
function mdo_home_perf_set_attr( $tag, $name, $value ) {
    $name_pattern = preg_quote( $name, '#' );
    $escaped      = esc_attr( $value );

    if ( preg_match( '#\s' . $name_pattern . '\s*=\s*(["\']).*?\1#is', $tag ) ) {
        return preg_replace(
            '#\s' . $name_pattern . '\s*=\s*(["\']).*?\1#is',
            ' ' . $name . '="' . $escaped . '"',
            $tag,
            1
        );
    }

    return preg_replace( '#>$#', ' ' . $name . '="' . $escaped . '">', $tag, 1 );
}

/**
 * Add a class to an opening tag while preserving existing classes.
 */
function mdo_home_perf_add_class( $tag, $class_name ) {
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
 * Remove a single attribute from an opening tag.
 */
function mdo_home_perf_remove_attr( $tag, $name ) {
    $name_pattern = preg_quote( $name, '#' );
    return preg_replace( '#\s+' . $name_pattern . '\s*=\s*(["\']).*?\1#is', '', $tag );
}

/**
 * Transform front-page HTML after WordPress has rendered it.
 */
function mdo_home_perf_transform_html( $html ) {
    if ( ! is_string( $html ) || '' === $html || false !== strpos( $html, 'mdo-home-performance-010284' ) ) {
        return $html;
    }

    // Remove only the coupon stylesheet from Home, including already-cached markup.
    $html = preg_replace(
        '#<link\b[^>]*href\s*=\s*(["\'])[^"\']*woo-coupon-usage[^"\']*\1[^>]*>\s*#is',
        '',
        $html
    );

    // Make the known text LCP immediately paintable without changing layout or typography.
    $html = preg_replace_callback(
        '#<p\b([^>]*)>((?:(?!</p>).)*En\s+El\s+Mercado\s+de\s+Origen\s+buscamos\s+productores(?:(?!</p>).)*)</p>#isu',
        static function ( $match ) {
            $open = '<p' . $match[1] . '>';
            $open = mdo_home_perf_add_class( $open, 'mdo-lcp-copy' );
            $open = mdo_home_perf_remove_attr( $open, 'data-aos' );
            return $open . $match[2] . '</p>';
        },
        $html,
        1
    );

    // Serve a much smaller responsive WebP for the oversized La Huerta producer card.
    $html = preg_replace_callback(
        '#<img\b[^>]*(?:src|data-src)\s*=\s*(["\'])[^"\']*La-huerta-de-ana-mary-fondo[^"\']*\1[^>]*>#i',
        static function ( $match ) {
            $tag = $match[0];

            if ( ! preg_match( '#\ssrc\s*=\s*(["\'])([^"\']+)\1#i', $tag, $src_match ) ) {
                return $tag;
            }

            $src      = html_entity_decode( $src_match[2], ENT_QUOTES, 'UTF-8' );
            $clean    = preg_replace( '#[?#].*$#', '', $src );
            $dir_url  = rtrim( str_replace( '\\', '/', dirname( $clean ) ), '/' );
            $src_480  = $dir_url . '/La-huerta-de-ana-mary-fondo-mdo-480.webp';
            $src_960  = $dir_url . '/La-huerta-de-ana-mary-fondo-mdo-960.webp';

            $tag = mdo_home_perf_set_attr( $tag, 'src', $src_480 );
            $tag = mdo_home_perf_set_attr( $tag, 'srcset', $src_480 . ' 480w, ' . $src_960 . ' 960w' );
            $tag = mdo_home_perf_set_attr( $tag, 'sizes', '(max-width: 767px) 46vw, (max-width: 1199px) 31vw, 360px' );
            $tag = mdo_home_perf_set_attr( $tag, 'loading', 'lazy' );
            $tag = mdo_home_perf_set_attr( $tag, 'decoding', 'async' );
            $tag = mdo_home_perf_add_class( $tag, 'mdo-home-producer-webp' );

            return $tag;
        },
        $html,
        1
    );

    // Delay only Facebook/Pinterest external scripts. Analytics/GTM are intentionally untouched.
    $html = preg_replace_callback(
        '#<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>\s*</script>#is',
        static function ( $match ) {
            $src = html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' );
            if (
                false === stripos( $src, 'connect.facebook.net' ) &&
                false === stripos( $src, 'assets.pinterest.com' ) &&
                false === stripos( $src, 's.pinimg.com' )
            ) {
                return $match[0];
            }

            $tag = $match[0];
            $tag = preg_replace(
                '#\ssrc\s*=\s*(["\'])([^"\']+)\1#i',
                ' data-mdo-delayed-social-src="$2"',
                $tag,
                1
            );
            $tag = preg_replace( '#\stype\s*=\s*(["\']).*?\1#is', '', $tag );
            $tag = preg_replace( '#<script\b#i', '<script type="text/plain"', $tag, 1 );

            return $tag;
        },
        $html
    );

    $critical = <<<'HTML'
<style id="mdo-home-performance-010284-css">.mdo-lcp-copy,.mdo-lcp-copy *{opacity:1!important;visibility:visible!important;transform:none!important;animation:none!important;transition:none!important}.mdo-lcp-copy{content-visibility:visible!important}</style>
<script id="mdo-home-performance-010284-loader">(function(){var done=false;function load(){if(done)return;done=true;var nodes=document.querySelectorAll('script[data-mdo-delayed-social-src]');nodes.forEach(function(p){var s=document.createElement('script');Array.prototype.slice.call(p.attributes).forEach(function(a){if(a.name==='type'||a.name==='data-mdo-delayed-social-src')return;try{s.setAttribute(a.name,a.value)}catch(e){}});s.src=p.getAttribute('data-mdo-delayed-social-src');s.async=true;p.parentNode.insertBefore(s,p.nextSibling);p.remove();});['pointerdown','keydown','touchstart','scroll'].forEach(function(n){window.removeEventListener(n,load);});}['pointerdown','keydown','touchstart','scroll'].forEach(function(n){window.addEventListener(n,load,{once:true,passive:true});});window.addEventListener('load',function(){if('requestIdleCallback'in window){requestIdleCallback(load,{timeout:3000});}else{setTimeout(load,1200);}},{once:true});})();</script>
<!-- mdo-home-performance-010284 -->
HTML;

    if ( false !== stripos( $html, '</head>' ) ) {
        $html = preg_replace( '#</head>#i', $critical . "\n</head>", $html, 1 );
    } else {
        $html = $critical . "\n" . $html;
    }

    return $html;
}
