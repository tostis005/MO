<?php
/**
 * Plugin Name: EMDO — Franja Black Friday 2026 en la portada
 * Description: Banner especial entre el hero y la cosecha, enlazado a la campaña.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Mantener el mismo ámbito que los banners actuales de cosecha y Navidad. */
function emdo_bf26_home_banner_allowed(): bool {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ! is_front_page() ) {
		return false;
	}
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	return ! preg_match( '~^/en(?:/|$)~i', $path );
}

function emdo_bf26_home_banner_html(): string {
	$href = esc_url( home_url( '/black-friday-2026/' ) );
	return '<aside class="emdo-bf26-home-feature" data-emdo-bf26-home-feature="2026" aria-label="Black Friday 2026: descuentos y regalos">'
		. '<div class="emdo-bf26-home-feature__inner">'
		. '<div class="emdo-bf26-home-feature__copy">'
		. '<span class="emdo-bf26-home-feature__eyebrow"><span class="emdo-bf26-home-feature__spark" aria-hidden="true">✳</span> BLACK FRIDAY 2026 <span class="emdo-bf26-home-feature__spark" aria-hidden="true">✳</span></span>'
		. '<strong class="emdo-bf26-home-feature__title">Este Black Friday, <em>hay mucho por descubrir.</em></strong>'
		. '<span class="emdo-bf26-home-feature__lead">Descuentos, regalos y ventajas especiales de nuestros productores, reunidos en un mismo lugar. <strong>Explora todas las promociones disponibles</strong> y descubre las que más te apetezcan.</span>'
		. '<span class="emdo-bf26-home-feature__note">Todas las ofertas y sus condiciones, en nuestra página especial de Black Friday.</span>'
		. '</div>'
		. '<div class="emdo-bf26-home-feature__action">'
		. '<div class="emdo-bf26-home-feature__seal" aria-hidden="true"><small>BLACK FRIDAY</small><strong>2026</strong><small>OFERTAS</small></div>'
		. '<a class="emdo-bf26-home-feature__cta" href="' . $href . '"><span class="emdo-bf26-home-feature__cta-label">Ver todas las promociones</span> <span class="emdo-bf26-home-feature__arrow" aria-hidden="true">↗</span></a>'
		. '</div>'
		. '</div></aside>';
}

/**
 * La cosecha se inserta en the_content a prioridad 90.
 * Insertar a 91 inmediatamente ANTES de su bloque; la Navidad queda debajo.
 * No añadir nada si la referencia no está, para no romper otras plantillas.
 */
add_filter( 'the_content', static function ( $content ) {
	if ( ! emdo_bf26_home_banner_allowed()
		|| ! in_the_loop() || ! is_main_query()
		|| ! is_string( $content ) || '' === $content
		|| false !== strpos( $content, 'data-emdo-bf26-home-feature="2026"' ) ) {
		return $content;
	}
	if ( ! preg_match(
		'~<aside\b[^>]*\bdata-emdo-aove-harvest=(["\'])2026-27\1[^>]*>~i',
		$content, $found, PREG_OFFSET_CAPTURE
	) ) {
		return $content;
	}
	$pos = (int) $found[0][1];
	return substr( $content, 0, $pos ) . emdo_bf26_home_banner_html() . substr( $content, $pos );
}, 91 );

