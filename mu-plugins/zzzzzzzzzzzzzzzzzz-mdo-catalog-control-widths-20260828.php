<?php
/**
 * Plugin Name: MDO Catalogue Control Widths 2026-08-28
 * Description: CSS-only final presentation owner for catalogue controls on the global shop and producer stores.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_control_widths_is_surface_20260828(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function mdo_catalog_control_widths_css_20260828(): void {
	if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
		return;
	}
	?>
	<style id="mdo-catalog-control-widths-css-only-20260828">
		/* Final owner: CSS only. No MutationObserver, ResizeObserver, RAF loops,
		 * timers, resize handlers or inline style writers are used here. */
		html body .emo-catalog-toolbar-shared-010229 {
			display:flex !important;
			box-sizing:border-box !important;
			position:relative !important;
			left:0 !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:auto !important;
			min-height:68px !important;
			align-items:center !important;
			justify-content:space-between !important;
			gap:18px !important;
			overflow:visible !important;
			margin:0 0 16px !important;
			padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;
			border-radius:16px !important;
			background:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			transform:none !important;
		}

		html body .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:1 1 auto !important;
			width:auto !important;
			min-width:0 !important;
			max-width:none !important;
			height:42px !important;
			align-items:center !important;
			gap:15px !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
			display:inline-flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 0 auto !important;
			width:auto !important;
			min-width:0 !important;
			height:42px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			color:#53665f !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			line-height:1.25 !important;
			white-space:nowrap !important;
		}

		html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
		html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
		html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 0 248px !important;
			width:248px !important;
			min-width:248px !important;
			max-width:248px !important;
			height:42px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
			display:grid !important;
			grid-template-columns:13px minmax(0,1fr) 16px !important;
			column-gap:8px !important;
			align-items:center !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:42px !important;
			margin:0 !important;
			padding:0 13px !important;
			border:1px solid rgba(23,63,50,.22) !important;
			border-radius:999px !important;
			background:#f1f6f2 !important;
			box-shadow:0 1px 2px rgba(17,42,34,.025) !important;
			color:#173f32 !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			line-height:1 !important;
			white-space:nowrap !important;
			text-align:left !important;
			cursor:pointer !important;
		}
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:hover,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:focus-visible,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:hover,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:focus-visible {
			background:#eaf2ed !important;
			border-color:rgba(23,63,50,.34) !important;
			outline:none !important;
		}
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg:first-child,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg:first-child,
		html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__pin,
		html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__pin {
			display:none !important;
		}
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::before,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::before {
			content:"" !important;
			display:block !important;
			box-sizing:border-box !important;
			width:13px !important;
			height:13px !important;
			min-width:13px !important;
			background-color:currentColor !important;
			-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			opacity:.72 !important;
			pointer-events:none !important;
		}
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > span,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > span {
			display:block !important;
			min-width:0 !important;
			overflow:hidden !important;
			text-overflow:ellipsis !important;
			white-space:nowrap !important;
			font-weight:500 !important;
			text-align:left !important;
		}
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] strong,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] strong {
			font-weight:760 !important;
			color:inherit !important;
		}
		html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron,
		html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__chevron,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg:last-child,
		html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg:last-child {
			display:block !important;
			position:static !important;
			align-self:center !important;
			justify-self:center !important;
			width:12px !important;
			height:8px !important;
			min-width:12px !important;
			max-width:12px !important;
			margin:0 !important;
			padding:0 !important;
			opacity:.72 !important;
			transform:none !important;
			pointer-events:none !important;
		}

		html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
			display:block !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 0 248px !important;
			width:248px !important;
			min-width:248px !important;
			max-width:248px !important;
			height:42px !important;
			margin:0 0 0 auto !important;
			padding:0 !important;
			border:0 !important;
			background:transparent !important;
			box-shadow:none !important;
			float:none !important;
			clear:none !important;
			transform:none !important;
			overflow:visible !important;
		}
		html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::before,
		html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after {
			content:none !important;
			display:none !important;
		}
		html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"],
		html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby {
			display:block !important;
			box-sizing:border-box !important;
			float:none !important;
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:42px !important;
			margin:0 !important;
			padding:0 36px 0 13px !important;
			border:1px solid rgba(23,63,50,.15) !important;
			border-radius:999px !important;
			-webkit-appearance:none !important;
			appearance:none !important;
			background-color:#f8faf8 !important;
			background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
			background-repeat:no-repeat !important;
			background-position:right 13px center !important;
			background-size:12px 8px !important;
			box-shadow:none !important;
			color:#173f32 !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			line-height:1 !important;
			text-align:left !important;
			text-align-last:left !important;
			cursor:pointer !important;
			pointer-events:auto !important;
		}

		@media (min-width:901px) {
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
			html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
				flex:0 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				max-width:320px !important;
			}
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
				width:auto !important;
				min-width:0 !important;
				max-width:320px !important;
			}
		}

		@media (max-width:767px) {
			html body .emo-catalog-toolbar-shared-010229 {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto 40px 40px !important;
				align-items:stretch !important;
				justify-items:stretch !important;
				gap:8px !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				min-height:0 !important;
				margin:0 0 12px !important;
				padding:11px !important;
				border-radius:15px !important;
			}

			/* WCFM adds an extra nested gutter on producer stores. Tie the card to
			 * the viewport with CSS only, preserving 16px outer margins. */
			html body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229,
			html body.wcfm-store-page .emo-catalog-toolbar-shared-010229 {
				left:50% !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				transform:translateX(-50%) !important;
			}

			html body .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:contents !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
				grid-column:1 !important;
				grid-row:1 !important;
				width:100% !important;
				height:18px !important;
				min-height:18px !important;
				max-height:18px !important;
				font-size:11px !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
			html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
				grid-column:1 !important;
				grid-row:2 !important;
				display:block !important;
				box-sizing:border-box !important;
				flex:none !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				margin:0 !important;
			}
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				font-size:11.75px !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:1 !important;
				grid-row:3 !important;
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
				left:0 !important;
				transform:none !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"],
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby {
				display:block !important;
				position:static !important;
				box-sizing:border-box !important;
				float:none !important;
				flex:none !important;
				width:100% !important;
				inline-size:100% !important;
				min-width:0 !important;
				min-inline-size:0 !important;
				max-width:100% !important;
				max-inline-size:100% !important;
				height:40px !important;
				margin:0 !important;
				padding:0 36px 0 13px !important;
				font-size:11.75px !important;
				background-position:right 12px center !important;
				transform:none !important;
			}

			/* Keep the producer product/filter surfaces aligned with the same 16px
			 * viewport gutter, also without runtime measurements. */
			html body.wcfmmp-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.wcfm-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
				position:relative !important;
				left:50% !important;
				box-sizing:border-box !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				margin-left:0 !important;
				margin-right:0 !important;
				transform:translateX(-50%) !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store ul.products {
				position:relative !important;
				left:calc(50% - 50vw + 16px) !important;
				box-sizing:border-box !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				margin-left:0 !important;
				margin-right:0 !important;
				transform:none !important;
			}
		}
	</style>
	<?php
}

/* Printed at the very end of the document so normal CSS cascade wins over
 * historical catalogue styles without any JavaScript style rewriting. */
add_action( 'wp_footer', 'mdo_catalog_control_widths_css_20260828', PHP_INT_MAX );
