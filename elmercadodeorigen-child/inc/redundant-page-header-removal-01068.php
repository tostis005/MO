<?php
/**
 * Elimina el page header nativo redundante y las migas visibles del tema.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * WooCommerce puede imprimir su breadcrumb fuera del page header del tema,
 * especialmente en fichas y archivos. Retiramos ese render cuando usa el hook
 * estándar; la capa CSS de abajo cubre además el breadcrumb propio de Woostify.
 */
add_action(
	'wp',
	static function (): void {
		if ( function_exists( 'woocommerce_breadcrumb' ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}
	},
	100
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-redundant-page-header-removal-01068">
			/*
			 * Woostify: bloque superior con título grande + breadcrumb.
			 * Las cabeceras editoriales del child theme usan clases .emo-* y no se
			 * ven afectadas por esta regla.
			 */
			html body.elmercado-child-theme .page-header {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
			}

			/* Breadcrumbs que puedan aparecer fuera de .page-header. */
			html body.elmercado-child-theme :is(.woostify-breadcrumb, .woocommerce-breadcrumb) {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
