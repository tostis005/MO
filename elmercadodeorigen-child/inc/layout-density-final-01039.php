<?php
/**
 * Responsive shop, content width and card density refinements 0.10.39.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_shop() || is_product_category() || is_product_tag() ) {
			$classes[] = 'elmercado-compact-shop';
		}
		if ( is_page( 'contacto' ) ) {
			$classes[] = 'elmercado-compact-contact';
		}
		if ( is_page( 'productores' ) ) {
			$classes[] = 'elmercado-compact-producers';
		}
		return $classes;
	}
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-layout-density-final-01039">
			/* Quitar cabeceras/breadcrumbs redundantes y acercar el contenido principal. */
			body.elmercado-child-theme:is(.elmercado-compact-shop,.elmercado-compact-contact,.elmercado-compact-producers) :is(
				.page-header,
				.entry-header,
				.woocommerce-products-header,
				.woocommerce-breadcrumb,
				.breadcrumbs,
				.woostify-breadcrumb,
				.page-title-wrap
			) {
				display: none !important;
			}
			body.elmercado-child-theme:is(.elmercado-compact-shop,.elmercado-compact-contact,.elmercado-compact-producers) .site-content {
				padding-top: 18px !important;
			}
			body.elmercado-child-theme.elmercado-compact-shop :is(.content-area,.site-main) {
				padding-top: 0 !important;
				margin-top: 0 !important;
			}

			/* Entrada de blog: nunca reservar una columna vacía para sidebar. */
			body.elmercado-child-theme.single-post #secondary,
			body.elmercado-child-theme.single-post .widget-area:not(.footer-widget-area) {
				display: none !important;
			}
			body.elmercado-child-theme.single-post :is(#primary,.content-area,.site-main) {
				width: 100% !important;
				max-width: none !important;
				float: none !important;
				margin-inline: 0 !important;
			}
			body.elmercado-child-theme.single-post .site-content > .woostify-container {
				display: block !important;
			}

			/* Home: menos aire entre bloques, manteniendo respiración editorial. */
			body.elmercado-child-theme.home .site-content {
				padding-top: 0 !important;
			}
			body.elmercado-child-theme.home .elementor-top-section:not(:first-child),
			body.elmercado-child-theme.home .e-con.e-parent:not(:first-child) {
				padding-top: clamp(24px, 3.2vw, 48px) !important;
				padding-bottom: clamp(24px, 3.2vw, 48px) !important;
			}

			/* Tarjetas: dos líneas reales, sin cortar descendentes, y bloque de precio más compacto. */
			body.elmercado-child-theme :is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			),
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			) {
				display: -webkit-box !important;
				min-height: 2.9em !important;
				max-height: 2.9em !important;
				margin: 9px 12px 3px !important;
				padding: 0 0 2px !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-height: 1.38 !important;
			}
			body.elmercado-child-theme :is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) ul.products li.product .price,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product .price {
				display: block !important;
				margin: 2px 12px 8px !important;
				padding: 0 !important;
				line-height: 1.25 !important;
			}
			body.elmercado-child-theme :is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) ul.products li.product,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products li.product {
				padding-bottom: 10px !important;
			}

			/* Filtros desktop: columna estable, alineada con la primera fila de producto y sticky. */
			@media (min-width: 1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) {
					position: sticky !important;
					top: 94px !important;
					align-self: start !important;
					height: max-content !important;
					margin-top: 64px !important;
					padding: 14px !important;
					border-radius: 16px !important;
					background: #fff !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-shell {
					display: none !important;
				}
			}

			/* Cuando deja de caber la columna, desaparece del flujo y el catálogo usa todo el ancho. */
			@media (max-width: 1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#primary,.content-area,.site-main) {
					width: 100% !important;
					max-width: 100% !important;
					float: none !important;
					margin-inline: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .site-content > .woostify-container {
					display: block !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .site-content :is(#secondary.widget-area,.shop-widget-area) {
					display: none !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle {
					display: inline-flex !important;
					width: 100% !important;
					min-height: 42px !important;
					align-items: center !important;
					justify-content: space-between !important;
					margin: 0 0 16px !important;
					padding: 0 13px !important;
					border: 1px solid rgba(23,63,50,.12) !important;
					border-radius: 12px !important;
					background: #f7f9f6 !important;
					color: #173f32 !important;
					font-size: 12px !important;
					font-weight: 750 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-shell:not([hidden]) {
					display: block !important;
				}
			}

			/* Drawer y títulos de filtros: más ligeros, separados y ordenados. */
			body.elmercado-child-theme .emo-mobile-filter-head {
				min-height: 44px !important;
				margin-bottom: 14px !important;
				padding-bottom: 10px !important;
			}
			body.elmercado-child-theme .emo-mobile-filter-title {
				font-size: 14px !important;
				font-weight: 800 !important;
				letter-spacing: .01em !important;
			}
			body.elmercado-child-theme :is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(
				#secondary.widget-area,
				.shop-widget-area,
				.emo-mobile-filter-content .widget-area
			) .widget {
				margin: 0 0 20px !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme :is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(
				#secondary.widget-area,
				.shop-widget-area,
				.emo-mobile-filter-content .widget-area
			) :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading) {
				min-height: 32px !important;
				margin: 0 0 12px !important;
				padding: 7px 10px !important;
				border-radius: 9px !important;
				font-size: 10.5px !important;
				font-weight: 800 !important;
				letter-spacing: .045em !important;
				line-height: 1.2 !important;
			}
			body.elmercado-child-theme .emo-mobile-filter-panel {
				width: min(86vw, 330px) !important;
				max-width: 330px !important;
				padding: 14px 14px calc(22px + env(safe-area-inset-bottom,0px)) !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-shop-filter-breakpoint-01039">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;

			const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area,.content-area + .widget-area');
			const toggle = document.querySelector('.emo-mobile-filter-toggle');
			const shell = document.querySelector('.emo-mobile-filter-shell');
			const content = shell?.querySelector('.emo-mobile-filter-content');
			if (!sidebar || !toggle || !shell || !content) return;

			const homeMarker = document.createComment('emo-filter-home-01039');
			if (sidebar.parentNode) sidebar.parentNode.insertBefore(homeMarker, sidebar);
			const compact = () => window.matchMedia('(max-width: 1100px)').matches;

			const sync = () => {
				if (compact()) {
					if (sidebar.parentElement !== content) content.append(sidebar);
					toggle.querySelector('.emo-filter-label')?.replaceChildren(document.createTextNode('Filtros'));
					const title = shell.querySelector('.emo-mobile-filter-title');
					if (title) title.textContent = 'Filtros';
				} else {
					if (homeMarker.parentNode && sidebar.parentElement === content) {
						homeMarker.parentNode.insertBefore(sidebar, homeMarker.nextSibling);
					}
					shell.hidden = true;
					document.documentElement.classList.remove('emo-shop-filter-open');
					body.classList.remove('emo-shop-filter-open');
					toggle.setAttribute('aria-expanded', 'false');
				}
			};

			sync();
			window.addEventListener('load', sync, { once: true });
			window.addEventListener('resize', sync, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
