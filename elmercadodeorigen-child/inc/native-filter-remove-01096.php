<?php
/**
 * Eliminación visual desde el primer pintado del trigger nativo redundante de filtros 0.10.180.
 *
 * El catálogo ya dispone de rail visible en escritorio y del trigger canónico
 * del child theme en compacto. El botón `.filter` de Woostify no aporta una
 * segunda acción útil, así que se oculta en CSS crítico antes del primer pintado,
 * sin retirada posterior mediante JavaScript.
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
			html body.elmercado-child-theme .woostify-sorting button.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html body.elmercado-child-theme .woostify-sorting a.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html body.elmercado-child-theme .woostify-sorting .filter.show:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle) {
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
		</style>
		<?php
	},
	0
);
