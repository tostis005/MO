<?php
/**
 * Plugin Name: MDO Catalogue CSS-only Safety 2026-08-28
 * Description: Makes catalogue toolbar geometry CSS-only and retires late runtime style writers that can fight WCFM rerenders.
 * Version: 1.0.2
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove only presentation callbacks from known catalogue layout owners.
 * Functional catalogue, filtering, destination and WooCommerce ordering logic
 * are deliberately untouched.
 */
function mdo_catalog_css_only_retire_runtime_layout_20260828(): void {
	global $wp_filter;

	$direct = array(
		'mdo_catalog_control_widths_output_20260828',
		'mdo_catalog_mobile_runtime_guard_output_20260824',
		'mdo_catalog_top_controls_parity_final_output_20260824',
		'mdo_catalog_top_controls_arrow_final_output_20260824',
	);
	foreach ( $direct as $callback ) {
		if ( function_exists( $callback ) ) {
			remove_action( 'wp_footer', $callback, PHP_INT_MAX );
		}
	}

	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}

	$targets = array_map(
		'wp_normalize_path',
		array(
			WP_CONTENT_DIR . '/mu-plugins/zzzzzzzzzzzzzzzzzz-mdo-catalog-control-widths-20260828.php',
			WP_CONTENT_DIR . '/mu-plugins/zzzzzzzzzzzzzz-mdo-catalog-mobile-runtime-guard-20260824.php',
			WP_CONTENT_DIR . '/mu-plugins/zzzzzzzzzzzz-mdo-catalog-top-controls-parity-final-20260824.php',
			WP_CONTENT_DIR . '/mu-plugins/zzzzzzzzzzzzz-mdo-catalog-top-controls-arrow-final-20260824.php',
			get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php',
		)
	);

	foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback_data ) {
			$callback = $callback_data['function'] ?? null;
			if ( ! $callback instanceof Closure ) {
				continue;
			}
			try {
				$reflection = new ReflectionFunction( $callback );
				$filename   = $reflection->getFileName();
			} catch ( Throwable $throwable ) {
				continue;
			}
			if ( is_string( $filename ) && in_array( wp_normalize_path( $filename ), $targets, true ) ) {
				remove_action( 'wp_footer', $callback, (int) $priority );
			}
		}
	}
}
add_action( 'wp_loaded', 'mdo_catalog_css_only_retire_runtime_layout_20260828', PHP_INT_MAX );

/**
 * Final geometry owner. CSS only: no runtime DOM watching or style mutation.
 */
function mdo_catalog_css_only_style_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-css-only-safety-20260828">
	@media (max-width:767px) {
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
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
			padding:11px !important;
		}

		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
			position:relative !important;
			left:50% !important;
			width:calc(100vw - 32px) !important;
			min-width:calc(100vw - 32px) !important;
			max-width:calc(100vw - 32px) !important;
			transform:translateX(-50%) !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
			display:contents !important;
	}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-destination-open]),
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left > :has([data-mdo-ps-destination-open]) {
			grid-column:1 !important;
			grid-row:2 !important;
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			margin:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-result-count,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
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
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination),
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination) {
			grid-column:1 !important;
			grid-row:2 !important;
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			min-height:40px !important;
			max-height:40px !important;
			margin:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
			margin:0 !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 form.woocommerce-ordering,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 form.woocommerce-ordering,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 form.woocommerce-ordering,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
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

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"],
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"] {
			display:block !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:none !important;
			float:none !important;
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
			transform:none !important;
			pointer-events:auto !important;
		}

		/* Global shop has late historical mobile CSS with the same !important
		 * declarations. Repeat the real classes to make this CSS-only owner
		 * unambiguously stronger without resorting to inline JS styles. */
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
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination.mdo-catalog-destination--canonical {
			grid-column:1 !important;
			grid-row:2 !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:40px !important;
		}
	}
	</style>
	<?php
}
add_action( 'wp_footer', 'mdo_catalog_css_only_style_20260828', PHP_INT_MAX );
