<?php
/**
 * Plugin Name: EMDO — Franja aceite nueva cosecha 2026/27
 * Description: Un banner editorial antes de la franja navideña de la portada.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_home_aove_banner_allowed_20261030(): bool {
    if ( is_admin() || is_feed() || wp_doing_ajax() || ! is_front_page() ) { return false; }
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    return ! preg_match( '~^/en(?:/|$)~i', $path );
}

function emdo_home_aove_banner_markup_20261030(): string {
    $target = 'https://www.elmercadodeorigen.com/tienda/1957/';
    return '<aside class="emdo-aove-harvest" data-emdo-aove-harvest="2026-27"'
        . ' aria-label="Aceite de oliva de la cosecha 2026/27">'
        . '<div class="emdo-aove-harvest__inner">'
        . '<div class="emdo-aove-harvest__copy">'
        . '<strong class="emdo-aove-harvest__title">Ya está aquí la cosecha 2026/27</strong>'
        . '<span class="emdo-aove-harvest__description">Aceite de oliva virgen extra, recién molturado y sin filtrar.</span>'
        . '</div>'
        . '<a class="emdo-aove-harvest__link" href="' . esc_url( $target ) . '">'
        . 'Descubrir la nueva cosecha <span aria-hidden="true">→</span></a>'
        . '</div></aside>';
}

/**
 * Navidad injects its banner into the main query at priority 89, directly
 * after the first homepage section. Insert here after that filtering at 90,
 * immediately before the Christmas banner. Server-side, no layout shift or JS.
 */
add_filter( 'the_content', static function ( $content ) {
    if ( ! emdo_home_aove_banner_allowed_20261030()
        || ! in_the_loop() || ! is_main_query()
        || ! is_string( $content ) || '' === $content ) {
        return $content;
    }
    if ( false !== strpos( $content, 'data-emdo-aove-harvest="2026-27"' ) ) {
        return $content;
    }

    // Match ONLY the actual Christmas promo, never its footer template fallback.
    if ( ! preg_match(
        '~<aside\b[^>]*class=(["\'])[^"\']*\bemdo-navidad-promo\b[^"\']*\1[^>]*\bdata-emdo-navidad-promo=(["\'])2026\2[^>]*>~i',
        $content,
        $match,
        PREG_OFFSET_CAPTURE
    ) ) {
        return $content; // Missing reference: never inject into an arbitrary spot.
    }
    $position = (int) $match[0][1];
    return substr( $content, 0, $position )
        . emdo_home_aove_banner_markup_20261030()
        . substr( $content, $position );
}, 90 );

/**
 * Critical, scoped CSS in the original page head. No new request or JS,
 * compact sizing and unique warm background distinct from Christmas green.
 */
add_action( 'wp_head', static function (): void {
    if ( ! emdo_home_aove_banner_allowed_20261030() ) { return; }
    ?>
<style id="emdo-home-aove-harvest-20261030">
.emdo-aove-harvest,
.emdo-aove-harvest *{box-sizing:border-box}
.emdo-aove-harvest{
 display:block;position:relative;z-index:1;isolation:isolate;clear:both;
 width:100%;max-width:none;min-width:0;margin:0;padding:0;
 border:0;border-top:1px solid #e5d8bb;border-bottom:1px solid #e0d3b5;
 border-radius:0;box-shadow:none;
 background:#f4ead7;color:#294331
}
.emdo-aove-harvest .emdo-aove-harvest__inner{
 display:flex;align-items:center;justify-content:space-between;gap:22px;
 width:calc(100% - 40px);max-width:1180px;min-width:0;
 margin:0 auto;padding:24px 0
}
.emdo-aove-harvest .emdo-aove-harvest__copy{
 display:flex;flex:1 1 auto;min-width:0;flex-direction:column;gap:5px
}
.emdo-aove-harvest .emdo-aove-harvest__title{
 display:block;margin:0;padding:0;color:#294331;
 font-family:inherit;font-size:clamp(17px,1.7vw,22px);
 font-weight:700;letter-spacing:-.015em;line-height:1.28
}
.emdo-aove-harvest .emdo-aove-harvest__description{
 display:block;margin:0;padding:0;color:#425644;
 font-family:inherit;font-size:13px;font-weight:400;line-height:1.45
}
.emdo-aove-harvest .emdo-aove-harvest__link,
.emdo-aove-harvest .emdo-aove-harvest__link:visited{
 display:inline-flex;flex:0 0 auto;align-items:center;justify-content:center;
 gap:7px;width:auto;min-height:41px;max-width:100%;
 margin:0;padding:10px 18px;border:1px solid #294d37;border-radius:4px;
 background:#294d37;color:#fff!important;
 font-family:inherit;font-size:13px;line-height:1.3;font-weight:700;
 text-decoration:none!important;text-align:center;white-space:normal;
 transition:background-color .15s,border-color .15s
}
.emdo-aove-harvest .emdo-aove-harvest__link:hover,
.emdo-aove-harvest .emdo-aove-harvest__link:focus-visible{
 color:#fff!important;background:#396b4c;border-color:#396b4c
}
.emdo-aove-harvest .emdo-aove-harvest__link:focus-visible{
 outline:2px solid #294d37;outline-offset:3px
}
@media(max-width:670px){
 .emdo-aove-harvest .emdo-aove-harvest__inner{
  width:calc(100% - 32px);flex-direction:column;
  align-items:stretch;gap:12px;padding:18px 0
 }
 .emdo-aove-harvest .emdo-aove-harvest__title{font-size:17px}
 .emdo-aove-harvest .emdo-aove-harvest__description{font-size:12px}
 .emdo-aove-harvest .emdo-aove-harvest__link{
  align-self:flex-start;min-height:38px;padding:9px 15px
 }
}
@media(prefers-reduced-motion:reduce){.emdo-aove-harvest *{transition:none!important}}
</style>
    <?php
}, 26 );
