<?php
/**
 * Plugin Name: MDO Catalog Toolbar Static Guard
 * Description: Disables the legacy mobile catalogue geometry script on public catalogue surfaces so the toolbar is controlled purely by responsive CSS.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this is the general public product catalogue, not a WCFM producer store.
 */
function mdo_catalog_toolbar_static_guard_is_shop_20260820(): bool {
	if ( is_admin() ) {
		return false;
	}

	/* Do not alter the producer-store parity script on WCFM store pages. */
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

/**
 * Remove only the footer closure that writes inline !important geometry for the
 * catalogue toolbar/order control. The module's PHP labels and wp_head CSS stay.
 *
 * This is server-side hook cleanup: no MutationObserver, no DOM movement and no
 * browser resize/reflow script is added in its place.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_catalog_toolbar_static_guard_is_shop_20260820() ) {
			return;
		}

		global $wp_filter;
		if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
			return;
		}

		$target = wp_normalize_path( get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php' );
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
				if ( is_string( $filename ) && wp_normalize_path( $filename ) === $target ) {
					remove_action( 'wp_footer', $callback, (int) $priority );
				}
			}
		}
	},
	1200
);
