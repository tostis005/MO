<?php
/**
 * CLS stabilization for the cached Home first frame.
 *
 * The Home cache extracts wp_head <style> blocks to a deferred stylesheet and
 * keeps only `elmercado-home-first-view-css` in the critical path. Therefore a
 * normal wp_head override can still arrive after first paint. This MU plugin
 * wraps the final response before the Home cache layer, appends the geometry
 * lock to that real critical style and persists the patched response back into
 * the Home transient/static cache.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bust Home caches once for this first-frame contract revision.
 */
add_action(
	'init',
	static function (): void {
		$revision = '010256-8';
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

/**
 * CSS that must match the final desktop geometry before any deferred CSS loads.
 */
function elmercado_home_first_view_lock_css_010256(): string {
	return <<<'CSS'
/*elmercado-home-first-view-lock-010256*/
body.home .topbar{display:none!important}
@media(min-width:992px){
body.home.elmercado-child-theme .site-header{position:sticky!important;top:0!important;height:64px!important;min-height:64px!important;max-height:64px!important}
body.home.elmercado-child-theme .site-header-inner{height:64px!important;min-height:64px!important;max-height:64px!important;padding:0!important}
body.home.elmercado-child-theme .site-header-inner>.woostify-container{display:grid!important;grid-template-columns:minmax(190px,auto) minmax(0,1fr) 148px!important;grid-template-rows:64px!important;align-items:center!important;column-gap:clamp(28px,3.2vw,54px)!important;width:min(calc(100% - 40px),1320px)!important;height:64px!important;min-height:64px!important;max-height:64px!important;margin-inline:auto!important;padding-block:0!important}
body.home.elmercado-child-theme .site-header .site-branding{grid-column:1!important;grid-row:1!important;align-self:center!important;justify-self:start!important;margin:0!important;min-width:0!important}
body.home.elmercado-child-theme .site-header .site-navigation{grid-column:2!important;grid-row:1!important;display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important;overflow:visible!important}
body.home.elmercado-child-theme .site-header .main-navigation,body.home.elmercado-child-theme .site-header .site-navigation>nav,body.home.elmercado-child-theme .site-header nav#site-navigation{display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important}
body.home.elmercado-child-theme .site-header .primary-navigation,body.home.elmercado-child-theme .site-header nav#site-navigation>ul{display:flex!important;align-items:center!important;justify-content:center!important;width:auto!important;height:44px!important;min-height:44px!important;max-height:44px!important;gap:clamp(.35rem,1vw,1rem)!important;margin:0!important;padding:0!important;list-style:none!important}
body.home.elmercado-child-theme .site-header .primary-navigation>li,body.home.elmercado-child-theme .site-header nav#site-navigation>ul>li{display:flex!important;align-items:center!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important;list-style:none!important}
body.home.elmercado-child-theme .site-header .primary-navigation>li>a,body.home.elmercado-child-theme .site-header nav#site-navigation>ul>li>a{display:flex!important;align-items:center!important;height:44px!important;min-height:44px!important;margin:0!important;padding:.65rem .55rem!important;line-height:1.2!important;white-space:nowrap!important}
body.home.elmercado-child-theme .site-header .site-tools{grid-column:3!important;grid-row:1!important;display:grid!important;grid-template-columns:repeat(3,44px)!important;grid-auto-flow:column!important;grid-auto-columns:44px!important;align-items:center!important;justify-content:end!important;gap:8px!important;width:148px!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important;overflow:visible!important}
body.home.elmercado-child-theme .emo-home>.emo-hero{position:relative!important;isolation:isolate!important;min-height:min(600px,calc(100svh - 108px))!important;padding-top:clamp(1.75rem,2.35vw,2.35rem)!important;padding-bottom:clamp(2rem,3vw,3rem)!important}
body.home.elmercado-child-theme .emo-home>.emo-hero>.emo-hero__grid{display:grid!important;grid-template-columns:minmax(0,.92fr) minmax(480px,.85fr)!important;align-items:center!important;gap:clamp(2rem,4vw,4rem)!important;width:min(calc(100% - 40px),1320px)!important;max-width:1320px!important;margin-inline:auto!important}
body.home .emo-hero__copy{max-width:700px!important}
body.home .emo-hero h1{max-width:760px!important;margin:0 0 1.5rem!important;font-size:clamp(3.75rem,5.45vw,4.9rem)!important;line-height:.94!important}
body.home .emo-hero__copy>p{max-width:600px!important;margin:.75rem 0 1rem!important;font-size:clamp(1rem,1.25vw,1.12rem)!important;line-height:1.5!important}
body.home .emo-hero__proof{margin-top:clamp(1.2rem,2vw,1.75rem)!important;padding-top:.8rem!important;gap:.65rem!important}
body.home .emo-hero__visual--vendors{display:grid!important;grid-template-columns:repeat(12,minmax(0,1fr))!important;grid-template-rows:repeat(10,38px)!important;height:380px!important;min-height:380px!important;min-width:0!important;transform:translateY(-34px)!important}
body.home .emo-hero__visual--vendors .emo-hero-card{position:relative!important;display:block!important;min-width:0!important;min-height:0!important;overflow:hidden!important;contain:layout paint}
body.home .emo-hero__visual--vendors .emo-hero-card--1{grid-column:1/7!important;grid-row:1/11!important;transform:rotate(-1.2deg)!important}
body.home .emo-hero__visual--vendors .emo-hero-card--2{grid-column:7/13!important;grid-row:1/6!important;transform:rotate(1.1deg)!important}
body.home .emo-hero__visual--vendors .emo-hero-card--3{grid-column:7/13!important;grid-row:6/11!important;transform:rotate(.45deg)!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--1{grid-column:1/7!important;grid-row:1/7!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--2{grid-column:7/13!important;grid-row:1/6!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--3{grid-column:1/6!important;grid-row:7/11!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--4{grid-column:6/13!important;grid-row:6/11!important;transform:rotate(-.55deg)!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1{grid-column:1/6!important;grid-row:1/7!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2{grid-column:6/13!important;grid-row:1/5!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3{grid-column:1/5!important;grid-row:7/11!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4{grid-column:5/9!important;grid-row:5/11!important;transform:rotate(-.55deg)!important}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5{grid-column:9/13!important;grid-row:5/11!important;transform:rotate(.65deg)!important}
body.home .emo-hero__visual--vendors .emo-hero-card figure{position:relative!important;display:block!important;width:100%!important;height:100%!important;margin:0!important;overflow:hidden!important}
body.home .emo-hero__visual--vendors .emo-hero-card figure>img{position:absolute!important;inset:0!important;display:block!important;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;object-fit:cover!important;object-position:center center!important}
body.home.elmercado-child-theme .emo-home>.emo-hero::after{display:none!important;content:none!important;width:0!important;height:0!important}
body.home #mobile-navigation.sidebar-menu{display:none!important;position:fixed!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important;transform:translate3d(-105%,0,0)!important}
}
@media(min-width:992px) and (max-width:1180px){body.home.elmercado-child-theme .emo-home>.emo-hero>.emo-hero__grid{grid-template-columns:minmax(0,1fr) minmax(420px,.82fr)!important}}
body.home:not(.emo-js-ready) #shop-cart-sidebar{position:fixed!important;top:0!important;right:0!important;bottom:0!important;left:auto!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important;transform:translate3d(105%,0,0)!important;margin:0!important}
body.home:not(.emo-js-ready) #woostify-overlay{position:fixed!important;inset:0!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}
@media(max-width:991px){html:not(.sidebar-menu-open) body.home #mobile-navigation.sidebar-menu{position:fixed!important;top:0!important;bottom:0!important;left:0!important;margin:0!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important;transform:translate3d(-105%,0,0)!important}}
@media(max-width:767px){body.home:not(.emo-js-ready) #shop-cart-sidebar{width:min(390px,calc(100vw - 22px))!important;height:100dvh!important;display:flex!important;transform:translateX(100%)!important;opacity:1!important;z-index:200!important}body.home:not(.emo-js-ready) #shop-cart-sidebar .cart-sidebar-content{visibility:hidden!important}}
CSS;
}

/**
 * Append the lock to the real critical style generated by home-cache.php.
 */
function elmercado_home_patch_first_view_html_010256( string $html ): string {
	if ( '' === $html || false === stripos( $html, '<html' ) || str_contains( $html, 'elmercado-home-first-view-lock-010256' ) ) {
		return $html;
	}

	$lock = elmercado_home_first_view_lock_css_010256();
	$patched = false;

	$html = preg_replace_callback(
		'~(<style\b[^>]*\bid=["\']elmercado-home-first-view-css["\'][^>]*>)(.*?)(</style\s*>)~is',
		static function ( array $matches ) use ( $lock, &$patched ): string {
			$patched = true;
			return $matches[1] . $matches[2] . "\n" . $lock . "\n" . $matches[3];
		},
		$html,
		1
	) ?? $html;

	if ( ! $patched && false !== stripos( $html, '</head>' ) ) {
		$tag  = '<style id="elmercado-home-first-view-lock-010256">' . $lock . '</style>';
		$html = preg_replace( '~</head>~i', $tag . "\n</head>", $html, 1 ) ?? $html;
		$patched = true;
	}

	/*
	 * The inner Home cache writes its response before this outer buffer closes.
	 * Persist the patched response again so the advanced-cache/static HIT also
	 * starts with the corrected geometry on the next request.
	 */
	if (
		$patched
		&& function_exists( 'elmercado_can_cache_home_request' )
		&& elmercado_can_cache_home_request()
	) {
		if ( function_exists( 'elmercado_home_cache_key' ) ) {
			set_transient( elmercado_home_cache_key(), $html, 10 * MINUTE_IN_SECONDS );
		}
		if ( function_exists( 'elmercado_write_home_static_cache' ) ) {
			elmercado_write_home_static_cache( $html );
		}
	}

	return $html;
}

/**
 * Start outside the theme Home cache buffer (-2000), so this callback sees the
 * final transformed document even when the cache layer echoes and exits.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}
		ob_start( 'elmercado_home_patch_first_view_html_010256' );
	},
	-3000
);
