<?php
/**
 * Plugin Name: MDO Catalog Toolbar Layout
 * Description: Integrates the exact result count and canonical shipping destination into the native Woostify ordering toolbar without client-side relocation.
 * Version: 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_summarybar_is_surface_20260820(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}
	return is_search() && 'product' === get_query_var( 'post_type' );
}

/*
 * Keep the child theme's exact count (priority 21) as the single catalogue count.
 * Only WooCommerce's generic count is removed defensively.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	},
	1100
);

/*
 * The exact result count and canonical destination control are already emitted by
 * PHP at priorities 21 and 22 inside Woostify's .woostify-toolbar-left. We only
 * define their final responsive geometry here. No controls are cloned or moved.
 *
 * This style is printed in the footer so it is the final layout contract after
 * the historical 0.10.229 / 0.10.236 catalogue CSS loaded by the child theme.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-toolbar-layout-20260820">
			/* Retired experimental surfaces must never create a second count/control. */
			body .mdo-catalog-summarybar,
			body .mdo-catalog-toolbar__count,
			body .mdo-catalog-toolbar__destination {
				display:none !important;
			}

			/* One white toolbar, shared by results, destination and ordering. */
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:62px !important;
				max-height:none !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:12px !important;
				overflow:visible !important;
				margin:0 0 14px !important;
				padding:10px 14px !important;
				border:1px solid rgba(23,63,50,.11) !important;
				border-radius:14px !important;
				background:#fff !important;
				box-shadow:0 10px 28px rgba(17,42,34,.06) !important;
			}

			/* Woostify hides this left rail on phones when it thinks no filter belongs there. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:1 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				max-width:none !important;
				height:auto !important;
				align-items:center !important;
				gap:10px !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}

			/* Defensive duplicate suppression: preserve only EMDO's exact count. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-result-count:not(.emo-catalog-result-count-010220):not(.emo-vendor-result-count-010225) {
				display:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
				display:inline-flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 0 auto !important;
				width:auto !important;
				min-width:0 !important;
				height:auto !important;
				min-height:36px !important;
				align-items:center !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				color:#42564e !important;
				font-size:12px !important;
				font-weight:700 !important;
				line-height:1.3 !important;
				white-space:nowrap !important;
			}

			/* Canonical shipping control: no proxy button, no duplicate surface. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
				display:inline-flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				max-width:100% !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				display:inline-flex !important;
				box-sizing:border-box !important;
				max-width:100% !important;
				min-width:0 !important;
				min-height:38px !important;
				align-items:center !important;
				gap:6px !important;
				margin:0 !important;
				padding:0 11px !important;
				border:1px solid rgba(23,63,50,.16) !important;
				border-radius:999px !important;
				background:#f9fbf9 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:600 !important;
				line-height:1 !important;
				white-space:nowrap !important;
				cursor:pointer !important;
			}
			html body.elmercadodeorigen-child .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible {
				background:#f4f8f5 !important;
				border-color:rgba(23,63,50,.32) !important;
				color:#173f32 !important;
				outline:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover *,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus *,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible * {
				color:#173f32 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger > span:nth-child(2) {
				min-width:0 !important;
				overflow:hidden !important;
				text-overflow:ellipsis !important;
				white-space:nowrap !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger strong {
				color:inherit !important;
				font-weight:750 !important;
			}

			/* Ordering stays at the right and is always allowed to shrink safely. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-right {
				display:flex !important;
				box-sizing:border-box !important;
				flex:0 1 auto !important;
				min-width:0 !important;
				margin:0 0 0 auto !important;
				padding:0 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				display:flex !important;
				visibility:visible !important;
				box-sizing:border-box !important;
				position:static !important;
				flex:0 1 250px !important;
				width:250px !important;
				min-width:0 !important;
				max-width:250px !important;
				height:auto !important;
				min-height:40px !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				display:block !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				margin:0 !important;
				padding:0 30px 0 12px !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				background-color:#f7f9f6 !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:700 !important;
				line-height:1 !important;
			}

			/* Tablet / phone landscape: compact single row, still fluid. */
			@media (min-width:641px) and (max-width:991px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					min-height:64px !important;
					gap:10px !important;
					padding:10px 12px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
					gap:8px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					min-height:38px !important;
					padding:0 9px !important;
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					flex:0 1 190px !important;
					width:190px !important;
					max-width:190px !important;
					min-width:130px !important;
				}
			}

			/* Phone portrait: summary on row one, ordering on row two. */
			@media (max-width:640px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) !important;
					grid-auto-rows:auto !important;
					align-items:center !important;
					gap:8px !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					padding:10px 11px !important;
					overflow:hidden !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
					dis:grid !important;
					display:grid !important;
					grid-template-columns:auto minmax(0,1fr) !important;
					width:100% !important;
					max-width:100% !important;
					gap:8px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
					min-height:36px !important;
					font-size:11px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
					width:100% !important;
					min-width:0 !important;
					justify-self:stretch !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					min-height:38px !important;
					justify-content:flex-start !important;
					padding:0 9px !important;
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-right {
					width:100% !important;
					max-width:100% !important;
					margin:0 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					display:flex !important;
					flex:1 1 100% !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					font-size:11.5px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
