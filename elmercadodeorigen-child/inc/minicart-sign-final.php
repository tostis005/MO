<?php
/**
 * Removes duplicated pseudo glyphs from minicart quantity buttons.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-minicart-sign-final">
			body.elmercado-child-theme #shop-cart-sidebar span.mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar span.mini-cart-product-qty::after,
			body.elmercado-child-theme #shop-cart-sidebar .quantity span.mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity span.mini-cart-product-qty::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity span.mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity span.mini-cart-product-qty::after {
				content: none !important;
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
