<?php
/**
 * Plugin Name: MDO Producer Toolbar Mobile Polish
 * Description: Mobile-only polish for producer shipping/ordering controls: no clipped label, no double ordering border, and reliable native select interaction.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_toolbar_mobile_polish_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_producer_toolbar_static_guard_is_store_20260821' ) ) {
		return mdo_producer_toolbar_static_guard_is_store_20260821();
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) ) {
		return mdo_ps_toolbar_ux_is_store_20260821();
	}
	return function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page();
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
			return;
		}
		?>
		<style id="mdo-producer-toolbar-mobile-polish-20260821">
			@media (max-width:640px) {
				/* Shipping pill: one clean text line, vertically centred, never clipped. */
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination--canonical,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination {
					overflow:visible !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger {
					position:relative !important;
					display:grid !important;
					grid-template-columns:minmax(0,1fr) 16px !important;
					align-items:center !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					padding:0 13px !important;
					overflow:visible !important;
					line-height:1.25 !important;
				}
				/* WCFM injects a real location SVG before the text; the shared shop control does not use it. */
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:first-child,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:first-child {
					display:none !important;
					visibility:hidden !important;
					width:0 !important;
					height:0 !important;
					margin:0 !important;
					padding:0 !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
					display:flex !important;
					min-width:0 !important;
					height:100% !important;
					align-items:center !important;
					overflow:visible !important;
					white-space:nowrap !important;
					text-overflow:clip !important;
					line-height:1.25 !important;
					padding:1px 0 0 !important;
				}

				/* Ordering: the form is only a transparent hit-area; the select owns the single visible border. */
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering {
					position:relative !important;
					z-index:20 !important;
					display:flex !important;
					box-sizing:border-box !important;
					border:0 !important;
					border-radius:0 !important;
					outline:0 !important;
					background:transparent !important;
					box-shadow:none !important;
					overflow:visible !important;
					pointer-events:auto !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::before,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::after {
					pointer-events:none !important;
					box-shadow:none !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering select {
					position:relative !important;
					z-index:21 !important;
					display:block !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:100% !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
					border:1px solid rgba(23,63,50,.14) !important;
					border-radius:999px !important;
					outline:0 !important;
					box-shadow:none !important;
					pointer-events:auto !important;
					cursor:pointer !important;
					touch-action:manipulation !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
