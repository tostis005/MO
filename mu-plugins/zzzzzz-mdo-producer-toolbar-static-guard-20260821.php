<?php
/**
 * Plugin Name: MDO Producer Toolbar Static Guard
 * Description: Prevents legacy child-theme footer scripts from rewriting the producer toolbar after the shared toolbar UX has mounted.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect a public WCFM producer store without depending on one specific plugin helper.
 */
function mdo_producer_toolbar_static_guard_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) && mdo_ps_toolbar_ux_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return (bool) get_query_var( 'store' );
}

/**
 * Remove only the two historical footer closures that continuously write inline
 * toolbar geometry. This runs at the start of wp_footer, after the route is fully
 * resolved but before either legacy PHP_INT_MAX callback can print its script.
 */
function mdo_producer_toolbar_static_guard_remove_legacy_footer_20260821(): void {
	if ( ! mdo_producer_toolbar_static_guard_is_store_20260821() ) {
		return;
	}

	global $wp_filter;
	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}

	$targets = array(
		wp_normalize_path( get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php' ),
		wp_normalize_path( get_stylesheet_directory() . '/inc/vendor-toolbar-mobile-final.php' ),
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
add_action( 'wp_footer', 'mdo_producer_toolbar_static_guard_remove_legacy_footer_20260821', -999999 );

/**
 * Phone width uses the producer content box, not 100vw. Chrome includes the
 * scrollbar in vw on this layout, which made the producer card ~15px too wide.
 */
function mdo_producer_toolbar_static_guard_css_20260821(): void {
	if ( ! mdo_producer_toolbar_static_guard_is_store_20260821() ) {
		return;
	}
	?>
	<style id="mdo-producer-toolbar-static-guard-20260821">
		@media (max-width:640px) {
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
				left:auto !important;
				width:calc(100% + 34px) !important;
				min-width:calc(100% + 34px) !important;
				max-width:calc(100% + 34px) !important;
				margin-left:-17px !important;
				margin-right:-17px !important;
				transform:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .elmercado-vendor-toolbar {
				display:contents !important;
				margin:0 !important;
				padding:0 !important;
				transform:none !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_producer_toolbar_static_guard_css_20260821', PHP_INT_MAX );
