<?php
/**
 * Estabilidad definitiva de los filtros de Tienda y productor.
 *
 * - elimina el reajuste visual continuo del rail en escritorio;
 * - fija una sola alineacion respecto a la barra de resultados/ordenacion;
 * - mantiene el rail sticky y acota su altura para evitar saltos con filtros largos;
 * - hace que el precio del productor use exactamente el slider visual de Tienda;
 * - restaura el subrayado de hover/foco en enlaces de filtros;
 * - neutraliza la geometria inline antigua cuando el rail del productor entra
 *   en el drawer movil nuevo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_catalog_filter_stability_target_010228(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}

	return function_exists( 'elmercado_vendor_store_is_request_010225' )
		&& elmercado_vendor_store_is_request_010225();
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_filter_stability_target_010228() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-stability-010228">
			/*
			 * Escritorio: mismo comportamiento en Tienda y productor.
			 * El offset inicial lo fija una sola vez el script; despues el navegador
			 * gobierna el sticky sin mediciones continuas ni transforms.
			 */
			@media (min-width:1101px) {
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 {
					position:sticky !important;
					top:94px !important;
					bottom:auto !important;
					align-self:flex-start !important;
					height:auto !important;
					max-height:calc(100dvh - 112px) !important;
					overflow-x:hidden !important;
					overflow-y:auto !important;
					overscroll-behavior:contain !important;
					scrollbar-gutter:stable !important;
					transform:none !important;
					transition:none !important;
					will-change:auto !important;
				}

				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .wcfmmp-store-page-wrap,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					overflow:visible !important;
					overflow-x:visible !important;
					overflow-y:visible !important;
				}
			}

			/*
			 * Slider de precio unico. El productor ya usa widget_price_filter, asi
			 * que forzamos aqui los mismos valores finales que gobiernan Tienda.
			 */
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider,
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_wrapper .ui-slider-horizontal {
				position:relative !important;
				height:4px !important;
				min-height:4px !important;
				margin:1.25rem 8px 1.5rem !important;
				padding:0 !important;
				border:0 !important;
				border-radius:999px !important;
				background:#d9e1dc !important;
				box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .ui-slider-range {
				top:0 !important;
				height:4px !important;
				border:0 !important;
				border-radius:999px !important;
				background:#2f7d5d !important;
				box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .ui-slider-handle {
				top:-6px !important;
				width:16px !important;
				height:16px !important;
				min-width:16px !important;
				min-height:16px !important;
				margin-top:0 !important;
				margin-left:-8px !important;
				padding:0 !important;
				box-sizing:border-box !important;
				border:3px solid #fff !important;
				border-radius:50% !important;
				background:#2f7d5d !important;
				box-shadow:0 1px 5px rgba(13,33,27,.28) !important;
				transform:none !important;
			}

			/* El bloque inferior del precio tambien queda comun y sin floats heredados. */
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
				width:100% !important;
				min-height:40px !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				clear:both !important;
				font-family:inherit !important;
				text-align:left !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount::before,
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount::after,
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount .clear {
				display:none !important;
				content:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount .button {
				flex:0 0 auto !important;
				min-width:0 !important;
				min-height:38px !important;
				margin:0 !important;
				padding:0 14px !important;
				float:none !important;
				border-radius:999px !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:750 !important;
				line-height:1 !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_label {
				position:static !important;
				display:block !important;
				flex:1 1 auto !important;
				min-width:0 !important;
				margin:0 0 0 auto !important;
				padding:0 !important;
				float:none !important;
				color:#42564e !important;
				font-family:inherit !important;
				font-size:11.5px !important;
				font-weight:700 !important;
				line-height:1.25 !important;
				letter-spacing:0 !important;
				text-align:right !important;
				text-transform:none !important;
				white-space:nowrap !important;
			}

			/* Hover/foco igual al comportamiento que ya tenia Tienda. */
			html body.elmercado-child-theme .emo-filter-row-parity-010227:hover > a,
			html body.elmercado-child-theme .emo-filter-row-parity-010227 > a:hover,
			html body.elmercado-child-theme .emo-filter-row-parity-010227 > a:focus-visible {
				color:#2f7d5d !important;
				text-decoration:underline !important;
				text-decoration-thickness:1px !important;
				text-underline-offset:3px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row a:hover,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row a:focus-visible {
				color:#2f7d5d !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}

			/* En el drawer movil manda solo la geometria nueva, no el lock antiguo. */
			@media (max-width:991px) {
				html body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-content .emo-vendor-filter-rail-010225 {
					position:static !important;
					inset:auto !important;
					width:100% !important;
					min-width:0 !important;
					max-width:none !important;
					height:auto !important;
					max-height:none !important;
					overflow:visible !important;
					margin:0 !important;
					padding:0 !important;
					border:0 !important;
					box-shadow:none !important;
					transform:none !important;
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
		if ( ! elmercado_catalog_filter_stability_target_010228() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-filter-stability-script-010228">
		(() => {
			'use strict';

			const desktopQuery = window.matchMedia('(min-width:1101px)');
			const locks = new WeakMap();
			const observers = new WeakMap();
			let resizeTimer = 0;

			const setImportant = (node, property, value) => {
				if (!node) return;
				const current = node.style.getPropertyValue(property).trim();
				const priority = node.style.getPropertyPriority(property);
				if (current === value && priority === 'important') return;
				node.style.setProperty(property, value, 'important');
			};

			const desktopLock = (rail, marginTop) => {
				const values = {
					'position':'sticky',
					'top':'94px',
					'right':'auto',
					'bottom':'auto',
					'left':'auto',
					'margin-top':`${marginTop}px`,
					'margin-right':'0px',
					'margin-bottom':'0px',
					'margin-left':'0px',
					'height':'auto',
					'max-height':'calc(100dvh - 112px)',
					'overflow-x':'hidden',
					'overflow-y':'auto',
					'transform':'none',
					'transition':'none',
					'will-change':'auto'
				};
				locks.set(rail, values);
				Object.entries(values).forEach(([property, value]) => setImportant(rail, property, value));
			};

			const mobileVendorLock = (rail) => {
				const values = {
					'position':'static',
					'top':'auto',
					'right':'auto',
					'bottom':'auto',
					'left':'auto',
					'width':'100%',
					'min-width':'0px',
					'max-width':'none',
					'height':'auto',
					'max-height':'none',
					'overflow':'visible',
					'margin':'0px',
					'padding':'0px',
					'border':'0px',
					'box-shadow':'none',
					'transform':'none'
				};
				locks.set(rail, values);
				Object.entries(values).forEach(([property, value]) => setImportant(rail, property, value));
			};

			const enforceLock = (rail) => {
				const values = locks.get(rail);
				if (!values) return;
				Object.entries(values).forEach(([property, value]) => setImportant(rail, property, value));
			};

			const observeRail = (rail) => {
				if (!rail || observers.has(rail)) return;
				const observer = new MutationObserver(() => enforceLock(rail));
				observer.observe(rail, { attributes:true, attributeFilter:['style'] });
				observers.set(rail, observer);
			};

			const documentTop = (node) => node ? node.getBoundingClientRect().top + window.scrollY : 0;

			const shopParts = () => {
				if (!document.body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || document.body.classList.contains('wcfmmp-store-page')) return null;
				const rail = document.querySelector('#secondary.widget-area,.shop-widget-area');
				const toolbar = document.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting');
				const host = document.querySelector('#content.site-content > .woostify-container');
				return rail && toolbar && host ? { rail, toolbar, host } : null;
			};

			const vendorParts = () => {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return null;
				const rail = document.querySelector('.emo-vendor-filter-rail-010225');
				const toolbar = store.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting,.elmercado-vendor-toolbar');
				const host = store.querySelector('.body_area');
				return rail && toolbar && host ? { rail, toolbar, host } : null;
			};

			const lockDesktopPair = (parts) => {
				if (!parts) return;
				const offset = Math.max(0, Math.round(documentTop(parts.toolbar) - documentTop(parts.host)));
				desktopLock(parts.rail, offset);
				observeRail(parts.rail);
			};

			const stabilize = () => {
				const shop = shopParts();
				const vendor = vendorParts();

				if (desktopQuery.matches) {
					lockDesktopPair(shop);
					lockDesktopPair(vendor);
					return;
				}

				if (vendor?.rail?.closest('.emo-vendor-mobile-filter-shell-010227')) {
					mobileVendorLock(vendor.rail);
					observeRail(vendor.rail);
				}
			};

			const delayedStabilize = () => {
				requestAnimationFrame(() => requestAnimationFrame(stabilize));
			};

			delayedStabilize();
			setTimeout(delayedStabilize, 160);
			setTimeout(delayedStabilize, 700);
			setTimeout(delayedStabilize, 1450);
			window.addEventListener('load', delayedStabilize, { once:true, passive:true });
			window.addEventListener('pageshow', delayedStabilize, { passive:true });
			window.addEventListener('resize', () => {
				window.clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(delayedStabilize, 140);
			}, { passive:true });
			document.fonts?.ready?.then(delayedStabilize).catch(() => {});

			/* Solo detecta el montaje del drawer; no vuelve a medir posiciones. */
			if (!desktopQuery.matches) {
				const mountObserver = new MutationObserver(() => {
					const vendor = vendorParts();
					if (vendor?.rail?.closest('.emo-vendor-mobile-filter-shell-010227')) {
						mobileVendorLock(vendor.rail);
						observeRail(vendor.rail);
					}
				});
				mountObserver.observe(document.body, { childList:true, subtree:true });
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
