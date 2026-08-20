<?php
/**
 * Plugin Name: MDO Catalog Toolbar Layout
 * Description: Keeps the exact result count, canonical shipping destination and WooCommerce ordering together inside Woostify's native white toolbar.
 * Version: 1.6.0
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

/**
 * Normalize the server-side hook layout after every MU plugin and the theme have
 * registered their callbacks. No browser-side relocation is used.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}

		/* Keep only EMDO's exact result count. */
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

		/*
		 * The canonical destination renderer belongs between Woostify's left rail
		 * open (15) and close (25). Remove any historical footer placement first.
		 */
		if ( function_exists( 'mdo_catalog_default_spain_render_20260820' ) ) {
			remove_action( 'wp_footer', 'mdo_catalog_default_spain_render_20260820', 5 );
			remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
			add_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
		}
	},
	1100
);

/**
 * Final geometry contract. Printed last so historical catalogue CSS cannot force
 * the old 68px mobile single row or fixed 148px ordering width back into place.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-toolbar-layout-20260820">
			/* Retired experimental surfaces never render a second visible count/control. */
			body .mdo-catalog-summarybar,
			body .mdo-catalog-toolbar__count,
			body .mdo-catalog-toolbar__destination {
				display:none !important;
			}

			/* Single white catalogue toolbar. */
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

			/* Left group: exact count + destination. */
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
				min-height:40px !important;
				align-items:center !important;
				gap:10px !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}

			/* Any generic Woo count is hidden; the exact EMDO count remains. */
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
				height:38px !important;
				min-height:38px !important;
				max-height:38px !important;
				align-items:center !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				color:#42564e !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:700 !important;
				line-height:1.3 !important;
				white-space:nowrap !important;
			}

			/* Real canonical destination trigger. */
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
				align-items:center !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				display:inline-flex !important;
				visibility:visible !important;
				opacity:1 !important;
				box-sizing:border-box !important;
				width:auto !important;
				min-width:0 !important;
				max-width:100% !important;
				height:38px !important;
				min-height:38px !important;
				max-height:38px !important;
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
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible {
				background:#f4f8f5 !important;
				border-color:rgba(23,63,50,.32) !important;
				box-shadow:none !important;
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

			/* Ordering is the final direct child of Woostify's sorting wrapper. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				display:flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 1 250px !important;
				width:250px !important;
				min-width:0 !important;
				max-width:250px !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				align-items:center !important;
				margin:0 0 0 auto !important;
				padding:0 !important;
				float:none !important;
				clear:none !important;
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
				max-height:40px !important;
				margin:0 !important;
				padding:0 30px 0 12px !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				background-color:#f7f9f6 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:700 !important;
				letter-spacing:0 !important;
				line-height:1 !important;
			}

			/* Tablet and landscape phone: compact single row. */
			@media (min-width:641px) and (max-width:991px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					display:flex !important;
					height:auto !important;
					min-height:62px !important;
					max-height:none !important;
					gap:10px !important;
					padding:10px 12px !important;
					overflow:visible !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
					display:flex !important;
					flex:1 1 auto !important;
					width:auto !important;
					min-width:0 !important;
					gap:8px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					padding:0 9px !important;
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					flex:0 1 190px !important;
					width:190px !important;
					min-width:130px !important;
					max-width:190px !important;
				}
			}

			/* Phone portrait: first row count + destination, second row ordering. */
			@media (max-width:640px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) !important;
					grid-template-rows:auto auto !important;
					align-items:center !important;
					justify-items:stretch !important;
					gap:8px !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					overflow:visible !important;
					padding:10px 11px !important;
			}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
					display:grid !important;
					grid-column:1 !important;
					grid-row:1 !important;
					grid-template-columns:max-content minmax(0,1fr) !important;
					align-items:center !important;
					gap:8px !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:auto !important;
					min-height:38px !important;
					max-height:none !important;
					overflow:visible !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
					grid-column:1 !important;
					grid-row:1 !important;
					width:auto !important;
					min-width:0 !important;
					font-size:11px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
					grid-column:2 !important;
					grid-row:1 !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					justify-self:stretch !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:38px !important;
					min-height:38px !important;
					max-height:38px !important;
					justify-content:flex-start !important;
					padding:0 9px !important;
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					grid-column:1 !important;
					grid-row:2 !important;
					justify-self:stretch !important;
					flex:none !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					font-size:11.5px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
