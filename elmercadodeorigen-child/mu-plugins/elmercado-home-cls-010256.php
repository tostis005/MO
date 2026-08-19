<?php
/**
 * CLS stabilization for transient pre-initialisation states on the Home.
 *
 * Locks the final above-the-fold geometry before the normal theme cascade is
 * parsed, and keeps Woostify off-canvas UI out of the first layout frame.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bust the Home's own transient/static cache once for this CLS revision.
 */
add_action(
	'init',
	static function (): void {
		$revision = '010256-3';
		if ( get_option( 'elmercado_home_cls_cache_revision', '' ) === $revision ) {
			return;
		}

		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		}

		update_option( 'elmercado_home_cls_cache_revision', $revision, false );
	},
	-100000
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}
		?>
<style id="elmercado-home-cls-010256">
body.home .topbar{display:none!important;}

/*
 * Final desktop Hero geometry, emitted before the large child-theme cascade.
 * This mirrors the Home vendor/copy layer that currently wins after all CSS is
 * loaded, so Lighthouse never sees an intermediate taller/wider Hero.
 */
@media(min-width:992px){
body.home.elmercado-child-theme .emo-home>.emo-hero{
position:relative!important;isolation:isolate!important;
min-height:min(600px,calc(100svh - 108px))!important;
padding-top:clamp(1.75rem,2.35vw,2.35rem)!important;
padding-bottom:clamp(2rem,3vw,3rem)!important;
}
body.home.elmercado-child-theme .emo-home>.emo-hero>.emo-hero__grid{
display:grid!important;
grid-template-columns:minmax(0,.92fr) minmax(480px,.85fr)!important;
align-items:center!important;
gap:clamp(2rem,4vw,4rem)!important;
width:min(calc(100% - 40px),1320px)!important;
max-width:1320px!important;
margin-inline:auto!important;
}
body.home .emo-hero__copy{max-width:700px!important;}
body.home .emo-hero h1{
max-width:760px!important;
margin:0 0 1.5rem!important;
font-size:clamp(3.75rem,5.45vw,4.9rem)!important;
line-height:.94!important;
}
body.home .emo-hero__copy>p{
max-width:600px!important;
margin:.75rem 0 1rem!important;
font-size:clamp(1rem,1.25vw,1.12rem)!important;
line-height:1.5!important;
}
body.home .emo-hero__proof{
margin-top:clamp(1.2rem,2vw,1.75rem)!important;
padding-top:.8rem!important;
gap:.65rem!important;
}
/* Pure decoration: remove the moving 54vw circle responsible for ~0.15 CLS. */
body.home.elmercado-child-theme .emo-home>.emo-hero::after{
display:none!important;content:none!important;width:0!important;height:0!important;
}
/* Mobile drawer markup is never part of desktop layout. */
body.home #mobile-navigation.sidebar-menu,body.home .sidebar-menu{
display:none!important;position:fixed!important;visibility:hidden!important;
opacity:0!important;pointer-events:none!important;transform:translate3d(-105%,0,0)!important;
}
}

@media(min-width:992px) and (max-width:1180px){
body.home.elmercado-child-theme .emo-home>.emo-hero>.emo-hero__grid{
grid-template-columns:minmax(0,1fr) minmax(420px,.82fr)!important;
}
}

/* Cart/overlay must start closed at every viewport before Woostify initialises. */
body.home #shop-cart-sidebar{
position:fixed!important;top:0!important;right:0!important;bottom:0!important;left:auto!important;
visibility:hidden!important;opacity:0!important;pointer-events:none!important;
transform:translate3d(105%,0,0)!important;margin:0!important;
}
body.home #woostify-overlay{
position:fixed!important;inset:0!important;visibility:hidden!important;
opacity:0!important;pointer-events:none!important;
}

@media(max-width:991px){
body.home #mobile-navigation.sidebar-menu[aria-hidden="true"],
html:not([data-emo-menu-intent="1"]) body.home #mobile-navigation.sidebar-menu{
position:fixed!important;top:0!important;bottom:0!important;left:0!important;
margin:0!important;visibility:hidden!important;opacity:0!important;
pointer-events:none!important;transform:translate3d(-105%,0,0)!important;
}
}

@media(max-width:767px){
body.home:not(.emo-js-ready) #shop-cart-sidebar{
position:fixed!important;top:0!important;right:0!important;left:auto!important;
width:min(390px,calc(100vw - 22px))!important;height:100dvh!important;
display:flex!important;transform:translateX(100%)!important;visibility:hidden!important;
opacity:1!important;z-index:200!important;margin:0!important;
}
body.home:not(.emo-js-ready) #shop-cart-sidebar .cart-sidebar-content{visibility:hidden!important;}
}
</style>
		<?php
	},
	-100000
);
