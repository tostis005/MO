<?php
/**
 * Limpieza final de la barra de resultados del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

add_action(
	'after_setup_theme',
	static function (): void {
		$modules = array(
			ELMERCADO_THEME_PATH . '/inc/vendor-store-catalog-layout-lock-010225.php',
			ELMERCADO_THEME_PATH . '/inc/vendor-store-shop-parity-010226.php',
			ELMERCADO_THEME_PATH . '/inc/home-visible-categories-final-010226.php',
		);
		foreach ( $modules as $module ) {
			if ( is_readable( $module ) ) {
				require_once $module;
			}
		}
	},
	PHP_INT_MAX
);

/*
 * La capa de estabilidad debe registrarse después del cierre visual 0.10.227.
 * wp_loaded ocurre después de after_setup_theme, pero todavía antes de wp_head
 * y wp_footer, por lo que sus reglas quedan realmente al final de la cascada.
 */
add_action(
	'wp_loaded',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/catalog-filter-stability-010228.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);
