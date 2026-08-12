<?php
/**
 * Ajuste final de consistencia visual del filtro Vendedor 0.10.194.
 *
 * La cascada real de Tienda deja las filas de Categorías en 34 px y 12.5 px.
 * Esta capa iguala Vendedor a esas métricas efectivas.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! is_shop() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-filter-visual-fix-010194">
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) #emo-global-vendor-filter .emo-global-vendor-filter__item > a {
				min-height:34px !important;
				padding:6px 1px !important;
				font-size:12.5px !important;
				font-weight:700 !important;
				line-height:1.3 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
