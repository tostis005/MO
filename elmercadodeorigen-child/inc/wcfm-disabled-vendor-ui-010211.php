<?php
/**
 * Limpieza visual de vendedores WCFM desactivados en el catálogo público.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Una URL antigua con vendor_id bloqueado no debe generar chip/selección activa.
 * La consulta principal ya ha sido restringida por el guard de visibilidad.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) || elmercado_wcfm_disabled_visibility_can_view_010210() ) {
			return;
		}

		$vendor_id = function_exists( 'elmercado_wcfm_requested_vendor_id_010210' ) ? elmercado_wcfm_requested_vendor_id_010210() : 0;
		if ( $vendor_id <= 0 || ! function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) || ! elmercado_wcfm_vendor_is_disabled_010210( $vendor_id ) ) {
			return;
		}

		unset( $_GET['vendor_id'], $_REQUEST['vendor_id'] );
	},
	1
);

/**
 * El filtro de vendedor se genera desde una consulta SQL propia del tema. Lo
 * mantenemos intacto para administradores y ocultamos solo las cuentas WCFM
 * bloqueadas para el público, antes de que el panel llegue a montarse.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) || elmercado_wcfm_disabled_visibility_can_view_010210() ) {
			return;
		}
		if ( ! function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
			return;
		}

		$ids = array_values( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) );
		if ( ! $ids ) {
			return;
		}

		$selectors = array();
		foreach ( $ids as $vendor_id ) {
			$selectors[] = '#emo-global-vendor-filter [data-vendor-id="' . $vendor_id . '"]';
		}
		?>
		<style id="elmercado-wcfm-disabled-vendor-ui-010211">
			<?php echo esc_html( implode( ',', $selectors ) ); ?> { display:none !important; visibility:hidden !important; }
		</style>
		<?php
	},
	999
);
