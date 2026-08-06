<?php
/**
 * Explicit minicart quantity labels after all inherited Woostify styles.
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
		<style id="elmercado-minicart-final-control">
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty {
				position: relative !important;
				font-size: 0 !important;
				line-height: 0 !important;
				color: transparent !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span *,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty * {
				display: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty::after {
				content: none !important;
				display: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:first-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:first-child::after {
				content: "−" !important;
				display: block !important;
				font-family: Arial, sans-serif !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				line-height: 38px !important;
				text-align: center !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:last-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:last-child::after {
				content: "+" !important;
				display: block !important;
				font-family: Arial, sans-serif !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				line-height: 38px !important;
				text-align: center !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				font-family: Arial, sans-serif !important;
				font-size: 16px !important;
				font-weight: 700 !important;
				line-height: 38px !important;
				text-align: center !important;
				color: #173f32 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
