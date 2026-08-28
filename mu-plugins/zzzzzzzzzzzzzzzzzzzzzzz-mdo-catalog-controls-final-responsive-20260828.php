<?php
/**
 * Plugin Name: MDO Catalogue Controls Final Responsive 2026-08-28
 * Description: CSS-only final responsive geometry for catalogue destination and ordering controls.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_controls_final_responsive_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-controls-final-responsive-20260828">
	/* Mobile: both controls always consume the full available row. */
	@media (max-width:767px) {
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-ps-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"] {
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
		}
	}

	/* Tablet/desktop: destination is intrinsic; ordering remains a stable control. */
	@media (min-width:768px) {
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-ps-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination) {
			box-sizing:border-box !important;
			flex:0 0 auto !important;
			flex-basis:auto !important;
			width:max-content !important;
			inline-size:max-content !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:320px !important;
			max-inline-size:320px !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
			box-sizing:border-box !important;
			width:max-content !important;
			inline-size:max-content !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:320px !important;
			max-inline-size:320px !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
			box-sizing:border-box !important;
			flex:0 0 248px !important;
			flex-basis:248px !important;
			width:248px !important;
			inline-size:248px !important;
			min-width:248px !important;
			min-inline-size:248px !important;
			max-width:248px !important;
			max-inline-size:248px !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"] {
			box-sizing:border-box !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
		}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_catalog_controls_final_responsive_20260828', PHP_INT_MAX );
