<?php
/**
 * Estabiliza el inicio real de las páginas tras retirar el encabezado nativo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-content-start-stability-01070">
			/*
			 * Woostify conserva aire en varios wrappers que antes quedaban debajo del
			 * page-header. Al retirarlo, ese aire se sumaba al gutter superior común.
			 * Lo neutralizamos en los wrappers estructurales, no en el contenido.
			 */
			html body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) .site-content > .woostify-container > :is(#primary,.content-area),
			html body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) :is(#primary,.content-area) > .site-main,
			html body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) main.site-main > article.page,
			html body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) article.page > .entry-content {
				margin-top: 0 !important;
				padding-top: 0 !important;
				translate: none !important;
				transform: none !important;
			}

			/* WooCommerce añade wrappers propios en carrito, checkout y cuenta. */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout,.woocommerce-account) :is(
				#primary,
				.content-area,
				.site-main,
				article.page,
				.entry-content,
				.entry-content > .woocommerce
			) {
				margin-top: 0 !important;
				padding-top: 0 !important;
				translate: none !important;
				transform: none !important;
			}

			/* Contacto y Productores ya reciben el gap desde .site-content. */
			html body.elmercado-child-theme:is(.elmercado-compact-contact,.elmercado-contact-page) .emo-contact-layout,
			html body.elmercado-child-theme:is(.elmercado-compact-producers,.elmercado-producers-page,.wcfm-store-list-page) .emo-producers-intro {
				margin-top: 0 !important;
			}

			/* El estado visual de scroll nunca debe alterar la geometría del contenido. */
			html body.elmercado-child-theme.is-scrolled:not(.home) #content,
			html body.elmercado-child-theme.is-scrolled:not(.home) .site-content,
			html body.elmercado-child-theme.is-scrolled:not(.home) :is(#primary,.content-area,.site-main) {
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 767px) {
				/* El título del drawer es texto de cabecera, no otro control. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-title {
					width: auto !important;
					max-width: none !important;
					min-height: 0 !important;
					flex: 1 1 auto !important;
					margin: 0 !important;
					padding: 0 !important;
					background: transparent !important;
					border: 0 !important;
					border-radius: 0 !important;
					box-shadow: none !important;
					outline: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
