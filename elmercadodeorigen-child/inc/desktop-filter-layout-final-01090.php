<?php
/**
 * Layout estructural del sidebar de filtros de escritorio 0.10.90.
 *
 * Convierte el contenedor principal de los archivos de producto en flex para
 * que el rail de filtros sea un elemento sticky real, sin floats ni cálculos
 * de posición. Tienda y archivos de taxonomía conservan ritmos superiores
 * distintos porque sólo Tienda incluye la introducción editorial.
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
		<style id="elmercado-desktop-filter-layout-final-01090">
			@media (min-width: 1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content > .woostify-container {
					overflow: visible !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content > .woostify-container {
					display: flex !important;
					align-items: flex-start !important;
					gap: 34px !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content > .woostify-container::before,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content > .woostify-container::after {
					display: none !important;
					content: none !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #primary.content-area {
					float: none !important;
					flex: 1 1 0 !important;
					min-width: 0 !important;
					width: auto !important;
					max-width: none !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					float: none !important;
					flex: 0 0 250px !important;
					align-self: flex-start !important;
					position: sticky !important;
					top: 94px !important;
				}

				/* La Tienda incluye su introducción editorial antes de la toolbar. */
				body.elmercado-child-theme.woocommerce-shop:not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					margin: 132px 0 0 !important;
				}
				/* Categorías y etiquetas empiezan directamente con la toolbar. */
				body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					margin: 12px 0 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);