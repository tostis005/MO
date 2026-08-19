<?php
/**
 * First-paint desktop header geometry for the Home.
 *
 * The normal child-theme cascade ultimately compacts the Home header to 72px,
 * hides duplicate search controls and turns the FiboSearch menu item into a
 * 44px icon. Emit those final structural rules before the large CSS cascade so
 * the primary navigation cannot re-centre several times during initial paint.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		$revision = '010279-1';
		if ( get_option( 'elmercado_home_header_cls_revision', '' ) === $revision ) {
			return;
		}

		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		}

		update_option( 'elmercado_home_header_cls_revision', $revision, false );
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
<style id="elmercado-home-header-cls-010279">
@media(min-width:992px){
/* Match the final Home header dimensions already emitted by home-refresh.php. */
body.home.elmercado-child-theme .site-header{
min-height:72px!important;
}
body.home.elmercado-child-theme .site-header-inner,
body.home.elmercado-child-theme .site-header-inner>.woostify-container{
min-height:72px!important;
}
body.home.elmercado-child-theme .site-header-inner>.woostify-container{
display:flex!important;
align-items:center!important;
width:min(calc(100% - 40px),1320px)!important;
max-width:1320px!important;
padding-block:0!important;
}
body.home.elmercado-child-theme .site-branding{
flex:0 0 auto!important;
}
body.home.elmercado-child-theme .site-branding img,
body.home.elmercado-child-theme .custom-logo{
display:block!important;
width:auto!important;
height:auto!important;
max-height:52px!important;
}

/* These controls are hidden by final.css; keep them out of the first layout too. */
body.home.elmercado-child-theme .site-tools .header-search-icon,
body.home.elmercado-child-theme .site-header .site-search,
body.home.elmercado-child-theme .site-tools .my-account .subbox{
display:none!important;
}

/* Reserve the final navigation box before Woostify/FiboSearch finish styling it. */
body.home.elmercado-child-theme .site-header .site-navigation{
display:flex!important;
flex:1 1 auto!important;
min-width:0!important;
min-height:44px!important;
align-items:center!important;
justify-content:center!important;
margin:0!important;
}
body.home.elmercado-child-theme .site-header .main-navigation{
display:flex!important;
min-width:0!important;
min-height:44px!important;
align-items:center!important;
justify-content:center!important;
margin-inline:auto!important;
}
body.home.elmercado-child-theme .site-header .primary-navigation{
display:flex!important;
min-height:44px!important;
align-items:center!important;
justify-content:center!important;
flex-wrap:nowrap!important;
margin:0!important;
padding:0!important;
}
body.home.elmercado-child-theme .site-header .primary-navigation>li{
display:flex!important;
min-height:44px!important;
align-items:center!important;
margin:0!important;
padding:0!important;
}
body.home.elmercado-child-theme .site-header .primary-navigation>li>a{
display:flex!important;
min-height:44px!important;
align-items:center!important;
margin:0 .9rem!important;
padding:.7rem 0!important;
font-family:Aptos,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif!important;
font-size:.86rem!important;
font-weight:760!important;
letter-spacing:.025em!important;
line-height:1.2!important;
white-space:nowrap!important;
}

/* FiboSearch ends as one 44px menu icon; reserve that footprint immediately. */
body.home.elmercado-child-theme .site-header .main-navigation .primary-navigation>li>.dgwt-wcas-search-wrapp{
display:grid!important;
width:44px!important;
min-width:44px!important;
max-width:44px!important;
height:44px!important;
min-height:44px!important;
margin:0 0 0 .45rem!important;
padding:0!important;
place-items:center!important;
line-height:1!important;
}
body.home.elmercado-child-theme .site-header .dgwt-wcas-search-icon{
position:static!important;
display:grid!important;
width:44px!important;
height:44px!important;
margin:0!important;
padding:0!important;
place-items:center!important;
}

/* Account + cart occupy two 44px controls in the final desktop header. */
body.home.elmercado-child-theme .site-tools{
display:flex!important;
min-width:94px!important;
min-height:44px!important;
flex:0 0 auto!important;
align-items:center!important;
justify-content:flex-end!important;
gap:.35rem!important;
}
body.home.elmercado-child-theme .site-tools>a.tools-icon,
body.home.elmercado-child-theme .site-tools>.my-account>a.tools-icon{
width:44px!important;
height:44px!important;
min-width:44px!important;
min-height:44px!important;
margin:0!important;
padding:0!important;
}
}
</style>
		<?php
	},
	-110000
);
