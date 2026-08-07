<?php
/**
 * Sistema visual final: gutters, home, carouseles y estabilidad de cabeceras 0.10.48.
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
		<style id="elmercado-premium-visual-system-01048">
			body.elmercado-child-theme {
				--emo-page-gutter: clamp(16px, 2.5vw, 32px);
				--emo-page-max: 1180px;
			}

			/* Un único ancho útil para las superficies principales del sitio. */
			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) #content > .woostify-container,
			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) .site-content > .woostify-container,
			body.woocommerce-shop.elmercado-child-theme #content > .woostify-container,
			body.tax-product_cat.elmercado-child-theme #content > .woostify-container,
			body.tax-product_tag.elmercado-child-theme #content > .woostify-container {
				box-sizing: border-box !important;
				width: min(calc(100% - (2 * var(--emo-page-gutter))), var(--emo-page-max)) !important;
				max-width: var(--emo-page-max) !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
			}

			/* Tiendas de productor: mismo gutter, sin un segundo margen interno. */
			body.wcfmmp-store-page.elmercado-child-theme #content > .woostify-container,
			body.wcfmmp-store-page.elmercado-child-theme .site-content > .woostify-container {
				width: min(calc(100% - (2 * var(--emo-page-gutter))), var(--emo-page-max)) !important;
				max-width: var(--emo-page-max) !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
			}
			body.wcfmmp-store-page.elmercado-child-theme #wcfmmp-store {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
			}

			/* Cabeceras interiores: siempre en flujo natural, sin saltos por scroll. */
			body.elmercado-child-theme :is(
				.emo-journal-hero,
				.emo-journal-hero__inner,
				.emo-producers-intro,
				.emo-contact-layout,
				.emo-contact-aside
			) {
				position: relative !important;
				top: auto !important;
				transform: none !important;
				translate: none !important;
				transition: none !important;
				animation: none !important;
			}
			body.elmercado-child-theme :is(.elmercado-compact-producers,.elmercado-contact-page,.elmercado-editorial-content) .site-content {
				transform: none !important;
				transition: none !important;
			}

			/* Contraste inequívoco en los bloques verdes de la Home. */
			body.home.elmercado-child-theme .emo-story__panel,
			body.home.elmercado-child-theme .emo-story__panel :is(h2,h3,strong,p,span,a),
			body.home.elmercado-child-theme .emo-story__panel .emo-kicker,
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				color: #fffdf8 !important;
			}
			body.home.elmercado-child-theme .emo-story__panel p {
				color: rgba(255,253,248,.82) !important;
			}
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				text-decoration-color: rgba(255,253,248,.38) !important;
			}

			/* Productos destacados: sin flechas. El gesto lateral es la pista visual. */
			body.home.elmercado-child-theme .emo-featured-products :is(
				.slick-arrow,.swiper-button-prev,.swiper-button-next,.owl-nav,.tns-controls,
				.wc-block-components-product-carousel__button,.products-carousel-nav
			) {
				display: none !important;
			}

			/* Drawer de filtros: estado abierto real y resistente a CSS heredado. */
			@media (max-width: 1100px) {
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]),
				body.elmercado-child-theme .emo-mobile-filter-shell:not([hidden]) {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-panel,
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-content,
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .widget-area,
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .widget {
					visibility: visible !important;
					opacity: 1 !important;
				}
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme { --emo-page-gutter: 16px; }

				/* Carrusel táctil: una tarjeta completa y un avance visible de la siguiente. */
				body.home.elmercado-child-theme .emo-featured-products .woocommerce {
					overflow: visible !important;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products {
					display: flex !important;
					flex-wrap: nowrap !important;
					gap: 14px !important;
					width: 100% !important;
					margin: 0 !important;
					padding: 2px max(0px, var(--emo-page-gutter)) 10px 0 !important;
					overflow-x: auto !important;
					overflow-y: visible !important;
					scroll-snap-type: x proximity;
					scroll-padding-inline: 0;
					-webkit-overflow-scrolling: touch;
					scrollbar-width: none;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products::-webkit-scrollbar { display: none; }
				body.home.elmercado-child-theme .emo-featured-products ul.products > li.product {
					box-sizing: border-box !important;
					flex: 0 0 82% !important;
					width: 82% !important;
					max-width: 82% !important;
					margin: 0 !important;
					scroll-snap-align: start;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products > li.product:last-child {
					margin-right: 8px !important;
				}
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
		<script id="elmercado-premium-visual-system-js-01048">
		(() => {
			'use strict';
			/* Evita que controles heredados de carrusel reaparezcan tras inicializaciones tardías. */
			if (document.body.classList.contains('home')) {
				document.querySelectorAll('.emo-featured-products .slick-arrow,.emo-featured-products .swiper-button-prev,.emo-featured-products .swiper-button-next,.emo-featured-products .owl-nav,.emo-featured-products .tns-controls').forEach((node) => node.setAttribute('hidden',''));
			}

			/* El drawer debe quedar realmente visible tras el click, incluso si otro módulo hereda visibility/opacity. */
			const toggle = document.getElementById('emo-premium-filter-toggle');
			const shell = document.getElementById('emo-premium-filter-shell');
			if (toggle && shell) {
				toggle.addEventListener('click', () => {
					requestAnimationFrame(() => {
						if (toggle.getAttribute('aria-expanded') === 'true') {
							shell.hidden = false;
							shell.style.setProperty('display','block','important');
							shell.style.setProperty('visibility','visible','important');
							shell.style.setProperty('opacity','1','important');
							shell.style.setProperty('pointer-events','auto','important');
						}
					});
				}, true);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