/* CSS scope entirely isolated to the front-page banner. */
add_action( 'wp_head', static function (): void {
	if ( ! emdo_bf26_home_banner_allowed() ) { return; }
	?>
<style id="emdo-bf26-home-feature-style">
.emdo-bf26-home-feature,.emdo-bf26-home-feature *,.emdo-bf26-home-feature *::before,.emdo-bf26-home-feature *::after{box-sizing:border-box}
.emdo-bf26-home-feature{
  display:block;clear:both;position:relative;isolation:isolate;overflow:hidden;
  width:100%;max-width:none;margin:0;padding:0;border:0;
  background:radial-gradient(ellipse at 82% 28%,#285543 0%,#153c2d 44%,#0a251b 100%);
  color:#fff9ee;border-top:1px solid #b99662;border-bottom:3px solid #c9a66c
}
.emdo-bf26-home-feature::before{
  content:"";position:absolute;pointer-events:none;right:-115px;top:-165px;
  width:500px;height:500px;border-radius:50%;border:1px solid #e6cd985e;
  box-shadow:0 0 0 60px #e3c9920a,0 0 0 125px #e4d1a208
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__inner{
  position:relative;z-index:1;width:calc(100% - 48px);max-width:1200px;
  margin:0 auto;padding:40px 0;display:grid;
  grid-template-columns:minmax(0,1fr) minmax(210px,300px);gap:40px;align-items:center
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__copy{display:flex;flex-direction:column;gap:10px;min-width:0;max-width:100%;overflow:visible}
.emdo-bf26-home-feature .emdo-bf26-home-feature__eyebrow{color:#e0c58f;font-size:11px;line-height:1.5;font-weight:850;letter-spacing:.16em}
.emdo-bf26-home-feature .emdo-bf26-home-feature__spark{color:#d9b477;margin:0 5px}
.emdo-bf26-home-feature .emdo-bf26-home-feature__title{
  display:block;max-width:100%;min-width:0;padding-block:3px;white-space:normal;overflow-wrap:break-word;color:#fffaf2;font:normal 500 clamp(29px,3vw,44px)/1.27 Georgia,serif;letter-spacing:-.032em
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__title em{color:#eed09a;font-style:italic}
.emdo-bf26-home-feature .emdo-bf26-home-feature__lead{
  display:block;max-width:720px;color:#edf0e8;font-size:clamp(14px,1.33vw,16px);line-height:1.68
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__lead strong{color:#fff8dd}
.emdo-bf26-home-feature .emdo-bf26-home-feature__note{color:#d8dfd0;font-size:13px;line-height:1.5}
.emdo-bf26-home-feature .emdo-bf26-home-feature__action{
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__seal{
  display:flex;align-items:center;justify-content:center;flex-direction:column;gap:3px;
  width:138px;height:138px;flex:none;border-radius:50%;border:1px solid #b18c57;
  outline:4px solid #e4c999;background:radial-gradient(circle at 30% 22%,#ffedc6,#e0bd7e 65%,#c29a60);
  color:#163b2a;transform:rotate(8deg);box-shadow:0 13px 34px #071c1540
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__seal small{display:block;max-width:calc(100% - 20px);white-space:nowrap;text-align:center;font-size:10px;line-height:1.35;letter-spacing:.1em;font-weight:850}
.emdo-bf26-home-feature .emdo-bf26-home-feature__seal strong{font:normal 700 41px/.95 Georgia,serif;letter-spacing:-.06em}
.emdo-bf26-home-feature .emdo-bf26-home-feature__seal strong span{font-size:.58em;letter-spacing:-.02em}
.emdo-bf26-home-feature .emdo-bf26-home-feature__cta,
.emdo-bf26-home-feature .emdo-bf26-home-feature__cta:visited{
  display:inline-flex;align-items:center;justify-content:center;gap:18px;
  min-height:49px;width:max-content;max-width:100%;padding:13px 23px;
  background:#ebd4a5;color:#183929!important;border:1px solid #f7e5bd;border-radius:4px;
  font-size:14px;line-height:1.3;font-weight:850;text-decoration:none!important;text-align:center;
  box-shadow:0 8px 23px #00180d3d;transition:background-color .2s,transform .2s
}
.emdo-bf26-home-feature .emdo-bf26-home-feature__cta-label{display:block;min-width:0;max-width:100%;white-space:normal;overflow-wrap:break-word;line-height:1.45}
.emdo-bf26-home-feature .emdo-bf26-home-feature__arrow{display:inline-flex;align-items:center;justify-content:center;flex:none;line-height:1}
.emdo-bf26-home-feature .emdo-bf26-home-feature__seal>*{flex:none;min-width:0}
.emdo-bf26-home-feature .emdo-bf26-home-feature__cta:hover{background:#fff0cf!important;transform:translateY(-2px)}
.emdo-bf26-home-feature .emdo-bf26-home-feature__cta:focus-visible{outline:3px solid #f0d29c;outline-offset:4px}
@media(max-width:760px){
 .emdo-bf26-home-feature .emdo-bf26-home-feature__inner{
  width:calc(100% - 34px);padding:28px 0;grid-template-columns:1fr;gap:20px
 }
 .emdo-bf26-home-feature .emdo-bf26-home-feature__title{font-size:clamp(28px,7vw,37px)}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__action{
  align-items:center;flex-direction:row;justify-content:space-between;gap:17px
 }
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal{width:89px;height:89px;outline-width:3px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal strong{font-size:27px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal small{font-size:7px;letter-spacing:.06em;max-width:calc(100% - 12px)}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__cta{min-height:43px;padding:10px 14px;font-size:13px;min-width:0}
}
@media(max-width:540px){
 .emdo-bf26-home-feature .emdo-bf26-home-feature__inner{gap:16px;padding:26px 0 28px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__copy{gap:9px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__title{font-size:clamp(27px,7.8vw,34px);line-height:1.3}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__lead{font-size:14px;line-height:1.65}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__action{flex-direction:column;align-items:stretch;justify-content:center;gap:13px;min-width:0}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal{align-self:center;width:95px;height:95px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal small{font-size:7.3px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal strong{font-size:28px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__cta{width:100%;max-width:100%;align-self:stretch;min-height:48px;padding:11px 17px;gap:9px;white-space:normal;font-size:14px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__cta-label{flex:0 1 auto}
}
@media(max-width:370px){
 .emdo-bf26-home-feature .emdo-bf26-home-feature__action{gap:12px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal{width:90px;height:90px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__seal strong{font-size:27px}
 .emdo-bf26-home-feature .emdo-bf26-home-feature__cta{gap:8px;font-size:13px;padding:10px 12px}
}
@media(prefers-reduced-motion:reduce){.emdo-bf26-home-feature *{transition:none!important}}
</style>
	<?php
}, 27 );
