<?php
/**
 * Unión final Home → footer en escritorio 0.10.136.
 *
 * Neutraliza el espacio inferior que Woostify/WordPress conserva en los
 * wrappers de página que envuelven a .emo-home. La portada termina en la
 * sección blanca y enlaza directamente con el pie.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-footer-gap-final-010136">
			@media (min-width: 992px) {
				html body.home.elmercado-child-theme :is(
					#content.site-content,
					.site-content,
					.content-area,
					#primary,
					#main,
					.site-main,
					article.page,
					article.hentry,
					.entry-content,
					.emo-home
				) {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
				}

				html body.home.elmercado-child-theme :is(#content,.site-content) > .woostify-container,
				html body.home.elmercado-child-theme :is(#primary,.content-area,.site-main) > article.page,
				html body.home.elmercado-child-theme article.page > .entry-content {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
				}

				html body.home.elmercado-child-theme .emo-home > .emo-vendor-cta:last-child,
				html body.home.elmercado-child-theme .emo-home > :last-child {
					margin-bottom: 0 !important;
				}

				html body.home.elmercado-child-theme .site-footer {
					margin-top: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
