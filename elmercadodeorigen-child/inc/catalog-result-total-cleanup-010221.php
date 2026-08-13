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

/* Desde 0.10.229 la única capa visual del catálogo es catalog-filter-unified-010229.php. */
add_action(
	'after_setup_theme',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/home-visible-categories-final-010226.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

/* #view de WCFM debe dejar que sticky se ancle al viewport real en escritorio. */
add_action(
	'wp_loaded',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/vendor-sticky-root-fix-010228.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_catalog_filter_unified_target_010229' ) || ! elmercado_catalog_filter_unified_target_010229() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-shared-interaction-010230">
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-title-shared-010229 { font-weight:800 !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-list-shared-010229 { gap:3px !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 { min-height:32px !important; margin:0 !important; padding:1px 4px !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 > .emo-filter-link-shared-010229 { padding:6px 4px !important; text-decoration:none !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) { background-color:#d9ede0 !important; box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:hover,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:focus-visible,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229 { color:#155b42 !important; font-weight:650 !important; text-decoration:underline !important; text-decoration-thickness:1px !important; text-underline-offset:3px !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove { display:inline-flex !important; flex:0 0 auto !important; align-items:center !important; gap:3px !important; min-width:max-content !important; white-space:nowrap !important; word-break:normal !important; overflow-wrap:normal !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove::before,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove::after { display:none !important; content:none !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove:hover > span:last-child { text-decoration:underline !important; text-decoration-thickness:1px !important; text-underline-offset:3px !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);
