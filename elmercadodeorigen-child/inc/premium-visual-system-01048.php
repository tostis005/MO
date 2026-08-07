<?php
/**
 * Sistema visual final: gutters, home, carouseles y estabilidad de cabeceras 0.10.49.
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
		<style id="elmercado-premium-visual-system-01049">
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

			/* Tiendas de productor: exactamente el mismo gutter exterior. */
			body.wcfmmp-store-page.elmercado-child-theme #content > .woostify-container,
			body.wcfmmp-store-page.elmercado-child-theme .site-content > .woostify-container {
				box-sizing: border-box !important;
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

			/* Cabeceras interiores: siempre dentro del flujo, sin compensaciones al hacer scroll. */
			body.elmercado-child-theme :is(
				.emo-journal-hero,
				.emo-journal-hero__inner,
				.emo-producers-intro,
				.emo-contact-layout,
				.emo-contact-aside
			) {
				position: relative !important;
				top: auto !important;
				bottom: auto !important;
				transform: none !important;
				translate: none !important;
				transition: none !important;
				animation: none !important;
			}
			body.elmercado-child-theme :is(.elmercado-compact-producers,.elmercado-contact-page,.elmercado-editorial-content) .site-content {
				transform: none !important;
				translate: none !important;
				transition: none !important;
			}

			/* Contraste inequívoco en el bloque editorial verde de la Home. */
			body.home.elmercado-child-theme .emo-story__panel,
			body.home.elmercado-child-theme .emo-story__panel :is(h2,h3,strong,span,a),
			body.home.elmercado-child-theme .emo-story__panel .emo-kicker,
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				color: #fffdf8 !important;
			}
			body.home.elmercado-child-theme .emo-story__panel p {
				color: rgba(255,253,248,.86) !important;
			}
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				text-decoration-color: rgba(255,253,248,.42) !important;
			}

			/* Productos destacados: sin flechas; el siguiente producto visible comunica el gesto. */
			body.home.elmercado-child-theme .emo-featured-products :is(
				.slick-arrow,.swiper-button-prev,.swiper-button-next,.owl-nav,.tns-controls,
				.wc-block-components-product-carousel__button,.products-carousel-nav
			) {
				display: none !important;
			}

			/* Drawer canónico, con dimensiones propias: no depende de estilos heredados del tema. */
			@media (max-width: 1100px) {
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) {
					display: block !important;
					position: fixed !important;
					inset: 0 !important;
					width: 100vw !important;
					height: 100dvh !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
					z-index: 10020 !important;
					background: rgba(12,31,25,.38) !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-panel {
					display: flex !important;
					flex-direction: column !important;
					position: absolute !important;
					top: 0 !important;
					right: 0 !important;
					bottom: 0 !important;
					width: min(390px, calc(100vw - 32px)) !important;
					height: 100% !important;
					padding: 18px !important;
					background: #fffdf8 !important;
					box-shadow: -18px 0 50px rgba(12,31,25,.14) !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-content {
					display: block !important;
					flex: 1 1 auto !important;
					overflow-y: auto !important;
					visibility: visible !important;
					opacity: 1 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) :is(.widget-area,.widget) {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme { --emo-page-gutter: 16px; }

				/* Carrusel táctil: una tarjeta completa y una porción clara de la siguiente. */
				body.home.elmercado-child-theme .emo-featured-products .woocommerce {
					overflow: visible !important;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products {
					display: flex !important;
					flex-wrap: nowrap !important;
					gap: 14px !important;
					width: 100% !important;
					margin: 0 !important;
					padding: 2px 0 10px !important;
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
		<script id="elmercado-premium-visual-system-js-01049">
		(() => {
			'use strict';

			if (document.body.classList.contains('home')) {
				document.querySelectorAll('.emo-featured-products .slick-arrow,.emo-featured-products .swiper-button-prev,.emo-featured-products .swiper-button-next,.emo-featured-products .owl-nav,.emo-featured-products .tns-controls').forEach((node) => node.setAttribute('hidden',''));
			}

			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;

			const canonicalToggle = document.getElementById('emo-premium-filter-toggle');
			const canonicalShell = document.getElementById('emo-premium-filter-shell');
			const canonicalContent = canonicalShell?.querySelector('.emo-mobile-filter-content');
			if (!canonicalToggle || !canonicalShell || !canonicalContent) return;

			const compact = () => matchMedia('(max-width:1100px)').matches;
			const getSidebar = () => canonicalContent.querySelector('#secondary.widget-area,.shop-widget-area,.widget-area')
				|| document.querySelector('#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area');

			const normalizeSidebar = (sidebar) => {
				if (!sidebar) return;
				sidebar.style.setProperty('display','block','important');
				sidebar.style.setProperty('visibility','visible','important');
				sidebar.style.setProperty('opacity','1','important');
				sidebar.style.setProperty('transform','none','important');
			};

			/* Módulos antiguos pueden reconstruir drawers tras DOM ready. Dejamos sólo la interfaz canónica. */
			const pruneLegacy = () => {
				document.querySelectorAll('.emo-mobile-filter-toggle').forEach((node) => {
					if (node !== canonicalToggle) node.remove();
				});
				document.querySelectorAll('.emo-mobile-filter-shell').forEach((node) => {
					if (node === canonicalShell) return;
					const sidebar = node.querySelector('#secondary.widget-area,.shop-widget-area,.widget-area');
					if (sidebar && !canonicalContent.querySelector('#secondary.widget-area,.shop-widget-area,.widget-area')) {
						canonicalContent.append(sidebar);
						normalizeSidebar(sidebar);
					}
					node.remove();
				});
				if (compact()) {
					const sidebar = getSidebar();
					if (sidebar && sidebar.parentElement !== canonicalContent) canonicalContent.append(sidebar);
					normalizeSidebar(sidebar);
				}
			};

			const closeDrawer = (focus = false) => {
				canonicalShell.hidden = true;
				canonicalShell.style.setProperty('display','none','important');
				canonicalToggle.setAttribute('aria-expanded','false');
				document.documentElement.classList.remove('emo-shop-filter-open');
				body.classList.remove('emo-shop-filter-open');
				if (focus && compact()) canonicalToggle.focus();
			};

			const openDrawer = () => {
				if (!compact()) return;
				pruneLegacy();
				const sidebar = getSidebar();
				if (sidebar && sidebar.parentElement !== canonicalContent) canonicalContent.append(sidebar);
				normalizeSidebar(sidebar);
				canonicalShell.hidden = false;
				canonicalShell.style.setProperty('display','block','important');
				canonicalShell.style.setProperty('visibility','visible','important');
				canonicalShell.style.setProperty('opacity','1','important');
				canonicalShell.style.setProperty('pointer-events','auto','important');
				canonicalToggle.setAttribute('aria-expanded','true');
				document.documentElement.classList.add('emo-shop-filter-open');
				body.classList.add('emo-shop-filter-open');
				requestAnimationFrame(() => canonicalShell.querySelector('.emo-mobile-filter-close')?.focus());
			};

			/* Captura antes de cualquier listener legado y toma control completo del trigger canónico. */
			document.addEventListener('click', (event) => {
				const toggle = event.target.closest?.('#emo-premium-filter-toggle');
				if (toggle) {
					event.preventDefault();
					event.stopImmediatePropagation();
					canonicalToggle.getAttribute('aria-expanded') === 'true' ? closeDrawer(true) : openDrawer();
					return;
				}
				const close = event.target.closest?.('#emo-premium-filter-shell .emo-mobile-filter-close');
				if (close || event.target === canonicalShell) {
					event.preventDefault();
					event.stopImmediatePropagation();
					closeDrawer(true);
				}
			}, true);

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && !canonicalShell.hidden) {
					event.preventDefault();
					closeDrawer(true);
				}
			}, true);

			[0, 60, 180, 360, 600, 1000].forEach((delay) => setTimeout(pruneLegacy, delay));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
