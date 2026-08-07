<?php
/**
 * Final mobile alignment for vendor result count and ordering controls.
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
		<style id="elmercado-vendor-toolbar-mobile-final">
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
				display: flex !important;
				flex-flow: row nowrap !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 14px !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count,
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				position: static !important;
				inset: auto !important;
				clear: none !important;
				float: none !important;
				align-self: center !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				flex: 1 1 auto !important;
				width: auto !important;
				min-width: 0 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				flex: 0 0 min(260px,42vw) !important;
				width: min(260px,42vw) !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
			}

			@media (max-width: 991px) {
				/* Woostify transforma el trigger original en una X externa. Durante el
				 * drawer lo ocultamos y conservamos solo el cierre del propio panel. */
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
			}

			@media (max-width: 600px) {
				/* El padding del host crea una separación real, sin colapso de márgenes. */
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-sorting-normalized {
					padding-top: 28px !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) 132px !important;
					align-items: center !important;
					justify-content: stretch !important;
					gap: 8px !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					grid-column: 1 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 100% !important;
					min-width: 0 !important;
					min-height: 44px !important;
					align-items: center !important;
					font-size: 11px !important;
					line-height: 1.25 !important;
					white-space: normal !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
					grid-column: 2 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 132px !important;
					min-width: 132px !important;
					min-height: 44px !important;
					align-items: center !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					height: 44px !important;
					min-height: 44px !important;
					font-size: 11px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
