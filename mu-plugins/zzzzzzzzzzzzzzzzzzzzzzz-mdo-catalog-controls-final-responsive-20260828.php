<?php
/**
 * Plugin Name: MDO Catalogue Controls Final Responsive 2026-08-28
 * Description: CSS-only final responsive geometry, icon ownership and first-paint styling for catalogue controls.
 * Version: 1.1.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One rule set is printed twice: once in the head so the destination control
 * is correct on its very first paint, and once as the last footer stylesheet
 * so historical catalogue CSS cannot win the final cascade.
 */
function mdo_catalog_controls_final_responsive_rules_20260828(): void {
	?>
	/* First-paint-safe destination owner. The duplicated attribute deliberately
	 * outranks generic theme button rules without inline styles or JavaScript. */
	html body button[data-mdo-destination-open][data-mdo-destination-open],
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open],
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open],
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] {
		display:grid !important;
		grid-template-columns:14px minmax(0,1fr) 12px !important;
		column-gap:8px !important;
		align-items:center !important;
		box-sizing:border-box !important;
		margin:0 !important;
		padding:0 12px !important;
		border:1px solid rgba(23,63,50,.22) !important;
		border-radius:999px !important;
		background:#f1f6f2 !important;
		background-color:#f1f6f2 !important;
		background-image:none !important;
		box-shadow:none !important;
		color:#173f32 !important;
		-webkit-appearance:none !important;
		appearance:none !important;
		font-family:inherit !important;
		font-size:12.5px !important;
		font-weight:500 !important;
		line-height:1 !important;
		text-align:left !important;
		white-space:nowrap !important;
		cursor:pointer !important;
	}

	/* Remove both markup icons. Exactly one pin and one chevron are rendered by
	 * pseudo-elements, so no historical SVG can occupy a second row. */
	html body .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open] svg,
	html body .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] svg,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open] svg,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] svg {
		display:none !important;
		visibility:hidden !important;
		width:0 !important;
		height:0 !important;
		min-width:0 !important;
		min-height:0 !important;
		margin:0 !important;
		padding:0 !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open]::before,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::before,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open]::before,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::before {
		content:"" !important;
		display:block !important;
		position:static !important;
		box-sizing:border-box !important;
		width:14px !important;
		height:14px !important;
		min-width:14px !important;
		margin:0 !important;
		padding:0 !important;
		border:0 !important;
		background-color:currentColor !important;
		background-image:none !important;
		-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
		mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
		opacity:.72 !important;
		transform:none !important;
		pointer-events:none !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open]::after,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::after,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open]::after,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::after {
		content:"" !important;
		display:block !important;
		position:static !important;
		top:auto !important;
		right:auto !important;
		bottom:auto !important;
		left:auto !important;
		box-sizing:border-box !important;
		width:12px !important;
		height:8px !important;
		min-width:12px !important;
		min-height:8px !important;
		margin:0 !important;
		padding:0 !important;
		border:0 !important;
		border-width:0 !important;
		background-color:transparent !important;
		background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
		background-repeat:no-repeat !important;
		background-position:center !important;
		background-size:12px 8px !important;
		box-shadow:none !important;
		opacity:.72 !important;
		transform:none !important;
		pointer-events:none !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open] > span,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] > span {
		display:block !important;
		min-width:0 !important;
		overflow:hidden !important;
		text-overflow:ellipsis !important;
		white-space:nowrap !important;
		font-weight:500 !important;
		text-align:left !important;
	}
	html body button[data-mdo-destination-open][data-mdo-destination-open] strong,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] strong {
		font-weight:760 !important;
		color:inherit !important;
	}

	/* Ordering owns one CSS chevron on the form. The select itself has no native
	 * or background arrow, preventing the duplicate-chevron failure. */
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 form.woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 form.woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 form.woocommerce-ordering.woocommerce-ordering,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
		position:relative !important;
		box-sizing:border-box !important;
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

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::before {
		content:none !important;
		display:none !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme #mdo-catalog-parity-final-20260824 .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after,
	html body.elmercado-child-theme.wcfmmp-store-page.wcfm-store-page #wcfmmp-store#wcfmmp-store #mdo-catalog-parity-final-20260824 .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering::after {
		content:"" !important;
		display:block !important;
		position:absolute !important;
		top:50% !important;
		right:12px !important;
		bottom:auto !important;
		left:auto !important;
		box-sizing:border-box !important;
		width:12px !important;
		height:8px !important;
		min-width:12px !important;
		min-height:8px !important;
		margin:0 !important;
		padding:0 !important;
		border:0 !important;
		border-width:0 !important;
		background-color:transparent !important;
		background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
		background-repeat:no-repeat !important;
		background-position:center !important;
		background-size:12px 8px !important;
		box-shadow:none !important;
		opacity:.72 !important;
		transform:translateY(-50%) !important;
		transform-origin:center !important;
		pointer-events:none !important;
		z-index:2 !important;
	}

	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
	html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select.orderby,
	html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
	html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"] {
		display:block !important;
		position:static !important;
		box-sizing:border-box !important;
		width:100% !important;
		inline-size:100% !important;
		min-width:0 !important;
		min-inline-size:0 !important;
		max-width:100% !important;
		max-inline-size:100% !important;
		margin:0 !important;
		padding:0 34px 0 12px !important;
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
		transform:none !important;
	}

	/* Tablet and desktop: result count, destination and ordering share one 38px
	 * vertical rhythm and one centre line. */
	@media (min-width:768px) {
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 {
			display:flex !important;
			position:relative !important;
			left:0 !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:auto !important;
			min-height:0 !important;
			align-items:center !important;
			justify-content:space-between !important;
			flex-wrap:nowrap !important;
			gap:14px !important;
			margin:0 0 16px !important;
			padding:8px 12px !important;
			transform:none !important;
			overflow:visible !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left {
			display:flex !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:1 1 auto !important;
			width:auto !important;
			min-width:0 !important;
			max-width:none !important;
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
			align-items:center !important;
			justify-content:flex-start !important;
			flex-wrap:nowrap !important;
			gap:12px !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
			display:flex !important;
			position:static !important;
			box-sizing:border-box !important;
			order:0 !important;
			flex:0 0 auto !important;
			width:auto !important;
			min-width:0 !important;
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
			align-items:center !important;
			align-self:center !important;
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-ps-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination) {
			order:1 !important;
			display:flex !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 0 auto !important;
			flex-basis:auto !important;
			width:max-content !important;
			inline-size:max-content !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:320px !important;
			max-inline-size:320px !important;
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
			align-items:center !important;
			align-self:center !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open],
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open],
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] {
			width:max-content !important;
			inline-size:max-content !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:320px !important;
			max-inline-size:320px !important;
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
			flex:0 0 248px !important;
			flex-basis:248px !important;
			width:248px !important;
			inline-size:248px !important;
			min-width:248px !important;
			min-inline-size:248px !important;
			max-width:248px !important;
			max-inline-size:248px !important;
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
			align-self:center !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"] {
			height:38px !important;
			min-height:38px !important;
			max-height:38px !important;
		}
	}

	/* Mobile: keep the deliberately touch-friendly 40px controls and full-width
	 * stacked layout. */
	@media (max-width:767px) {
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 {
			display:grid !important;
			grid-template-columns:minmax(0,1fr) !important;
			grid-template-rows:auto 40px 40px !important;
			align-items:stretch !important;
			justify-items:stretch !important;
			gap:8px !important;
			box-sizing:border-box !important;
			height:auto !important;
			min-height:0 !important;
			max-height:none !important;
			margin:0 0 12px !important;
			padding:10px 11px !important;
		}

		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 {
			position:relative !important;
			left:50% !important;
			width:calc(100vw - 32px) !important;
			min-width:calc(100vw - 32px) !important;
			max-width:calc(100vw - 32px) !important;
			transform:translateX(-50%) !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left {
			display:contents !important;
			width:auto !important;
			height:auto !important;
			min-height:0 !important;
			max-height:none !important;
	}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
			grid-column:1 !important;
			grid-row:1 !important;
			display:flex !important;
			position:static !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:18px !important;
			min-height:18px !important;
			max-height:18px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.woostify-toolbar-left > :has([data-mdo-ps-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination,.mdo-catalog-destination--canonical,.mdo-ps-destination) {
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
			min-height:40px !important;
			max-height:40px !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open],
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-destination-open][data-mdo-destination-open],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] {
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:40px !important;
			min-height:40px !important;
			max-height:40px !important;
			font-size:11.75px !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering {
			grid-column:1 !important;
			grid-row:3 !important;
			position:relative !important;
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
			transform:none !important;
		}

		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .woocommerce-ordering.woocommerce-ordering select[name="orderby"] {
			width:100% !important;
			inline-size:100% !important;
			min-width:0 !important;
			min-inline-size:0 !important;
			max-width:100% !important;
			max-inline-size:100% !important;
			height:40px !important;
			min-height:40px !important;
			max-height:40px !important;
			font-size:11.75px !important;
		}
	}
	<?php
}

function mdo_catalog_controls_final_responsive_critical_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-controls-final-responsive-critical-20260828">
	<?php mdo_catalog_controls_final_responsive_rules_20260828(); ?>
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_catalog_controls_final_responsive_critical_20260828', PHP_INT_MAX );

function mdo_catalog_controls_final_responsive_footer_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-controls-final-responsive-20260828">
	<?php mdo_catalog_controls_final_responsive_rules_20260828(); ?>
	</style>
	<?php
}

/* Append after historical PHP_INT_MAX catalogue presentation layers. This is
 * still CSS-only: no DOM observers, timers or runtime style mutations. */
add_action(
	'wp_footer',
	static function (): void {
		add_action( 'wp_footer', 'mdo_catalog_controls_final_responsive_footer_20260828', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
