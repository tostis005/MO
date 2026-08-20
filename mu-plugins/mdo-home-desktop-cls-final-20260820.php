<?php
/**
 * Plugin Name: MDO - Home Desktop CLS Final 2026-08-20
 * Description: Hace coincidir la geometría crítica del hero de productores con su estado final desde el primer paint.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mdo_home_desktop_cls_final_css_20260820(): string {
    return '@media(min-width:992px){'
        . 'body.home .emo-hero{min-height:min(600px,calc(100svh - 108px))!important;padding-top:clamp(1.75rem,2.35vw,2.35rem)!important;padding-bottom:clamp(2rem,3vw,3rem)!important}'
        . 'body.home .emo-hero__grid{gap:clamp(2rem,4vw,4rem)!important}'
        . 'body.home .emo-hero h1{font-size:clamp(3.75rem,5.45vw,4.9rem)!important;line-height:.94!important}'
        . 'body.home .emo-hero__copy>p{font-size:clamp(1rem,1.25vw,1.12rem)!important;line-height:1.5!important;margin-top:.75rem!important;margin-bottom:1rem!important}'
        . 'body.home .emo-hero__proof{margin-top:clamp(1.2rem,2vw,1.75rem)!important;padding-top:.8rem!important;gap:.65rem!important}'
        . 'body.home .emo-hero__visual--vendors{display:grid!important;transform:translateY(-34px)!important;grid-template-columns:repeat(12,minmax(0,1fr))!important;grid-template-rows:repeat(10,38px)!important;height:380px!important;min-height:380px!important;min-width:0!important}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card{min-width:0!important;contain:layout paint}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card figure{height:100%!important;margin:0!important}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card img{display:block!important;width:100%!important;height:100%!important;object-fit:cover!important}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card--1{grid-column:1/7!important;grid-row:1/11!important;transform:rotate(-1.2deg)!important}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card--2{grid-column:7/13!important;grid-row:1/6!important;transform:rotate(1.1deg)!important}'
        . 'body.home .emo-hero__visual--vendors .emo-hero-card--3{grid-column:7/13!important;grid-row:6/11!important;transform:rotate(.45deg)!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--1{grid-column:1/7!important;grid-row:1/7!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--2{grid-column:7/13!important;grid-row:1/6!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--3{grid-column:1/6!important;grid-row:7/11!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--4{grid-column:6/13!important;grid-row:6/11!important;transform:rotate(-.55deg)!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1{grid-column:1/6!important;grid-row:1/7!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2{grid-column:6/13!important;grid-row:1/5!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3{grid-column:1/5!important;grid-row:7/11!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4{grid-column:5/9!important;grid-row:5/11!important;transform:rotate(-.55deg)!important}'
        . 'body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5{grid-column:9/13!important;grid-row:5/11!important;transform:rotate(.65deg)!important}'
        . '}';
}

function mdo_home_desktop_cls_final_output_20260820( string $html ): string {
    if ( '' === $html || str_contains( $html, 'mdo-home-desktop-cls-final-20260820' ) ) {
        return $html;
    }

    $css = '/* mdo-home-desktop-cls-final-20260820 */' . mdo_home_desktop_cls_final_css_20260820();
    $updated = preg_replace_callback(
        '~(<style\b[^>]*\bid=["\']elmercado-home-first-view-css["\'][^>]*>)(.*?)(</style\s*>)~is',
        static fn ( array $matches ): string => $matches[1] . $matches[2] . $css . $matches[3],
        $html,
        1
    );

    if ( is_string( $updated ) && $updated !== $html ) {
        $html = $updated;
    } else {
        $style = '<style id="mdo-home-desktop-cls-final-20260820">' . $css . '</style>';
        $html  = preg_replace( '~<head\b[^>]*>~i', '$0' . $style, $html, 1 ) ?? $html;
    }

    /*
     * home-fresh is an outer buffer while home-cache writes its static file from
     * an inner buffer. Re-write the static Home with the final, already-stable
     * document so subsequent advanced-cache hits preserve the same first frame.
     */
    if ( function_exists( 'elmercado_write_home_static_cache' ) ) {
        elmercado_write_home_static_cache( $html );
    }

    return $html;
}

add_action(
    'template_redirect',
    static function (): void {
        if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
            return;
        }
        ob_start( 'mdo_home_desktop_cls_final_output_20260820' );
    },
    -10000
);

add_action(
    'init',
    static function (): void {
        $revision = '20260820-1';
        if ( get_option( 'mdo_home_desktop_cls_final_revision', '' ) === $revision ) {
            return;
        }
        if ( function_exists( 'elmercado_flush_home_cache' ) ) {
            elmercado_flush_home_cache();
        } else {
            $file = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
            if ( is_file( $file ) ) {
                @unlink( $file );
            }
        }
        update_option( 'mdo_home_desktop_cls_final_revision', $revision, false );
    },
    -99990
);
