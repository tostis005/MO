<?php
/**
 * Plugin Name: MDO Catalog Toolbar Static Guard
 * Description: Disables legacy catalogue geometry/reparenting scripts so main and producer toolbars are controlled by the final static responsive contract.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether this is the general public product catalogue, not a WCFM producer store. */
function mdo_catalog_toolbar_static_guard_is_shop_20260820(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return false;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return false;
	}
	if ( get_query_var( 'store' ) ) {
		return false;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}
	return is_search() && 'product' === get_query_var( 'post_type' );
}

/** Whether this is a producer/WCFM storefront. */
function mdo_catalog_toolbar_static_guard_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
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
 * Remove only footer closures that rewrite toolbar geometry/parentage. Their PHP
 * labels and wp_head CSS remain. On producer pages two historical observers kept
 * moving result/order back into .elmercado-vendor-toolbar after the final EMDO
 * normaliser had already created the main-shop DOM; those observers are now
 * suppressed server-side instead of fighting them with another MutationObserver.
 */
add_action(
	'wp',
	static function (): void {
		$is_shop  = mdo_catalog_toolbar_static_guard_is_shop_20260820();
		$is_store = mdo_catalog_toolbar_static_guard_is_store_20260821();
		if ( ! $is_shop && ! $is_store ) {
			return;
		}

		global $wp_filter;
		if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
			return;
		}

		$targets = array(
			wp_normalize_path( get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php' ),
		);
		if ( $is_store ) {
			$targets[] = wp_normalize_path( get_stylesheet_directory() . '/inc/vendor-mobile-toolbar-final.php' );
			$targets[] = wp_normalize_path( get_stylesheet_directory() . '/inc/runtime-stability-final.php' );
		}

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
	},
	1200
);
