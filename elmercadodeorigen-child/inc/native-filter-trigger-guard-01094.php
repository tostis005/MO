<?php
/**
 * Guardia declarativa del trigger nativo de filtros tras navegación 0.10.94.
 *
 * Woostify vuelve a renderizar un button.filter en archivos de categoría,
 * etiqueta y precio. En <=1100 px sólo debe existir el botón propio de filtros,
 * situado fuera de la toolbar de resultados.
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
		<style id="elmercado-native-filter-trigger-guard-01094">
			@media (max-width: 1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .woostify-sorting button.filter,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .woostify-sorting a.filter,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .woostify-sorting .emo-remove-filter-toggle {
					display: none !important;
					visibility: hidden !important;
					width: 0 !important;
					height: 0 !important;
					min-width: 0 !important;
					min-height: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					border: 0 !important;
					overflow: hidden !important;
					pointer-events: none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
