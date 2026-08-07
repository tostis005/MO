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
		<style id="elmercado-vendor-mobile-toolbar-final">
			@media (max-width: 600px) {
				body.elmercado-child-theme .wcfmmp-store-content .woostify-sorting,
				body.elmercado-child-theme .wcfm_store_content .woostify-sorting {
					display: flex !important;
					flex-wrap: nowrap !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 8px !important;
					min-height: 46px !important;
					padding: 0 !important;
				}

				body.elmercado-child-theme .wcfmmp-store-content .woostify-toolbar-left,
				body.elmercado-child-theme .wcfm_store_content .woostify-toolbar-left,
				body.elmercado-child-theme .wcfmmp-store-content .woocommerce-result-count,
				body.elmercado-child-theme .wcfm_store_content .woocommerce-result-count {
					flex: 1 1 auto !important;
					width: auto !important;
					min-width: 0 !important;
					min-height: 44px !important;
					align-items: center !important;
					margin: 0 !important;
					padding: 0 !important;
					font-size: 12px !important;
					line-height: 1.25 !important;
				}

				body.elmercado-child-theme .wcfmmp-store-content .woocommerce-ordering,
				body.elmercado-child-theme .wcfm_store_content .woocommerce-ordering {
					flex: 0 0 auto !important;
					width: auto !important;
					min-height: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					align-items: center !important;
				}

				body.elmercado-child-theme .wcfmmp-store-content .woocommerce-ordering select,
				body.elmercado-child-theme .wcfm_store_content .woocommerce-ordering select {
					box-sizing: border-box !important;
					width: min(42vw, 150px) !important;
					height: 44px !important;
					min-height: 44px !important;
					padding: 0 30px 0 10px !important;
					font-size: 12px !important;
					line-height: 1.2 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
