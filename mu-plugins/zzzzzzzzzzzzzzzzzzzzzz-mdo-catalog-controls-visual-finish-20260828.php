<?php
/**
 * Plugin Name: MDO Catalogue Controls Visual Finish 2026-08-28
 * Description: CSS-only visual finish for destination and ordering controls, preserving the stable catalogue geometry.
 * Version: 1.2.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_controls_visual_finish_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-controls-visual-finish-20260828">
	/*
	 * Visual-only owner. High specificity is intentional so this CSS beats old
	 * WCFM/theme !important rules without observers, timers or inline styles.
	 */
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open],
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open],
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
		display:grid !important;
		grid-template-columns:14px minmax(0,1fr) 12px !important;
		column-gap:8px !important;
		align-items:center !important;
		box-sizing:border-box !important;
		width:100% !important;
		max-width:100% !important;
		height:100% !important;
		min-height:0 !important;
		margin:0 !important;
		padding:0 13px !important;
		border:1px solid rgba(23,63,50,.22) !important;
		border-radius:999px !important;
		background:#f1f6f2 !important;
		box-shadow:none !important;
		color:#173f32 !important;
		font-family:inherit !important;
		font-size:12.5px !important;
		font-weight:500 !important;
		line-height:1 !important;
		text-align:left !important;
		white-space:nowrap !important;
		cursor:pointer !important;
	}

	/* Hide markup icons and render exactly one stable CSS pin + one chevron. */
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg,
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg {
		display:none !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::before,
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::before,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::before,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::before,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::before,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::before {
		content:"" !important;
		display:block !important;
		box-sizing:border-box !important;
		width:14px !important;
		height:14px !important;
		min-width:14px !important;
		background-color:currentColor !important;
		-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
		mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
		opacity:.72 !important;
		pointer-events:none !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::after,
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::after,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::after,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::after,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]::after,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]::after {
		content:"" !important;
		display:block !important;
		box-sizing:border-box !important;
		width:12px !important;
		height:8px !important;
		min-width:12px !important;
		background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
		background-repeat:no-repeat !important;
		background-position:center !important;
		background-size:12px 8px !important;
		opacity:.72 !important;
		pointer-events:none !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > span,
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > span,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > span,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > span,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > span,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > span {
		display:block !important;
		min-width:0 !important;
		overflow:hidden !important;
		text-overflow:ellipsis !important;
		white-space:nowrap !important;
		font-weight:500 !important;
		text-align:left !important;
	}

	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] strong,
	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] strong,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] strong,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] strong,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] strong,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] strong {
		font-weight:760 !important;
		color:inherit !important;
	}

	/*
	 * Ordering arrow lives on the form wrapper, not on the select. WCFM resets
	 * select background images at mobile/tablet widths; the wrapper pseudo-element
	 * is stable and lets both controls use the exact same 12x8 chevron.
	 */
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
		position:relative !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before {
		content:none !important;
		display:none !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after {
		content:"" !important;
		display:block !important;
		position:absolute !important;
		top:50% !important;
		right:13px !important;
		left:auto !important;
		box-sizing:border-box !important;
		width:12px !important;
		height:8px !important;
		min-width:12px !important;
		margin:0 !important;
		padding:0 !important;
		background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
		background-repeat:no-repeat !important;
		background-position:center !important;
		background-size:12px 8px !important;
		opacity:.72 !important;
		transform:translateY(-50%) !important;
		pointer-events:none !important;
		z-index:2 !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby {
		display:block !important;
		box-sizing:border-box !important;
		width:100% !important;
		inline-size:100% !important;
		max-width:100% !important;
		height:100% !important;
		min-height:0 !important;
		margin:0 !important;
		padding:0 36px 0 13px !important;
		border:1px solid rgba(23,63,50,.15) !important;
		border-radius:999px !important;
		-webkit-appearance:none !important;
		appearance:none !important;
		background-color:#f8faf8 !important;
		background-image:none !important;
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

	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:hover,
	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:focus-visible,
	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:hover,
	html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:focus-visible {
		background:#eaf2ed !important;
		border-color:rgba(23,63,50,.34) !important;
		outline:none !important;
	}
	</style>
	<?php
}

/* Paint the final visual state before first render. Specificity keeps it stable. */
add_action( 'wp_head', 'mdo_catalog_controls_visual_finish_20260828', PHP_INT_MAX );
