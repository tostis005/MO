<?php
/**
 * Measured layout corrections after focused browser audit.
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
		<style id="elmercado-layout-consistency-098">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header,
				body.elmercado-child-theme .site-header .site-header-inner,
				body.elmercado-child-theme .site-header .woostify-container {
					min-height: 62px !important;
					height: 62px !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					display: flex !important;
					align-items: center !important;
					width: 190px !important;
					height: 62px !important;
					flex: 0 0 190px !important;
				}
				body.elmercado-child-theme .site-header #site-navigation.main-navigation {
					display: flex !important;
					align-items: center !important;
					justify-content: flex-start !important;
					flex: 0 0 auto !important;
					width: max-content !important;
					max-width: max-content !important;
					height: 62px !important;
					margin: 0 0 0 58px !important;
				}
				body.elmercado-child-theme .site-header #site-navigation.main-navigation > ul,
				body.elmercado-child-theme .site-header #site-navigation.main-navigation .primary-navigation {
					width: max-content !important;
					max-width: max-content !important;
					justify-content: flex-start !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					height: 62px !important;
					margin-left: auto !important;
				}
			}

			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 1rem !important;
				height: auto !important;
				min-height: 70px !important;
				margin: 2rem 0 1.5rem !important;
				padding: 12px 16px !important;
				border: 1px solid rgba(23,63,50,.1) !important;
				border-radius: 14px !important;
				background: #fff !important;
				box-shadow: 0 8px 24px rgba(13,33,27,.05) !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woostify-toolbar-left {
				display: flex !important;
				align-items: center !important;
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count,
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
				display: flex !important;
				align-items: center !important;
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
				margin-left: auto !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering select {
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
			}
			@media (max-width: 767px) {
				body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
					align-items: stretch !important;
					flex-direction: column !important;
				}
				body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
					width: 100% !important;
					margin-left: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
