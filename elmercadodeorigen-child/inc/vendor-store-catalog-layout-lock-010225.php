<?php
/**
 * Cierre geométrico para el rail de filtros de las tiendas de productor.
 *
 * WCFM y varias capas históricas del tema escriben display/position sobre el
 * sidebar después de construirlo. Este módulo, cargado al final, fija el rail
 * nuevo sin modificar el resto de la ficha del productor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-store-layout-lock-010225">
			@media (min-width: 1101px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) 250px !important;
					gap: 0 34px !important;
					align-items: start !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					grid-column: 2 !important;
					grid-row: 1 !important;
					box-sizing: border-box !important;
					width: 250px !important;
					min-width: 250px !important;
					max-width: 250px !important;
					height: auto !important;
					min-height: 1px !important;
					float: none !important;
					position: sticky !important;
					top: 94px !important;
					inset-inline: auto !important;
					transform: none !important;
					pointer-events: auto !important;
					overflow: visible !important;
					margin: 0 !important;
					padding: 18px !important;
					border: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store :is(
					.right_side,
					.right_side_full,
					.products-wrapper,
					.wcfmmp-store-product,
					.product_area
				) {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					grid-column: 1 !important;
					grid-row: 1 !important;
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
					max-width: none !important;
					float: none !important;
					position: static !important;
					transform: none !important;
					margin: 0 !important;
				}
			}

			@media (max-width: 1100px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					box-sizing: border-box !important;
					pointer-events: auto !important;
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
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-store-layout-lock-js-010225">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;

			const rail = () => store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225');
			const products = () => store.querySelector('.right_side, .right_side_full, .products-wrapper, .wcfmmp-store-product, .product_area');
			const body = () => store.querySelector('.body_area');

			function lockLayout() {
				const sidebar = rail();
				const productHost = products();
				const bodyHost = body();
				if (!sidebar || !productHost || !bodyHost) return;

				const desktop = window.matchMedia('(min-width: 1101px)').matches;
				sidebar.style.setProperty('display', 'block', 'important');
				sidebar.style.setProperty('visibility', 'visible', 'important');
				sidebar.style.setProperty('opacity', '1', 'important');
				sidebar.style.setProperty('pointer-events', 'auto', 'important');

				if (desktop) {
					bodyHost.style.setProperty('display', 'grid', 'important');
					bodyHost.style.setProperty('grid-template-columns', 'minmax(0, 1fr) 250px', 'important');
					bodyHost.style.setProperty('column-gap', '34px', 'important');
					bodyHost.style.setProperty('align-items', 'start', 'important');

					productHost.style.setProperty('grid-column', '1', 'important');
					productHost.style.setProperty('grid-row', '1', 'important');
					productHost.style.setProperty('display', 'block', 'important');
					productHost.style.setProperty('width', '100%', 'important');
					productHost.style.setProperty('min-width', '0', 'important');
					productHost.style.setProperty('max-width', 'none', 'important');
					productHost.style.setProperty('float', 'none', 'important');
					productHost.style.setProperty('position', 'static', 'important');

					sidebar.style.setProperty('grid-column', '2', 'important');
					sidebar.style.setProperty('grid-row', '1', 'important');
					sidebar.style.setProperty('width', '250px', 'important');
					sidebar.style.setProperty('min-width', '250px', 'important');
					sidebar.style.setProperty('max-width', '250px', 'important');
					sidebar.style.setProperty('height', 'auto', 'important');
					sidebar.style.setProperty('position', 'sticky', 'important');
					sidebar.style.setProperty('top', '94px', 'important');
					sidebar.style.setProperty('right', 'auto', 'important');
					sidebar.style.setProperty('bottom', 'auto', 'important');
					sidebar.style.setProperty('left', 'auto', 'important');
					sidebar.style.setProperty('transform', 'none', 'important');
					sidebar.style.setProperty('overflow', 'visible', 'important');
					sidebar.style.setProperty('margin', '0', 'important');
					sidebar.style.setProperty('padding', '18px', 'important');
				} else {
					bodyHost.style.setProperty('display', 'block', 'important');
					productHost.style.setProperty('display', 'block', 'important');
					productHost.style.setProperty('width', '100%', 'important');
					productHost.style.setProperty('max-width', 'none', 'important');
					productHost.style.setProperty('float', 'none', 'important');

					sidebar.style.setProperty('position', 'fixed', 'important');
					sidebar.style.setProperty('z-index', '1000001', 'important');
					sidebar.style.setProperty('top', '0', 'important');
					sidebar.style.setProperty('right', '0', 'important');
					sidebar.style.setProperty('bottom', '0', 'important');
					sidebar.style.setProperty('left', 'auto', 'important');
					sidebar.style.setProperty('width', 'min(360px, 92vw)', 'important');
					sidebar.style.setProperty('min-width', '0', 'important');
					sidebar.style.setProperty('max-width', '92vw', 'important');
					sidebar.style.setProperty('height', '100dvh', 'important');
					sidebar.style.setProperty('overflow', 'auto', 'important');
					sidebar.style.setProperty('margin', '0', 'important');
					sidebar.style.setProperty('padding', '20px 22px 32px', 'important');
					const open = document.documentElement.classList.contains('emo-vendor-filters-open-010225');
					sidebar.style.setProperty('transform', open ? 'translateX(0)' : 'translateX(105%)', 'important');
				}
			}

			const htmlObserver = new MutationObserver(lockLayout);
			htmlObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
			const storeObserver = new MutationObserver(() => requestAnimationFrame(lockLayout));
			storeObserver.observe(store, { childList: true, subtree: true });
			window.addEventListener('resize', lockLayout, { passive: true });
			document.addEventListener('click', (event) => {
				if (event.target.closest('.emo-vendor-filter-toggle-010225, .emo-vendor-filters__close, .emo-vendor-filter-overlay-010225')) {
					requestAnimationFrame(lockLayout);
				}
			});
			lockLayout();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
