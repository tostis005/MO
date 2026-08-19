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
		$revision = '010256-4';
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
 * Legacy sticky-header code is cleaned later by theme.js. Make that cleanup a
 * visual no-op on Home so deleting inline margin/top/bumper state cannot move
 * the whole page after first paint.
 */
body.home #content,
body.home #content.site-content{
	margin-top:0!important;
}
body.home .site-header-inner.fija{
	position:static!important;
	top:auto!important;
	right:auto!important;
	bottom:auto!important;
	left:auto!important;
	transform:none!important;
}
body.home .site-header-inner + .bumper,
body.home .site-header-inner ~ .bumper{
	display:none!important;
	width:0!important;
	height:0!important;
	min-height:0!important;
	margin:0!important;
	padding:0!important;
	border:0!important;
}

/*
 * Final desktop header geometry. Several historical layers still declare 80,
 * 72 and 62px; the final runtime layer resolves to 64px. Reserve that exact
 * geometry before any footer JavaScript touches the header.
 */
@media(min-width:992px){
body.home.elmercado-child-theme .site-header{
	position:sticky!important;
	top:0!important;
	height:64px!important;
	min-height:64px!important;
}
body.home.elmercado-child-theme .site-header-inner{
	position:static!important;
	height:64px!important;
	min-height:64px!important;
	padding:0!important;
}
body.home.elmercado-child-theme .site-header-inner>.woostify-container{
	display:grid!important;
	grid-template-columns:minmax(190px,auto) minmax(0,1fr) 148px!important;
	align-items:center!important;
	column-gap:clamp(28px,3.2vw,54px)!important;
	height:64px!important;
	min-height:64px!important;
	padding-block:0!important;
}

/* Final desktop Hero geometry. */
body.home.elmercado-child-theme .emo-home>.emo-hero{
	position:relative!important;
	isolation:isolate!important;
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
body.home .emo-hero__copy{
	max-width:700px!important;
}
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

/*
 * Reserve the producer collage before any image decodes or later Home layer
 * reasserts the 10x38 grid. This removes intrinsic-image layout participation.
 */
body.home .emo-hero__visual--vendors{
	display:grid!important;
	grid-template-columns:repeat(12,minmax(0,1fr))!important;
	grid-template-rows:repeat(10,38px)!important;
	height:380px!important;
	min-height:380px!important;
	min-width:0!important;
	transform:translateY(-34px)!important;
}
body.home .emo-hero__visual--vendors .emo-hero-card{
	position:relative!important;
	display:block!important;
	min-width:0!important;
	overflow:hidden!important;
	contain:layout paint;
}
body.home .emo-hero__visual--vendors .emo-hero-card--1{
	grid-column:1/7!important;
	grid-row:1/11!important;
	transform:rotate(-1.2deg)!important;
}
body.home .emo-hero__visual--vendors .emo-hero-card--2{
	grid-column:7/13!important;
	grid-row:1/6!important;
	transform:rotate(1.1deg)!important;
}
body.home .emo-hero__visual--vendors .emo-hero-card--3{
	grid-column:7/13!important;
	grid-row:6/11!important;
	transform:rotate(.45deg)!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--1{
	grid-column:1/7!important;
	grid-row:1/7!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--2{
	grid-column:7/13!important;
	grid-row:1/6!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--3{
	grid-column:1/6!important;
	grid-row:7/11!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--4{
	grid-column:6/13!important;
	grid-row:6/11!important;
	transform:rotate(-.55deg)!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1{
	grid-column:1/6!important;
	grid-row:1/7!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2{
	grid-column:6/13!important;
	grid-row:1/5!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3{
	grid-column:1/5!important;
	grid-row:7/11!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4{
	grid-column:5/9!important;
	grid-row:5/11!important;
	transform:rotate(-.55deg)!important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5{
	grid-column:9/13!important;
	grid-row:5/11!important;
	transform:rotate(.65deg)!important;
}
body.home .emo-hero__visual--vendors .emo-hero-card figure{
	position:relative!important;
	width:100%!important;
	height:100%!important;
	margin:0!important;
	overflow:hidden!important;
}
body.home .emo-hero__visual--vendors .emo-hero-card figure>img{
	position:absolute!important;
	inset:0!important;
	display:block!important;
	width:100%!important;
	height:100%!important;
	max-width:none!important;
	max-height:none!important;
	object-fit:cover!important;
	object-position:center center!important;
}

/* Pure decoration: never allow the large circle to become a layout-shift node. */
body.home.elmercado-child-theme .emo-home>.emo-hero::after{
	display:none!important;
	content:none!important;
	width:0!important;
	height:0!important;
}

/* Mobile drawer markup is never part of desktop layout. */
body.home #mobile-navigation.sidebar-menu,
body.home .sidebar-menu{
	display:none!important;
	position:fixed!important;
	visibility:hidden!important;
	opacity:0!important;
	pointer-events:none!important;
	transform:translate3d(-105%,0,0)!important;
}
body.home #mobile-navigation .site-navigation{
	display:none!important;
}
}

@media(min-width:992px) and (max-width:1180px){
body.home.elmercado-child-theme .emo-home>.emo-hero>.emo-hero__grid{
	grid-template-columns:minmax(0,1fr) minmax(420px,.82fr)!important;
}
}

/* Cart/overlay must start closed at every viewport before Woostify initialises. */
body.home #shop-cart-sidebar{
	position:fixed!important;
	top:0!important;
	right:0!important;
	bottom:0!important;
	left:auto!important;
	visibility:hidden!important;
	opacity:0!important;
	pointer-events:none!important;
	transform:translate3d(105%,0,0)!important;
	margin:0!important;
	contain:layout paint;
}
body.home #shop-cart-sidebar .cart-sidebar-content{
	visibility:hidden!important;
	contain:layout paint;
}
html[data-emo-cart-intent="1"].cart-sidebar-open body.home #shop-cart-sidebar .cart-sidebar-content{
	visibility:visible!important;
}
body.home #woostify-overlay{
	position:fixed!important;
	inset:0!important;
	visibility:hidden!important;
	opacity:0!important;
	pointer-events:none!important;
}

@media(max-width:991px){
body.home #mobile-navigation.sidebar-menu[aria-hidden="true"],
html:not([data-emo-menu-intent="1"]) body.home #mobile-navigation.sidebar-menu{
	position:fixed!important;
	top:0!important;
	bottom:0!important;
	left:0!important;
	margin:0!important;
	visibility:hidden!important;
	opacity:0!important;
	pointer-events:none!important;
	transform:translate3d(-105%,0,0)!important;
}
}

@media(max-width:767px){
body.home:not(.emo-js-ready) #shop-cart-sidebar{
	position:fixed!important;
	top:0!important;
	right:0!important;
	left:auto!important;
	width:min(390px,calc(100vw - 22px))!important;
	height:100dvh!important;
	display:flex!important;
	transform:translateX(100%)!important;
	visibility:hidden!important;
	opacity:1!important;
	z-index:200!important;
	margin:0!important;
}
}
</style>
		<?php
	},
	-100000
);
