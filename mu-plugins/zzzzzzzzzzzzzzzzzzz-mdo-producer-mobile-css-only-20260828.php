<?php
/**
 * Plugin Name: MDO Producer Mobile CSS Only 2026-08-28
 * Description: High-specificity CSS-only mobile layout for producer catalogue controls.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_mobile_css_only_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-producer-mobile-css-only-20260828">
	@media (max-width:767px) {
		/* WCFM has vendor-only !important rules with greater specificity than the
		 * shared shop rules. These selectors intentionally include both vendor
		 * body classes and repeat the toolbar class so CSS wins the cascade with
		 * no runtime measurements or DOM/style rewriting. */
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 {
			display:grid !important;
			grid-template-columns:minmax(0,1fr) !important;
			grid-template-rows:auto 40px 40px !important;
			align-items:stretch !important;
			justify-items:stretch !important;
			gap:8px !important;
			position:relative !important;
			left:50% !important;
			box-sizing:border-box !important;
			width:calc(100vw - 32px) !important;
			min-width:calc(100vw - 32px) !important;
			max-width:calc(100vw - 32px) !important;
			height:auto !important;
			min-height:0 !important;
			margin:0 0 12px !important;
			padding:11px !important;
			transform:translateX(-50%) !important;
			overflow:visible !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
			display:contents !important;
			position:static !important;
			width:auto !important;
			height:auto !important;
			min-width:0 !important;
			max-width:none !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
			grid-column:1 !important;
			grid-row:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:18px !important;
			min-height:18px !important;
			max-height:18px !important;
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			transform:none !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
			grid-column:1 !important;
			grid-row:2 !important;
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:40px !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			margin:0 !important;
			transform:none !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 form.woocommerce-ordering,
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
			grid-column:1 !important;
			grid-row:3 !important;
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			flex-basis:auto !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:40px !important;
			min-height:40px !important;
			max-height:40px !important;
			margin:0 !important;
			padding:0 !important;
			left:auto !important;
			right:auto !important;
			float:none !important;
			clear:none !important;
			transform:none !important;
			overflow:visible !important;
		}

		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 form.woocommerce-ordering select[name="orderby"],
		html body.wcfm-store-page.wcfmmp-store-page .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby {
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			flex-basis:auto !important;
			float:none !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:40px !important;
			min-height:40px !important;
			max-height:40px !important;
			margin:0 !important;
			left:auto !important;
			right:auto !important;
			transform:none !important;
			pointer-events:auto !important;
		}
	}
	</style>
	<?php
}

add_action( 'wp_footer', 'mdo_producer_mobile_css_only_20260828', PHP_INT_MAX );
