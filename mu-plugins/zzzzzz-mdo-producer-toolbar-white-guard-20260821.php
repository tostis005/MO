<?php
/**
 * Plugin Name: MDO Producer Toolbar White Guard
 * Description: Makes the producer catalogue toolbar match the main shop white toolbar from first paint and keeps that style after the destination control is mounted.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_toolbar_white_guard_is_store_20260821(): bool {
	return function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821();
}

function mdo_producer_toolbar_white_guard_head_20260821(): void {
	if ( ! mdo_producer_toolbar_white_guard_is_store_20260821() ) {
		return;
	}
	?>
	<style id="mdo-producer-toolbar-white-guard-20260821" data-mdo-producer-toolbar-white-guard="1">
		/* Deliberately high specificity: older vendor-store CSS uses !important. */
		html body.mdo-producer-store-toolbar-ux.mdo-producer-store-toolbar-ux.mdo-producer-store-toolbar-ux
		.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting {
			display:flex !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:auto !important;
			min-height:68px !important;
			max-height:none !important;
			align-items:center !important;
			justify-content:space-between !important;
			gap:18px !important;
			overflow:visible !important;
			margin:0 0 16px !important;
			padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;
			border-radius:16px !important;
			background:#fff !important;
			background-color:#fff !important;
			background-image:none !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			color:#173f32 !important;
		}
		html body.mdo-producer-store-toolbar-ux .woostify-sorting .mdo-ps-destination,
		html body.mdo-producer-store-toolbar-ux .woostify-sorting .mdo-catalog-destination--canonical {
			margin:0 !important;
			padding:0 !important;
		}
		html body.mdo-producer-store-toolbar-ux .woostify-sorting .mdo-ps-destination__trigger,
		html body.mdo-producer-store-toolbar-ux .woostify-sorting .mdo-catalog-destination__trigger {
			background:#f8faf8 !important;
			background-color:#f8faf8 !important;
			color:#173f32 !important;
			border-color:rgba(23,63,50,.15) !important;
		}
		@media (max-width:640px) {
			html body.mdo-producer-store-toolbar-ux.mdo-producer-store-toolbar-ux.mdo-producer-store-toolbar-ux
			.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting.woostify-sorting {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) !important;
				min-height:0 !important;
				height:auto !important;
				gap:10px !important;
				padding:11px !important;
				border-radius:15px !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_producer_toolbar_white_guard_head_20260821', PHP_INT_MAX );

function mdo_producer_toolbar_white_guard_footer_20260821(): void {
	if ( ! mdo_producer_toolbar_white_guard_is_store_20260821() ) {
		return;
	}
	?>
	<script id="mdo-producer-toolbar-white-guard-js-20260821">
	(() => {
		'use strict';
		const trigger = document.querySelector('[data-mdo-ps-destination-open]');
		if (!trigger) return;
		const toolbar = trigger.closest('.woostify-sorting');
		if (!toolbar) return;
		const important = (property, value) => toolbar.style.setProperty(property, value, 'important');
		important('background', '#fff');
		important('background-color', '#fff');
		important('background-image', 'none');
		important('border', '1px solid rgba(23,63,50,.11)');
		important('border-radius', window.matchMedia('(max-width:640px)').matches ? '15px' : '16px');
		important('box-shadow', '0 10px 28px rgba(17,42,34,.055)');
		toolbar.setAttribute('data-mdo-producer-toolbar-white', '1');
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_producer_toolbar_white_guard_footer_20260821', PHP_INT_MAX );
