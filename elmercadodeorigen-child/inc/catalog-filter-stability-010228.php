<?php
/**
 * Estabilidad definitiva de filtros para Tienda y tiendas de productor.
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
	return function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225();
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_filter_stability_target_010228() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-stability-010228">
			/* Escritorio: una sola geometria y sticky nativo, sin transforms. */
			@media (min-width:1101px) {
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 {
					position:sticky !important;
					top:94px !important;
					right:auto !important;
					bottom:auto !important;
					left:auto !important;
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

			/* Slider: la misma regla final y la misma geometria para ambos rails. */
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227 .widget_price_filter .price_slider,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 #emo-vendor-filters .emo-vendor-price-filter.widget_price_filter .price_slider {
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
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227 .widget_price_filter .ui-slider-range,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 #emo-vendor-filters .emo-vendor-price-filter.widget_price_filter .ui-slider-range {
				top:0 !important;
				height:4px !important;
				border:0 !important;
				border-radius:999px !important;
				background:#2f7d5d !important;
				box-shadow:none !important;
			}
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227 .widget_price_filter .ui-slider-handle,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 #emo-vendor-filters .emo-vendor-price-filter.widget_price_filter .ui-slider-handle {
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

			/* Bloque inferior del precio sin floats y con el mismo ritmo. */
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area).emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225.emo-filter-rail-parity-010227 #emo-vendor-filters .emo-vendor-price-filter.widget_price_filter .price_slider_amount {
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
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount .clear,
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount::before,
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount::after {
				display:none !important;
				content:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_slider_amount .button {
				float:none !important;
				flex:0 0 auto !important;
				min-height:38px !important;
				margin:0 !important;
				padding:0 14px !important;
				border-radius:999px !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:750 !important;
				line-height:1 !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-parity-010227 .widget_price_filter .price_label {
				position:static !important;
				display:block !important;
				float:none !important;
				flex:1 1 auto !important;
				min-width:0 !important;
				margin:0 0 0 auto !important;
				padding:0 !important;
				color:#42564e !important;
				font-size:11.5px !important;
				font-weight:700 !important;
				line-height:1.25 !important;
				text-align:right !important;
				white-space:nowrap !important;
			}

			/* Hover y foco: fondo comun + texto subrayado, igual en ambos. */
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .emo-filter-row-parity-010227:hover > a,
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .emo-filter-row-parity-010227 > a:hover,
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .emo-filter-row-parity-010227 > a:focus-visible,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 .emo-filter-row-parity-010227:hover > a,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 .emo-filter-row-parity-010227 > a:hover,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 .emo-filter-row-parity-010227 > a:focus-visible {
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

			/* Drawer movil: neutraliza por completo el antiguo drawer derecho. */
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
			const desktop = matchMedia('(min-width:1101px)');
			const locks = new WeakMap();
			const watched = new WeakSet();
			let resizeTimer = 0;

			const put = (node, property, value) => {
				if (!node) return;
				if (node.style.getPropertyValue(property).trim() === value && node.style.getPropertyPriority(property) === 'important') return;
				node.style.setProperty(property, value, 'important');
			};
			const apply = (rail, values) => {
				locks.set(rail, values);
				Object.entries(values).forEach(([key, value]) => put(rail, key, value));
				if (watched.has(rail)) return;
				watched.add(rail);
				new MutationObserver(() => {
					const current = locks.get(rail);
					if (current) Object.entries(current).forEach(([key, value]) => put(rail, key, value));
				}).observe(rail, {attributes:true, attributeFilter:['style']});
			};
			const docTop = node => node ? node.getBoundingClientRect().top + scrollY : 0;

			function parts() {
				const vendorStore = document.querySelector('#wcfmmp-store');
				if (vendorStore) {
					const rail = vendorStore.querySelector('.emo-vendor-filter-rail-010225');
					const toolbar = vendorStore.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting,.elmercado-vendor-toolbar');
					const host = vendorStore.querySelector('.body_area');
					return rail && toolbar && host ? {rail, toolbar, host} : null;
				}
				if (!document.body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag')) return null;
				const rail = document.querySelector('#secondary.widget-area,.shop-widget-area');
				const toolbar = document.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting');
				const host = document.querySelector('#content.site-content > .woostify-container');
				return rail && toolbar && host ? {rail, toolbar, host} : null;
			}

			function lockDesktop() {
				if (!desktop.matches) return;
				const p = parts();
				if (!p) return;
				const marginTop = Math.max(0, Math.round(docTop(p.toolbar) - docTop(p.host)));
				apply(p.rail, {
					'position':'sticky','top':'94px','right':'auto','bottom':'auto','left':'auto',
					'margin-top':`${marginTop}px`,'margin-right':'0px','margin-bottom':'0px','margin-left':'0px',
					'height':'auto','max-height':'calc(100dvh - 112px)','overflow-x':'hidden','overflow-y':'auto',
					'transform':'none','transition':'none','will-change':'auto'
				});
			}

			function lockMobileVendor() {
				if (desktop.matches) return;
				const rail = document.querySelector('.emo-vendor-mobile-filter-shell-010227 .emo-vendor-filter-rail-010225');
				if (!rail) return;
				apply(rail, {
					'position':'static','top':'auto','right':'auto','bottom':'auto','left':'auto',
					'width':'100%','min-width':'0px','max-width':'none','height':'auto','max-height':'none',
					'overflow':'visible','margin':'0px','padding':'0px','border':'0px','box-shadow':'none','transform':'none'
				});
			}

			/* Una sola medicion al montar; despues el sticky es puramente CSS. */
			requestAnimationFrame(() => requestAnimationFrame(() => {
				lockDesktop();
				lockMobileVendor();
			}));
			window.addEventListener('pageshow', () => requestAnimationFrame(lockDesktop), {passive:true});
			window.addEventListener('resize', () => {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(() => {
					lockDesktop();
					lockMobileVendor();
				}, 120);
			}, {passive:true});
			document.addEventListener('click', event => {
				if (event.target.closest('.emo-vendor-mobile-filter-toggle-010227')) requestAnimationFrame(() => requestAnimationFrame(lockMobileVendor));
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
