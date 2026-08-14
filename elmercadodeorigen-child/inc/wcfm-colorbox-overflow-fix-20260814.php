<?php
/**
 * Evita que Colorbox recorte los formularios modales de WCFM en la gestión
 * de vendedores. WCFM usa Colorbox en el frontend del Store Manager; su CSS
 * base aplica overflow:hidden tanto al contenedor exterior como al wrapper.
 * En el modal de creación de envíos el contenido puede superar el alto que
 * Colorbox calcula inicialmente y queda oculto detrás del overlay.
 *
 * También corrige el margen excesivo que WCFM aplica a sus checkboxes dentro
 * de estos formularios, para que queden visualmente cerca de sus opciones.
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

			/*
			 * WCFM aplica por defecto margin-right:50% a .wcfm-checkbox. Dentro de
			 * estos modales deja un hueco excesivo entre el check y su opción.
			 */
			#colorbox input.wcfm-checkbox,
			#colorbox input[type="checkbox"].wcfm-checkbox {
				margin-left: 0 !important;
				margin-right: 8px !important;
				position: static !important;
				vertical-align: middle !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
