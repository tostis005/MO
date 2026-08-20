<?php
/**
 * CLS stabilization for transient pre-initialisation states on the Home.
 *
 * Woostify renders some legacy/off-canvas elements in document flow before its
 * final front-end CSS/JS state is applied. Match the final closed/hidden state
 * from the first paint without changing the post-initialisation behaviour.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bust the Home's own transient/static cache once for this CLS revision.
 * `wp cache flush` is not sufficient when a stale Home transient survives in
 * another cache layer, so use the theme's canonical invalidation routine.
 */
add_action(
	'init',
	static function (): void {
		$revision = '010256-14';
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
 * Keep the final producer-collage geometry in the same render-blocking inline
 * block as the structural Woostify/Home CSS. The regular Home fresh layer also
 * prints this CSS near </head>, but the static-cache optimizer defers standalone
 * style blocks. Without this copy the first desktop frame uses the generic hero
 * geometry and shifts again when the 1–5 vendor layout arrives.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		if ( ! function_exists( 'elmercado_home_vendor_css_010244' ) ) {
			return;
		}

		$parent_handle = wp_style_is( 'woostify-parent-style', 'registered' )
			? 'woostify-parent-style'
			: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : '' );

		if ( '' === $parent_handle ) {
			return;
		}

		$vendor_css = trim( (string) elmercado_home_vendor_css_010244() );
		if ( '' !== $vendor_css ) {
			wp_add_inline_style( $parent_handle, "\n/* elmercado-home-vendors-critical-010256-14 */\n" . $vendor_css );
		}
	},
	PHP_INT_MAX
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
