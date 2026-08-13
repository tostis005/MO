<?php
/**
 * Permite que el sticky del rail de productor use el viewport real.
 * WCFM/Woostify deja #view con overflow-y:auto aunque no sea el scroller visible,
 * lo que convierte ese contenedor en el ancestro sticky y hace que el panel se
 * desplace con el contenido en vez de quedarse fijado.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-sticky-root-fix-010228">
			@media (min-width:1101px) {
				html body.elmercado-child-theme.wcfmmp-store-page #view,
				html body.elmercado-child-theme.wcfmmp-store-page #page {
					overflow:visible !important;
					overflow-x:visible !important;
					overflow-y:visible !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
