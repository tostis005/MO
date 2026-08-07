<?php
/**
 * Estabiliza el ritmo de la tienda de productor y unifica los filtros de tienda.
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
		<style id="elmercado-store-vendor-layout-final-01037">
			/*
			 * Tienda de productor: el espacio pertenece al flujo normal.
			 * No se corrige la posición con transformaciones dependientes del viewport.
			 */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .tab_area {
				margin-bottom: 20px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
				margin-top: 0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-sorting-normalized {
				margin: 0 !important;
				padding: 0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
				position: relative !important;
				inset: auto !important;
				min-height: 54px !important;
				margin: 0 0 18px !important;
				padding: 10px 12px !important;
				transform: none !important;
				transition: none !important;
				border: 1px solid rgba(23,63,50,.12) !important;
				border-radius: 14px !important;
				background: #fff !important;
				box-shadow: 0 8px 24px rgba(13,33,27,.045) !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products {
				margin-top: 0 !important;
				padding-top: 0 !important;
				transform: none !important;
				transition: none !important;
			}

			/* Cierres del menú y del panel de filtros: círculo real, no píldora. */
			html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close,
			html body.elmercado-child-theme .emo-mobile-filter-close {
				box-sizing: border-box !important;
				display: grid !important;
				width: 42px !important;
				height: 42px !important;
				min-width: 42px !important;
				max-width: 42px !important;
				min-height: 42px !important;
				max-height: 42px !important;
				flex: 0 0 42px !important;
				aspect-ratio: 1 / 1 !important;
				padding: 0 !important;
				place-items: center !important;
				border-radius: 50% !important;
				line-height: 1 !important;
			}

			/* Titulares de filtros: bloque principal verde, centrado y legible. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(
				#secondary.widget-area,
				.shop-widget-area,
				.emo-mobile-filter-content .widget-area
			) :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading),
			body.elmercado-child-theme .emo-mobile-filter-panel .widget :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading) {
				display: flex !important;
				width: 100% !important;
				min-height: 38px !important;
				align-items: center !important;
				justify-content: center !important;
				margin: 0 0 12px !important;
				padding: 8px 12px !important;
				border: 0 !important;
				border-radius: 10px !important;
				background: #173f32 !important;
				box-shadow: none !important;
				color: #fff !important;
				font-size: 12px !important;
				font-weight: 800 !important;
				line-height: 1.25 !important;
				letter-spacing: .035em !important;
				text-align: center !important;
				text-transform: uppercase !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(
				#secondary.widget-area,
				.shop-widget-area,
				.emo-mobile-filter-content .widget-area
			) .widget {
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
			}

			/* En escritorio permanece el panel de filtros real; desaparece el trigger móvil redundante. */
			@media (min-width: 992px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-shell {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) {
					display: block !important;
					position: sticky !important;
					top: 106px !important;
					height: max-content !important;
					margin: 0 !important;
					padding: 14px !important;
					border: 1px solid rgba(23,63,50,.10) !important;
					border-radius: 16px !important;
					background: #fff !important;
					box-shadow: 0 10px 30px rgba(13,33,27,.05) !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .tab_area {
					margin-bottom: 16px !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					min-height: 48px !important;
					margin: 0 0 14px !important;
					padding: 7px 8px !important;
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
		<script id="elmercado-vendor-static-rhythm-01037">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store || !document.body.classList.contains('wcfmmp-store-page')) return;

			let frame = 0;
			const settle = () => {
				if (frame) cancelAnimationFrame(frame);
				frame = requestAnimationFrame(() => requestAnimationFrame(() => {
					frame = 0;
					const toolbar = store.querySelector('.elmercado-vendor-toolbar');
					const products = store.querySelector('ul.products');
					if (toolbar) {
						toolbar.style.setProperty('transform', 'none', 'important');
						toolbar.style.setProperty('margin-bottom', window.matchMedia('(max-width: 991px)').matches ? '14px' : '18px', 'important');
						delete toolbar.dataset.elmercadoTabGap;
					}
					if (products) {
						products.style.setProperty('transform', 'none', 'important');
						products.style.setProperty('margin-bottom', '0', 'important');
						delete products.dataset.elmercadoToolbarGap;
					}
				}));
			};

			settle();
			window.addEventListener('load', settle, { once: true });
			window.addEventListener('resize', settle, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
