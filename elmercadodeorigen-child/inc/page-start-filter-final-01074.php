<?php
/**
 * Alineación final de superficies interiores y geometría del filtro móvil 0.10.77.
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
		<style id="elmercado-page-start-filter-final-01077">
			/*
			 * Quiénes somos comparte la cadencia de las introducciones sobre papel.
			 * Neutralizamos también el contenedor exterior de Woostify: era el último
			 * nivel que seguía sumando aire sobre los wrappers ya normalizados.
			 */
			html body.elmercado-child-theme.elmercado-about-page .site-content,
			html body.elmercado-child-theme.elmercado-about-page .site-content > .woostify-container,
			html body.elmercado-child-theme.elmercado-about-page #content > .woostify-container,
			html body.elmercado-child-theme.elmercado-about-page :is(#primary,.content-area,main.site-main,article.page,.entry-content) {
				margin-top: 0 !important;
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}
			html body.elmercado-child-theme.elmercado-about-page .emo-about-layout {
				margin-top: 0 !important;
				padding-top: max(14px, calc(var(--emo-content-start-gap, 18px) - 4px)) !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}
			html body.elmercado-child-theme.elmercado-about-page .emo-about-intro {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}
			html body.elmercado-child-theme.is-scrolled.elmercado-about-page :is(.site-content,.site-content>.woostify-container,#content>.woostify-container,#primary,.content-area,main.site-main,article.page,.entry-content,.emo-about-layout,.emo-about-intro) {
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 1100px) {
				/* El drawer usa una sola jerarquía visual para todas sus secciones. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget {
					position: relative !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget > :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading):first-child,
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget.widget_price_filter > .widget-title {
					box-sizing: border-box !important;
					display: flex !important;
					width: 100% !important;
					height: 36px !important;
					min-height: 36px !important;
					max-height: 36px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 0 18px !important;
					padding: 0 12px !important;
					background: #173f32 !important;
					border: 0 !important;
					border-radius: 10px !important;
					box-shadow: none !important;
					color: #fffdf8 !important;
					font-family: inherit !important;
					font-size: 10.5px !important;
					font-weight: 820 !important;
					letter-spacing: .045em !important;
					line-height: 1 !important;
					text-align: center !important;
					text-transform: uppercase !important;
				}

				/* Precio: flujo propio y separación real antes de la siguiente sección. */
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter {
					display: block !important;
					padding-bottom: 0 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter form {
					display: block !important;
					margin: 0 !important;
					padding: 0 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_wrapper {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 16px !important;
					margin: 0 !important;
					padding: 0 !important;
					min-height: 0 !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider-horizontal {
					box-sizing: border-box !important;
					position: relative !important;
					width: auto !important;
					height: 4px !important;
					margin: 8px 12px 0 !important;
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
					background: #2f6650 !important;
					border: 0 !important;
					border-radius: 999px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider .ui-slider-handle {
					box-sizing: border-box !important;
					position: absolute !important;
					top: 50% !important;
					width: 18px !important;
					height: 18px !important;
					min-width: 18px !important;
					min-height: 18px !important;
					margin-top: 0 !important;
					margin-left: -9px !important;
					padding: 0 !important;
					background: #fffdf8 !important;
					border: 3px solid #2f6650 !important;
					border-radius: 50% !important;
					box-shadow: 0 2px 7px rgba(23,63,50,.14) !important;
					transform: translateY(-50%) !important;
					touch-action: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount {
					position: static !important;
					display: grid !important;
					grid-template-columns: max-content minmax(0, 1fr) !important;
					align-items: center !important;
					gap: 12px 16px !important;
					width: 100% !important;
					height: auto !important;
					min-height: 42px !important;
					margin: 0 0 20px !important;
					padding: 0 !important;
					clear: both !important;
					float: none !important;
					overflow: visible !important;
					text-align: right !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount .button,
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_label {
					position: static !important;
					float: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount .clear {
					display: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter + .widget {
					padding-top: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/*
 * WooCommerce expone init_price_filter para reinicializar el slider clásico.
 * Al mover el sidebar al drawer puede quedar pendiente; reemitimos el evento
 * únicamente al abrirlo y sólo si el widget aún no tiene handles inicializados.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}
		?>
		<script id="elmercado-price-filter-reinit-01077">
		(() => {
			'use strict';
			const needsInit = () => {
				const slider = document.querySelector('#emo-premium-filter-shell .widget_price_filter .price_slider');
				return slider && !slider.classList.contains('ui-slider') && !slider.querySelector('.ui-slider-handle');
			};
			const initPriceFilter = () => {
				if (!needsInit() || !window.jQuery) return;
				window.jQuery(document.body).trigger('init_price_filter');
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
