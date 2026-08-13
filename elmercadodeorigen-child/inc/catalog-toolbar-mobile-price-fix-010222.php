<?php
/**
 * Corrección de toolbar de catálogo y slider de precio móvil 0.10.222.
 *
 * Restaura el selector nativo de ordenación que las capas 0.10.220/0.10.221
 * ocultaron al simplificar el contador, y centra geométricamente los tiradores
 * del filtro de precio en el drawer móvil con una especificidad que prevalece
 * sobre las reglas históricas del sistema de filtros.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alguna de las capas históricas del catálogo retiró el callback nativo de
 * WooCommerce, por lo que no basta con volver a mostrar el formulario por CSS.
 * Lo normalizamos al final de template_redirect: exactamente un callback en 30.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() || ! function_exists( 'woocommerce_catalog_ordering' ) ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-toolbar-mobile-price-fix-010222">
			/* El contador sigue siendo único, pero la ordenación vuelve a estar disponible. */
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .site-main .woocommerce-ordering,
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting .woocommerce-ordering {
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
			}

			/*
			 * 0.10.207 conserva margin-top:-9px con !important y una especificidad
			 * elevada por :is(#secondary,...). Esta regla la supera de forma explícita
			 * para que top:50% + translateY(-50%) sea la única compensación vertical.
			 */
			@media (max-width: 1100px) {
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget-area .widget_price_filter .price_slider .ui-slider-handle,
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget-area .widget_price_filter .ui-slider-horizontal .ui-slider-handle {
					top: 50% !important;
					margin-top: 0 !important;
					box-sizing: border-box !important;
					transform: translateY(-50%) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
