<?php
/**
 * Acabado visual de filtros de Tienda en escritorio 0.10.89.
 *
 * Mantiene el breakpoint funcional existente: a partir de 1101 px el sidebar
 * permanece en el flujo de la Tienda y sticky. Esta capa sólo corrige su
 * jerarquía visual y su alineación con la toolbar del catálogo.
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
		<style id="elmercado-desktop-filter-visual-final-01089">
			@media (min-width: 1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) {
					box-sizing: border-box !important;
					position: sticky !important;
					top: 94px !important;
					align-self: start !important;
					width: 250px !important;
					min-width: 250px !important;
					max-width: 250px !important;
					height: max-content !important;
					margin: 132px 0 0 !important;
					padding: 18px !important;
					border: 1px solid rgba(23,63,50,.11) !important;
					border-radius: 18px !important;
					background: #fff !important;
					box-shadow: 0 12px 32px rgba(17,42,34,.07) !important;
					overflow: visible !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget {
					margin: 0 0 18px !important;
					padding: 0 !important;
					border: 0 !important;
					border-radius: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget:last-child {
					margin-bottom: 0 !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) :is(.widget-title,.widgettitle,.sidebar-heading,.widget-heading,.wp-block-heading) {
					display: flex !important;
					min-height: 40px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 0 14px !important;
					padding: 8px 12px !important;
					border: 0 !important;
					border-radius: 11px !important;
					background: #173f32 !important;
					color: #fff !important;
					font-family: inherit !important;
					font-size: 12px !important;
					font-weight: 850 !important;
					letter-spacing: .06em !important;
					line-height: 1.2 !important;
					text-align: center !important;
					text-transform: uppercase !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget_price_filter form,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_wrapper {
					margin: 0 !important;
					padding: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider {
					position: relative !important;
					height: 4px !important;
					margin: 12px 9px 20px !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: #dfe9e3 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider .ui-slider-range {
					top: 0 !important;
					height: 4px !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: #2f7d5d !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider .ui-slider-handle {
					top: 50% !important;
					width: 18px !important;
					height: 18px !important;
					margin-top: -9px !important;
					margin-left: -9px !important;
					border: 3px solid #2f7d5d !important;
					border-radius: 50% !important;
					background: #fff !important;
					box-shadow: 0 1px 5px rgba(17,42,34,.12) !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_amount {
					display: flex !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 10px !important;
					width: 100% !important;
					min-height: 40px !important;
					margin: 0 !important;
					padding: 0 !important;
					text-align: left !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_amount::before,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_amount::after {
					display: none !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_amount .button {
					flex: 0 0 auto !important;
					min-height: 38px !important;
					margin: 0 !important;
					padding: 0 14px !important;
					border-radius: 999px !important;
					font-size: 12px !important;
					line-height: 1 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .price_slider_amount .price_label {
					min-width: 0 !important;
					margin: 0 0 0 auto !important;
					color: #42564e !important;
					font-size: 11.5px !important;
					font-weight: 700 !important;
					line-height: 1.25 !important;
					text-align: right !important;
					white-space: nowrap !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget ul {
					margin: 0 !important;
					padding: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget li {
					margin: 0 !important;
					border-bottom: 1px solid rgba(23,63,50,.10) !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget li:last-child {
					border-bottom: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .widget li > a {
					min-height: 42px !important;
					padding: 9px 1px !important;
					font-size: 13px !important;
					font-weight: 700 !important;
					line-height: 1.3 !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_tag_cloud,.widget_tag_cloud) {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .tagcloud {
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 7px !important;
					margin: 0 !important;
					padding: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .tagcloud a {
					display: inline-flex !important;
					min-height: 32px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 !important;
					padding: 6px 10px !important;
					border: 1px solid rgba(23,63,50,.13) !important;
					border-radius: 999px !important;
					background: #f4f7f3 !important;
					color: #29483d !important;
					font-size: 11px !important;
					font-weight: 700 !important;
					line-height: 1.15 !important;
					text-decoration: none !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .tagcloud a:hover,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) :is(#secondary.widget-area,.shop-widget-area) .tagcloud a:focus-visible {
					border-color: rgba(47,125,93,.38) !important;
					background: #eaf2ed !important;
					color: #173f32 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);