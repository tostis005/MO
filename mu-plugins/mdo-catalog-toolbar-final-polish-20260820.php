<?php
/**
 * Plugin Name: MDO Catalog Toolbar Final Polish
 * Description: Final geometry and layering overrides for the native catalog toolbar and shipping destination modal.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_toolbar_final_polish_output_20260820(): void {
	if ( ! function_exists( 'mdo_catalog_summarybar_is_surface_20260820' ) || ! mdo_catalog_summarybar_is_surface_20260820() ) {
		return;
	}
	?>
	<style id="mdo-catalog-toolbar-final-polish-20260820">
		/* Desktop: every control shares the same 42 px optical baseline. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
			min-height:68px !important;
			align-items:center !important;
			padding:12px 14px !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
			display:flex !important;
			align-items:center !important;
			min-height:42px !important;
			height:42px !important;
			max-height:42px !important;
			gap:15px !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left .emo-catalog-result-count-010220.woocommerce-result-count {
			display:flex !important;
			align-items:center !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			margin:0 !important;
			padding:0 2px !important;
			line-height:1.25 !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select {
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-self:center !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
			margin-left:auto !important;
		}

		/* Use one integrated ordering arrow instead of a detached theme pseudo-icon. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering::before,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering::after {
			content:none !important;
			display:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select.orderby {
			display:block !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			-webkit-appearance:none !important;
			appearance:none !important;
			background-color:#f8faf8 !important;
			background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
			background-repeat:no-repeat !important;
			background-position:right 13px center !important;
			background-size:12px 8px !important;
			padding-right:36px !important;
		}

		/* No leading locator icon; the chevron sits exactly on the vertical centre. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__pin {
			display:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron {
			display:grid !important;
			place-items:center !important;
			align-self:center !important;
			width:16px !important;
			height:16px !important;
			min-width:16px !important;
			min-height:16px !important;
			margin:0 !important;
			padding:0 !important;
			line-height:0 !important;
			transform:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron svg {
			display:block !important;
			width:12px !important;
			height:8px !important;
			margin:0 !important;
			transform:none !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover *,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus *,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible * {
			color:#173f32 !important;
		}

		/* Phones: one calm column. Count, destination and ordering never compete for width. */
		@media (max-width:640px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto auto !important;
				align-items:stretch !important;
				justify-items:stretch !important;
				gap:10px !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:0 !important;
				max-height:none !important;
				overflow:visible !important;
				margin:0 0 12px !important;
				padding:12px !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:grid !important;
				grid-column:1 !important;
				grid-row:1 !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto 40px !important;
				align-items:stretch !important;
				gap:7px !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:0 !important;
				max-height:none !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left .emo-catalog-result-count-010220.woocommerce-result-count {
				grid-column:1 !important;
				grid-row:1 !important;
				display:flex !important;
				align-items:center !important;
				width:100% !important;
				height:17px !important;
				min-height:17px !important;
				max-height:17px !important;
				margin:0 !important;
				padding:0 2px !important;
				font-size:11px !important;
				line-height:17px !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
				grid-column:1 !important;
				grid-row:2 !important;
				display:block !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 12px 0 13px !important;
				font-size:11.75px !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
				grid-column:1 !important;
				grid-row:2 !important;
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				padding:0 !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select.orderby {
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 36px 0 12px !important;
				font-size:11.75px !important;
				background-position:right 12px center !important;
			}
		}

		/* Extra guard for the inherited <=360px fixed-width ordering rule. */
		@media (max-width:360px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > form.woocommerce-ordering,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > form.woocommerce-ordering select,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > form.woocommerce-ordering select.orderby {
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				flex:0 0 100% !important;
			}
		}

		/* Modal is a root layer and its close control matches drawer sizing. */
		html body > .mdo-destination-modal--root {
			z-index:2147483646 !important;
		}
		html body > .mdo-destination-modal--root .mdo-destination-modal__close {
			width:42px !important;
			height:42px !important;
			min-width:42px !important;
			min-height:42px !important;
			top:10px !important;
			right:10px !important;
		}
		html body > .mdo-destination-modal--root .mdo-destination-modal__panel h2 {
			margin-right:48px !important;
		}
	</style>
	<?php
}

/*
 * Register the final CSS while wp_footer is already running, one priority before
 * the maximum. The output callback is therefore appended after theme/plugin
 * callbacks that were registered earlier at PHP_INT_MAX.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! function_exists( 'mdo_catalog_summarybar_is_surface_20260820' ) || ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		add_action( 'wp_footer', 'mdo_catalog_toolbar_final_polish_output_20260820', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
