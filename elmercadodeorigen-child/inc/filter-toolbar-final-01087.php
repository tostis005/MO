<?php
/**
 * Limpieza declarativa final de la toolbar de filtros móviles 0.10.87.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-filter-toolbar-final-01087">
			@media (max-width: 1100px) {
				/* Woostify puede reinyectar este trigger nativo tras filtrar por AJAX.
				 * En compacto sólo debe existir el trigger propio situado fuera de la toolbar. */
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .woostify-sorting .emo-remove-filter-toggle {
					display: none !important;
					visibility: hidden !important;
				}

				/* El enlace de limpieza nunca debe partirse en móviles estrechos. */
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__head {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) auto !important;
					align-items: center !important;
					column-gap: 10px !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__title {
					min-width: 0 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__clear {
					width: auto !important;
					min-width: max-content !important;
					white-space: nowrap !important;
					word-break: keep-all !important;
					overflow-wrap: normal !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
