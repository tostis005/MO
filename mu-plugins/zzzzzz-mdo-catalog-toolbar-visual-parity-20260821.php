<?php
/**
 * Plugin Name: MDO Catalog Toolbar Visual Parity
 * Description: Locks first-paint and responsive toolbar geometry so the main shop and producer stores use the same composition at every viewport.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_toolbar_visual_parity_surface_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) && mdo_ps_toolbar_ux_is_store_20260821() ) {
		return true;
	}
	return function_exists( 'mdo_catalog_summarybar_is_surface_20260820' ) && mdo_catalog_summarybar_is_surface_20260820();
}

function mdo_toolbar_visual_parity_css_20260821(): void {
	if ( ! mdo_toolbar_visual_parity_surface_20260821() ) {
		return;
	}
	?>
	<style id="mdo-toolbar-visual-parity-20260821" data-mdo-toolbar-visual-parity="1">
		/* Never animate the toolbar shell itself; this prevents colour/layout flashes. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
			transition:none !important;
			animation:none !important;
		}

		/* Tablet: both catalogues remain a single calm row. */
		@media (min-width:641px) and (max-width:991px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:64px !important;
				max-height:none !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:12px !important;
				margin:0 0 16px !important;
				padding:10px 12px !important;
				border:1px solid rgba(23,63,50,.11) !important;
				border-radius:16px !important;
				background:#fff !important;
				background-color:#fff !important;
				box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
				display:flex !important;
				flex:1 1 auto !important;
				min-width:0 !important;
				align-items:center !important;
				gap:10px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
				font-size:11.5px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				padding:0 11px 0 12px !important;
				font-size:11.75px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				flex:0 1 190px !important;
				width:190px !important;
				min-width:150px !important;
				max-width:190px !important;
			}

			/* WCFM has an extra gutter: escape only that gutter to match the shop's 16px viewport margins. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
				left:50% !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				transform:translateX(-50%) !important;
			}
		}

		/* Phone: same two-row composition in both places. */
		@media (max-width:640px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;
				grid-template-rows:auto auto !important;
				align-items:center !important;
				justify-items:stretch !important;
				column-gap:8px !important;
				row-gap:9px !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:0 !important;
				max-height:none !important;
				overflow:visible !important;
				margin:0 0 12px !important;
				padding:11px !important;
				border:1px solid rgba(23,63,50,.11) !important;
				border-radius:15px !important;
				background:#fff !important;
				background-color:#fff !important;
				box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:contents !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
				grid-column:1 / -1 !important;
				grid-row:1 !important;
				width:100% !important;
				min-height:18px !important;
				padding:0 2px !important;
				font-size:11px !important;
				line-height:1.25 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
				grid-column:1 !important;
				grid-row:2 !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 10px 0 11px !important;
				font-size:11.5px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:2 !important;
				grid-row:2 !important;
				justify-self:stretch !important;
				flex:none !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding-left:10px !important;
				padding-right:28px !important;
				font-size:11.25px !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
				left:50% !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				transform:translateX(-50%) !important;
			}
		}

		@media (max-width:350px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto auto auto !important;
				row-gap:8px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
				grid-column:1 !important;
				grid-row:2 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:1 !important;
				grid-row:3 !important;
			}
		}
	</style>
	<?php
}

/* Register after theme callbacks, so this is the final head geometry before first paint. */
add_action(
	'wp',
	static function (): void {
		if ( mdo_toolbar_visual_parity_surface_20260821() ) {
			add_action( 'wp_head', 'mdo_toolbar_visual_parity_css_20260821', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX
);
