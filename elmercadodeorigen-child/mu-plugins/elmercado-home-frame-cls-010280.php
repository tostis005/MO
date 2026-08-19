<?php
/**
 * First-frame structural geometry for the desktop Home.
 *
 * The final child-theme CSS turns the front page into a full-bleed layout only
 * after the normal Woostify cascade has been parsed. Without these few rules,
 * the Hero can be painted once inside Woostify's content container and then
 * expand to 100vw, which Lighthouse records against .emo-hero__grid and its
 * first card. Emit the final structural frame before that cascade.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		$revision = '010280-1';
		if ( get_option( 'elmercado_home_frame_cls_revision', '' ) === $revision ) {
			return;
		}

		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		}

		update_option( 'elmercado_home_frame_cls_revision', $revision, false );
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
<style id="elmercado-home-frame-cls-010280">
@media(min-width:992px){
/* The Home has no theme page header in its final state. */
body.home .page-header,
body.home article.page>.entry-header,
body.home .post-navigation{
display:none!important;
}

/* Remove Woostify's content/container geometry before the Hero can paint. */
body.home #content,
body.home .site-content,
body.home .site-main,
body.home article.page,
body.home article.page .entry-content{
width:100%!important;
max-width:none!important;
min-width:0!important;
margin:0!important;
padding:0!important;
}
body.home article.page{
border:0!important;
box-shadow:none!important;
}

/* Match theme.css final full-bleed Home geometry on the very first frame. */
body.home .emo-home{
position:relative!important;
width:100vw!important;
max-width:none!important;
margin-left:calc(50% - 50vw)!important;
margin-right:0!important;
padding:0!important;
overflow:clip!important;
}
body.home .emo-home>.emo-hero{
width:100%!important;
max-width:none!important;
margin:0!important;
box-sizing:border-box!important;
}
body.home .emo-home>.emo-hero>.emo-hero__grid{
box-sizing:border-box!important;
}

/* Keep the already-reserved producer-card boxes stable when global box-sizing arrives. */
body.home .emo-hero__visual--vendors,
body.home .emo-hero__visual--vendors .emo-hero-card,
body.home .emo-hero__visual--vendors .emo-hero-card figure,
body.home .emo-hero__visual--vendors .emo-hero-card figure>img{
box-sizing:border-box!important;
}
}
</style>
		<?php
	},
	-120000
);
