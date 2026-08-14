<?php
/**
 * Evita que Colorbox recorte los formularios modales de WCFM en la gestión
 * de vendedores. WCFM usa Colorbox en el frontend del Store Manager; su CSS
 * base aplica overflow:hidden tanto al contenedor exterior como al wrapper.
 * En el modal de creación de envíos el contenido puede superar el alto que
 * Colorbox calcula inicialmente y queda oculto detrás del overlay.
 *
 * Parche acotado a /store-manager/vendors-manage/.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve true únicamente en la edición de vendedores del WCFM Store Manager.
 */
function elmercado_is_wcfm_vendor_manager_request(): bool {
	if ( is_admin() ) {
		return false;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_unslash( $_SERVER['REQUEST_URI'] )
		: '';
	$path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

	return str_starts_with( trailingslashit( $path ), '/store-manager/vendors-manage/' );
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_is_wcfm_vendor_manager_request() ) {
			return;
		}
		?>
		<style id="elmercado-wcfm-colorbox-overflow-fix">
			/*
			 * El overlay debe seguir recortado. Solo liberamos los dos contenedores
			 * que envuelven el formulario para que Colorbox no oculte su contenido.
			 */
			#colorbox,
			#cboxWrapper {
				overflow: visible !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
