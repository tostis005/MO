<?php
/**
 * Eliminación del trigger nativo redundante de filtros, refinada en 0.10.181.
 *
 * El catálogo ya dispone de rail visible en escritorio y de un trigger propio
 * en compacto. Retiramos desde PHP el botón que Woostify registra en
 * `woocommerce_before_shop_loop`; el CSS crítico queda sólo como red de
 * seguridad para integraciones que vuelvan a imprimir un control equivalente.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evita que el botón nativo de Woostify llegue al HTML.
 *
 * Woostify registra `woostify_toggle_sidebar_mobile_button` en
 * `woocommerce_before_shop_loop` con prioridad 15. El hook `wp` ocurre después
 * de que el tema padre haya registrado sus callbacks y antes del render del loop.
 */
add_action(
	'wp',
	static function (): void {
		$is_catalog = ( function_exists( 'is_shop' ) && is_shop() ) ||
			( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ||
			( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) ||
			( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) ||
			( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() );

		if ( is_admin() || ! $is_catalog ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop', 'woostify_toggle_sidebar_mobile_button', 15 );
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		$is_catalog = ( function_exists( 'is_shop' ) && is_shop() ) ||
			( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ||
			( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) ||
			( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) ||
			( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() );

		if ( is_admin() || ! $is_catalog ) {
			return;
		}
		?>
		<style id="elmercado-native-filter-remove-010181">
			/* Red de seguridad: debe aplicar antes del primer paint. */
			html #toggle-sidebar-mobile-button.filter,
			html .woostify-sorting button.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html .woostify-sorting a.filter:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle),
			html .woostify-sorting .filter.show:not(#emo-premium-filter-toggle):not(.emo-mobile-filter-toggle) {
				display: none !important;
				visibility: hidden !important;
			}

			/* El botón propio de filtros sólo pertenece al layout compacto. */
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
