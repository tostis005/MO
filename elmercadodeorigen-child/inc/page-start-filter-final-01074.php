<?php
/**
 * Alineación estructural de páginas y geometría real del filtro móvil 0.10.79.
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
		<style id="elmercado-page-start-filter-final-01079">
			body.elmercado-child-theme {
				--emo-content-start-gap: clamp(18px, 1.7vw, 24px);
			}

			/*
			 * Todas las páginas que ya no usan el page-header nativo arrancan desde
			 * el mismo punto del flujo. El único aire superior vive en site-content.
			 * No hay transformaciones, mediciones ni compensaciones al hacer scroll.
			 */
			html body.elmercado-child-theme.page:not(.home) .site-content,
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .site-content {
				padding-top: var(--emo-content-start-gap) !important;
			}

			html body.elmercado-child-theme.page:not(.home) :is(
				.site-content > .woostify-container,
				#content > .woostify-container,
				#primary,
				.content-area,
				main.site-main,
				article.page,
				article.page > .entry-content,
				.entry-content > .woocommerce
			),
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(
				.site-content > .woostify-container,
				#content > .woostify-container,
				#primary,
				.content-area,
				main.site-main
			) {
				margin-top: 0 !important;
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			/* Primer bloque útil: misma coordenada superior en Tienda, About y compra. */
			html body.elmercado-child-theme.woocommerce-shop .emo-shop-lead,
			html body.elmercado-child-theme.elmercado-about-page :is(.emo-about-layout,.emo-about-intro),
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro),
			html body.elmercado-child-theme:is(.elmercado-compact-contact,.elmercado-contact-page) .emo-contact-layout,
			html body.elmercado-child-theme:is(.elmercado-compact-producers,.elmercado-producers-page,.wcfm-store-list-page) .emo-producers-intro {
				margin-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme.woocommerce-shop .emo-shop-lead,
			html body.elmercado-child-theme.elmercado-about-page .emo-about-layout,
			html body.elmercado-child-theme.elmercado-about-page .emo-about-intro {
				padding-top: 0 !important;
			}

			/* El estado is-scrolled sólo cambia el acabado del header, nunca la geometría. */
			html body.elmercado-child-theme.is-scrolled:not(.home) :is(
				#content,
				.site-content,
				.site-content > .woostify-container,
				#primary,
				.content-area,
				main.site-main,
				article.page,
				.entry-content,
				.woocommerce,
				.emo-shop-lead,
				.emo-about-layout,
				.emo-about-intro,
				.emo-cart-intro,
				.emo-checkout-intro,
				.emo-contact-layout,
				.emo-producers-intro
			) {
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme {
					--emo-content-start-gap: 18px;
				}

				/* Drawer: más ancho útil en móviles estrechos y una cabecera compacta. */
				html body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-panel {
					box-sizing: border-box !important;
					width: min(calc(100vw - 14px), 360px) !important;
					max-width: 360px !important;
					border-radius: 20px 0 0 20px !important;
					overflow: hidden !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-head {
					box-sizing: border-box !important;
					min-height: 70px !important;
					margin: 0 !important;
					padding: max(14px, env(safe-area-inset-top,0px)) 14px 12px 18px !important;
					background: #fffdf8 !important;
					border-bottom: 1px solid rgba(23,63,50,.10) !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-title {
					width: auto !important;
					height: auto !important;
					min-height: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					background: transparent !important;
					border: 0 !important;
					border-radius: 0 !important;
					box-shadow: none !important;
					color: #173f32 !important;
					font-family: Georgia, "Times New Roman", serif !important;
					font-size: 22px !important;
					font-weight: 700 !important;
					line-height: 1.08 !important;
					text-align: left !important;
					text-transform: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-close {
					box-sizing: border-box !important;
					width: 42px !important;
					height: 42px !important;
					min-width: 42px !important;
					min-height: 42px !important;
					margin: 0 !important;
					padding: 0 !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					box-shadow: none !important;
					color: #fffdf8 !important;
					font-size: 24px !important;
					line-height: 1 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content {
					box-sizing: border-box !important;
					padding: 16px 16px calc(26px + env(safe-area-inset-bottom,0px)) !important;
					overflow-x: hidden !important;
				}

				/* Cada widget contiene su propio flujo: ningún float puede pisar al siguiente. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget {
					box-sizing: border-box !important;
					display: flow-root !important;
					width: 100% !important;
					height: auto !important;
					min-height: 0 !important;
					margin: 0 0 16px !important;
					padding: 0 0 16px !important;
					background: transparent !important;
					border: 0 !important;
					border-bottom: 1px solid rgba(23,63,50,.10) !important;
					border-radius: 0 !important;
					box-shadow: none !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget:last-child {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
					border-bottom: 0 !important;
				}

				/* Una sola plantilla para TODOS los encabezados de sección. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget > :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading),
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget.widget_price_filter > .widget-title {
					box-sizing: border-box !important;
					display: flex !important;
					width: 100% !important;
					height: 38px !important;
					min-height: 38px !important;
					max-height: 38px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 0 16px !important;
					padding: 0 12px !important;
					background: #173f32 !important;
					border: 0 !important;
					border-radius: 11px !important;
					box-shadow: none !important;
					color: #fffdf8 !important;
					font-family: inherit !important;
					font-size: 11px !important;
					font-weight: 820 !important;
					letter-spacing: .045em !important;
					line-height: 1 !important;
					text-align: center !important;
					text-transform: uppercase !important;
					white-space: normal !important;
				}

				/* Precio: pista y tiradores centrados con geometría independiente del navegador. */
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter {
					display: flow-root !important;
					margin-bottom: 16px !important;
					padding-bottom: 16px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter form,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_wrapper {
					box-sizing: border-box !important;
					width: 100% !important;
					height: auto !important;
					min-height: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					float: none !important;
					clear: both !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_wrapper {
					display: grid !important;
					grid-template-columns: minmax(0,1fr) !important;
					gap: 18px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_wrapper::before,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_wrapper::after,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount::before,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount::after {
					display: none !important;
					content: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider-horizontal {
					box-sizing: border-box !important;
					position: relative !important;
					width: auto !important;
					height: 4px !important;
					min-height: 4px !important;
					margin: 10px 12px 0 !important;
					padding: 0 !important;
					background: #d8e2dc !important;
					border: 0 !important;
					border-radius: 999px !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider .ui-slider-range {
					position: absolute !important;
					top: 0 !important;
					height: 100% !important;
					margin: 0 !important;
					padding: 0 !important;
					background: #2f6650 !important;
					border: 0 !important;
					border-radius: 999px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider .ui-slider-handle {
					box-sizing: border-box !important;
					position: absolute !important;
					top: 50% !important;
					width: 20px !important;
					height: 20px !important;
					min-width: 20px !important;
					min-height: 20px !important;
					margin-top: 0 !important;
					margin-left: -10px !important;
					padding: 0 !important;
					background: #fffdf8 !important;
					border: 3px solid #2f6650 !important;
					border-radius: 50% !important;
					box-shadow: 0 2px 7px rgba(23,63,50,.14) !important;
					transform: translateY(-50%) !important;
					touch-action: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount {
					box-sizing: border-box !important;
					position: static !important;
					display: flex !important;
					width: 100% !important;
					height: auto !important;
					min-height: 42px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 12px !important;
					margin: 0 !important;
					padding: 0 !important;
					float: none !important;
					clear: both !important;
					overflow: visible !important;
					text-align: right !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount .button {
					box-sizing: border-box !important;
					flex: 0 0 auto !important;
					min-width: 94px !important;
					min-height: 42px !important;
					margin: 0 !important;
					padding: 0 17px !important;
					float: none !important;
					border-radius: 999px !important;
					font-size: 12px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_label {
					position: static !important;
					display: block !important;
					flex: 1 1 auto !important;
					min-width: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					float: none !important;
					color: #43564e !important;
					font-size: 12px !important;
					font-weight: 720 !important;
					line-height: 1.25 !important;
					text-align: right !important;
					white-space: nowrap !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount .clear {
					display: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter + .widget {
					margin-top: 0 !important;
					padding-top: 0 !important;
					clear: both !important;
				}

				/* Categorías y etiquetas mantienen densidad táctil sin tocarse entre sí. */
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.widget_product_categories,.wc-block-product-categories) li > a {
					min-height: 44px !important;
					padding-block: 9px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.tagcloud,.wp-block-tag-cloud) {
					gap: 8px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.tagcloud,.wp-block-tag-cloud) a {
					min-height: 36px !important;
					padding: 7px 12px !important;
					font-size: 11.5px !important;
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
		if ( is_admin() || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}
		?>
		<script id="elmercado-price-filter-reinit-01079">
		(() => {
			'use strict';
			const initPriceFilter = () => {
				const slider = document.querySelector('#emo-premium-filter-shell .widget_price_filter .price_slider');
				if (!slider || !window.jQuery) return;
				if (!slider.classList.contains('ui-slider') || !slider.querySelector('.ui-slider-handle')) {
					window.jQuery(document.body).trigger('init_price_filter');
				}
			};
			const scheduleInit = () => {
				window.setTimeout(initPriceFilter, 0);
				window.setTimeout(initPriceFilter, 120);
			};
			document.addEventListener('click', (event) => {
				if (event.target.closest?.('#emo-premium-filter-toggle')) scheduleInit();
			}, true);
			window.addEventListener('load', scheduleInit, { once: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
