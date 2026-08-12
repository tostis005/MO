<?php
/**
 * Ajuste final de consistencia visual del filtro Vendedor 0.10.194.
 *
 * Replica las métricas efectivas de las filas de Categorías en Tienda para que
 * Vendedor no tenga una presentación paralela o ligeramente distinta.
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
				border-radius:9px !important;
				color:#173f32 !important;
				font-size:12.5px !important;
				font-weight:700 !important;
				line-height:1.25 !important;
				text-align:start !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
