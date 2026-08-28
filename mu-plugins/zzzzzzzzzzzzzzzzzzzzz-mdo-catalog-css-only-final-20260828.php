<?php
/**
 * Plugin Name: MDO Catalogue CSS-only Final 2026-08-28
 * Description: Final CSS-only mobile geometry owner for catalogue controls.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_css_only_final_style_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-css-only-final-20260828">
	@media (max-width:767px) {
		/* Global shop: one column, count + destination + ordering. Repeated real
		 * classes intentionally outrank historical !important mobile rules. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 {
			display:grid !important;
			grid-template-columns:minmax(0,1fr) !important;
			grid-template-rows:auto 40px 40px !important;
			align-items:stretch !important;
			justify-items:stretch !important;
			gap:8px !important;
			height:auto !important;
			min-height:0 !important;
			max-height:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left {
			display:contents !important;
			width:auto !important;
			height:auto !important;
			min-height:0 !important;
			max-height:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-ps-destination-open]),
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination.mdo-catalog-destination,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical.mdo-catalog-destination--canonical {
			grid-column:1 !important;
			grid-row:2 !important;
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			margin:0 !important;
			float:none !important;
			transform:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > form.woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
			grid-column:1 !important;
			grid-row:3 !important;
			display:block !important;
			position:static !important;
			inset:auto !important;
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
			float:none !important;
			clear:none !important;
			transform:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby.orderby,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"] {
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			margin:0 !important;
			float:none !important;
			transform:none !important;
			pointer-events:auto !important;
		}

		/* Producer store: preserve the viewport-aligned CSS-only geometry that
		 * fixes the 335px WCFM case without any mutation/resize observers. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
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
			max-height:none !important;
			transform:translateX(-50%) !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
			display:contents !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .mdo-ps-destination.mdo-ps-destination,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .mdo-ps-destination.mdo-ps-destination {
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			margin:0 !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"] {
			display:block !important;
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			margin:0 !important;
			float:none !important;
			pointer-events:auto !important;
		}
	}
	</style>
	<?php
}

/* Append after all historical PHP_INT_MAX presentation layers. */
add_action(
	'wp_footer',
	static function (): void {
		add_action( 'wp_footer', 'mdo_catalog_css_only_final_style_20260828', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
