<?php
/**
 * Ritmo vertical estable tras retirar page headers y acabado móvil de filtros.
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
		<style id="elmercado-content-start-mobile-filter-01069">
			body.elmercado-child-theme {
				--emo-content-start-gap: clamp(18px, 1.7vw, 24px);
			}

			/* El contenido arranca siempre desde el flujo normal, sin compensaciones al hacer scroll. */
			html body.elmercado-child-theme:not(.home) #content {
				margin-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag,.woocommerce-cart,.woocommerce-checkout,.woocommerce-account,.single-post) .site-content,
			html body.elmercado-child-theme.page:not(.home):not(.elmercado-compact-contact):not(.elmercado-contact-page):not(.elmercado-compact-producers):not(.elmercado-producers-page) .site-content,
			html body.elmercado-child-theme:is(.elmercado-compact-contact,.elmercado-contact-page,.elmercado-compact-producers,.elmercado-producers-page) .site-content {
				padding-top: var(--emo-content-start-gap) !important;
			}

			/* En el listado del blog el margen pertenece al wrapper editorial. */
			html body.elmercado-child-theme.blog .site-content,
			html body.elmercado-child-theme.archive.elmercado-editorial-content .site-content {
				padding-top: 0 !important;
			}
			html body.elmercado-child-theme.blog .emo-journal-hero,
			html body.elmercado-child-theme.archive.elmercado-editorial-content .emo-journal-hero {
				padding-top: var(--emo-content-start-gap) !important;
			}

			/* La ficha de producto conserva su navegación anterior/siguiente propia. */
			html body.elmercado-child-theme.single-product .site-content {
				padding-top: 0 !important;
			}

			html body.elmercado-child-theme:not(.home) :is(.site-content,#primary,.content-area,main.site-main) {
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme {
					--emo-content-start-gap: 18px;
				}

				/* Trigger de filtros: una sola acción clara y táctil. */
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) #emo-premium-filter-toggle {
					display: flex !important;
					height: 48px !important;
					min-height: 48px !important;
					align-items: center !important;
					justify-content: flex-start !important;
					gap: 10px !important;
					margin: 0 0 18px !important;
					padding: 0 16px !important;
					border: 1px solid rgba(23,63,50,.16) !important;
					border-radius: 14px !important;
					background: rgba(255,253,248,.92) !important;
					box-shadow: 0 7px 20px rgba(23,63,50,.045) !important;
					color: #173f32 !important;
					font-size: 13px !important;
					font-weight: 820 !important;
					letter-spacing: .01em !important;
				}
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) #emo-premium-filter-toggle::before {
					width: 18px !important;
					height: 18px !important;
					flex: 0 0 18px !important;
					background:
						linear-gradient(#2f6650,#2f6650) 0 3px/18px 1px no-repeat,
						linear-gradient(#2f6650,#2f6650) 0 9px/18px 1px no-repeat,
						linear-gradient(#2f6650,#2f6650) 0 15px/18px 1px no-repeat !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-toggle .emo-filter-label {
					margin-right: auto !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-toggle .emo-filter-chevron {
					display: none !important;
				}

				/* Drawer móvil: cabecera real, panel de lectura limpio y sin bloques redundantes. */
				html body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) {
					background: rgba(8,27,22,.48) !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-panel {
					position: absolute !important;
					inset: 0 0 0 auto !important;
					display: flex !important;
					width: min(92vw, 380px) !important;
					max-width: 380px !important;
					height: 100dvh !important;
					padding: 0 !important;
					flex-direction: column !important;
					background: #fffdf8 !important;
					border-radius: 22px 0 0 22px !important;
					box-shadow: -18px 0 52px rgba(8,27,22,.18) !important;
					overflow: hidden !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-head {
					position: sticky !important;
					top: 0 !important;
					z-index: 4 !important;
					display: flex !important;
					min-height: 72px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 14px !important;
					margin: 0 !important;
					padding: max(14px, env(safe-area-inset-top,0px)) 16px 13px 20px !important;
					background: rgba(255,253,248,.98) !important;
					border-bottom: 1px solid rgba(23,63,50,.10) !important;
					box-shadow: 0 8px 22px rgba(23,63,50,.035) !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-title {
					margin: 0 !important;
					padding: 0 !important;
					background: transparent !important;
					color: #173f32 !important;
					font-family: Georgia, "Times New Roman", serif !important;
					font-size: 22px !important;
					font-weight: 700 !important;
					letter-spacing: -.025em !important;
					line-height: 1.1 !important;
					text-align: left !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-close {
					display: grid !important;
					width: 42px !important;
					height: 42px !important;
					min-width: 42px !important;
					min-height: 42px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					box-shadow: none !important;
					color: #fffdf8 !important;
					font-size: 24px !important;
					font-weight: 400 !important;
					line-height: 1 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content {
					box-sizing: border-box !important;
					display: block !important;
					flex: 1 1 auto !important;
					min-height: 0 !important;
					padding: 16px 18px calc(28px + env(safe-area-inset-bottom,0px)) !important;
					overflow-x: hidden !important;
					overflow-y: auto !important;
					scrollbar-width: thin;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content :is(#secondary.widget-area,.shop-widget-area,.widget-area) {
					width: 100% !important;
					max-width: none !important;
					margin: 0 !important;
					padding: 0 !important;
					background: transparent !important;
					border: 0 !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget {
					margin: 0 !important;
					padding: 14px 0 16px !important;
					background: transparent !important;
					border: 0 !important;
					border-bottom: 1px solid rgba(23,63,50,.10) !important;
					border-radius: 0 !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget:first-child {
					padding-top: 0 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget:last-child {
					padding-bottom: 0 !important;
					border-bottom: 0 !important;
				}

				/* Títulos de sección compactos, centrados y consistentes. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content :is(.widget-title,.sidebar-heading,.widget-heading,.wp-block-heading) {
					display: flex !important;
					min-height: 34px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 0 13px !important;
					padding: 7px 12px !important;
					background: #173f32 !important;
					border: 0 !important;
					border-radius: 10px !important;
					color: #fffdf8 !important;
					font-family: inherit !important;
					font-size: 10.5px !important;
					font-weight: 820 !important;
					letter-spacing: .045em !important;
					line-height: 1.15 !important;
					text-align: center !important;
					text-transform: uppercase !important;
				}

				/* Precio: slider respirado y acción compacta. */
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider {
					margin: 7px 8px 20px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider-horizontal {
					height: 4px !important;
					background: #d8e2dc !important;
					border-radius: 999px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider .ui-slider-range {
					background: #2f6650 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .ui-slider .ui-slider-handle {
					width: 17px !important;
					height: 17px !important;
					top: -7px !important;
					background: #fffdf8 !important;
					border: 4px solid #2f6650 !important;
					border-radius: 50% !important;
					box-shadow: 0 2px 7px rgba(23,63,50,.12) !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount {
					display: grid !important;
					grid-template-columns: max-content minmax(0,1fr) !important;
					align-items: center !important;
					gap: 12px !important;
					text-align: right !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_slider_amount .button {
					min-height: 40px !important;
					margin: 0 !important;
					padding: 0 16px !important;
					border-radius: 999px !important;
					font-size: 12px !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter .price_label {
					margin: 0 !important;
					color: #43564e !important;
					font-size: 11.5px !important;
					font-weight: 700 !important;
					line-height: 1.35 !important;
				}

				/* Categorías como filas táctiles simples. */
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.widget_product_categories,.wc-block-product-categories) ul {
					margin: 0 !important;
					padding: 0 !important;
					list-style: none !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.widget_product_categories,.wc-block-product-categories) li {
					margin: 0 !important;
					padding: 0 !important;
					border-bottom: 1px solid rgba(23,63,50,.08) !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.widget_product_categories,.wc-block-product-categories) li:last-child {
					border-bottom: 0 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.widget_product_categories,.wc-block-product-categories) li > a {
					display: flex !important;
					min-height: 44px !important;
					align-items: center !important;
					margin: 0 !important;
					padding: 9px 2px !important;
					color: #173f32 !important;
					font-size: 13px !important;
					font-weight: 720 !important;
					line-height: 1.25 !important;
					text-decoration: none !important;
				}

				/* Etiquetas como chips suaves y legibles. */
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.tagcloud,.wp-block-tag-cloud) {
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 7px !important;
					margin: 0 !important;
				}
				html body.elmercado-child-theme #emo-premium-filter-shell :is(.tagcloud,.wp-block-tag-cloud) a {
					display: inline-flex !important;
					min-height: 34px !important;
					align-items: center !important;
					margin: 0 !important;
					padding: 7px 11px !important;
					background: #f2f5f1 !important;
					border: 1px solid rgba(23,63,50,.10) !important;
					border-radius: 999px !important;
					color: #314b41 !important;
					font-size: 11.5px !important;
					font-weight: 700 !important;
					line-height: 1.1 !important;
					text-decoration: none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
