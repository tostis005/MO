<?php
/**
 * Plugin Name: MDO Catalogue Controls Visual Finish 2026-08-28
 * Description: CSS-only visual finish for destination and ordering controls, preserving the stable catalogue geometry.
 * Version: 1.0.0
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
	/* Presentation only. The geometry/layout owner remains the CSS-only safety layer. */
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
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

	/* Use the original location SVG only: no pseudo icon, no duplicate paint. */
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg:first-child,
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg:first-child {
		display:block !important;
		position:static !important;
		box-sizing:border-box !important;
		width:14px !important;
		height:14px !important;
		min-width:14px !important;
		max-width:14px !important;
		margin:0 !important;
		padding:0 !important;
		opacity:.72 !important;
		transform:none !important;
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

	/* Same down-chevron geometry as the ordering control. */
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open] > svg:last-child,
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] > svg:last-child,
	html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron,
	html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__chevron {
		display:block !important;
		position:static !important;
		align-self:center !important;
		justify-self:center !important;
		box-sizing:border-box !important;
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

	html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"],
	html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby {
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

	html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:hover,
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open]:focus-visible,
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:hover,
	html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open]:focus-visible {
		background:#eaf2ed !important;
		border-color:rgba(23,63,50,.34) !important;
		outline:none !important;
	}
	</style>
	<?php
}

/* Register late so this wins presentation-only conflicts without inline JS. */
add_action(
	'wp_footer',
	static function (): void {
		add_action( 'wp_footer', 'mdo_catalog_controls_visual_finish_20260828', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
