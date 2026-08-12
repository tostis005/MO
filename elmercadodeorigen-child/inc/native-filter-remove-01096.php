<?php
/**
 * Eliminación visual desde el primer pintado de triggers redundantes de filtros.
 *
 * El catálogo ya dispone de rail visible en escritorio y de un trigger propio
 * en compacto. Los controles nativos de Woostify se ocultan en CSS crítico,
 * antes de que el navegador pinte el body, para evitar cualquier parpadeo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		$is_catalog = ( function_exists( 'is_shop' ) && is_shop() ) ||
			( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ||
			( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) ||
			( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() );

		if ( is_admin() || ! $is_catalog ) {
			return;
		}
		?>
		<style id="elmercado-native-filter-remove-010180">
			/*
			 * No dependemos de una clase del body: este CSS se emite sólo en catálogo
			 * y debe poder aplicarse incluso antes de que el body exista en el DOM.
			 */
			html .woostify-sorting button.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html .woostify-sorting a.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html .woostify-sorting .filter.show:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle) {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
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

			/*
			 * El botón propio de filtros sólo pertenece al layout compacto. En PC
			 * nace oculto desde el head, en lugar de esperar a CSS/JS posterior.
			 */
			@media (min-width: 1101px) {
				html #emo-premium-filter-toggle,
				html .emo-mobile-filter-toggle,
				html #emo-premium-filter-shell,
				html .emo-mobile-filter-shell {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
			}
		</style>
		<?php
	},
	0
);
