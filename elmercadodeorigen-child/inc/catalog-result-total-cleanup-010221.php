<?php
/**
 * Limpieza final de la barra de resultados del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Oculta desde la primera pintura cualquier contador heredado y el ordenamiento.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-result-total-cleanup-010221">
			.emo-catalog-result-count-010218 { display:none !important; visibility:hidden !important; }
			.emo-catalog-result-count-010220 { display:block !important; visibility:visible !important; }
			.woocommerce-ordering { display:none !important; visibility:hidden !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Elimina del DOM el contador heredado para que accesibilidad, automatización y
 * cualquier script posterior encuentren una sola fuente de verdad.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-result-total-cleanup-script-010221">
			document.querySelectorAll('.emo-catalog-result-count-010218').forEach(function (node) {
				node.remove();
			});
		</script>
		<?php
	},
	PHP_INT_MAX
);

/**
 * El cierre geométrico de las tiendas de productor debe registrarse después de
 * que functions.php haya cargado el módulo principal 0.10.225. Se difiere a
 * after_setup_theme para que sus callbacks de wp_head/wp_footer sean los últimos
 * dentro de su prioridad y puedan neutralizar reglas históricas de WCFM.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		$layout_lock = ELMERCADO_THEME_PATH . '/inc/vendor-store-catalog-layout-lock-010225.php';
		if ( is_readable( $layout_lock ) ) {
			require_once $layout_lock;
		}
	},
	PHP_INT_MAX
);