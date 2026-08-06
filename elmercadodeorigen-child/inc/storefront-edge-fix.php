<?php
/**
 * Final edge fixes for product media and minicart quantity controls.
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
		<style id="elmercado-storefront-edge-fix">
			/* Remove every inherited pseudo-icon first, then draw only the two real controls. */
			body.elmercado-child-theme #shop-cart-sidebar .quantity > *::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > *::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > *::before,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > *::after {
				content: none !important;
				display: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:first-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:first-child::after {
				content: "−" !important;
				display: block !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				line-height: 1 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:last-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:last-child::after {
				content: "+" !important;
				display: block !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				line-height: 1 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty::before,
			body.elmercado-child-theme #shop-cart-sidebar input.qty::after {
				content: none !important;
				display: none !important;
			}

			/* Product imagery sits flush against the card with no beige/white inner frame. */
			body.elmercado-child-theme ul.products li.product {
				border: 0 !important;
				background: #fff !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-wrapper,
			body.elmercado-child-theme ul.products li.product .woocommerce-LoopProduct-link,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link,
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				border: 0 !important;
				box-shadow: none !important;
				background: transparent !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				width: calc(100% + 2px) !important;
				height: calc(100% + 2px) !important;
				max-width: none !important;
				margin: -1px !important;
				padding: 0 !important;
				border: 0 !important;
				outline: 0 !important;
				border-radius: 0 !important;
				object-fit: contain !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper::after {
				height: 15% !important;
				background: linear-gradient(180deg,rgba(255,255,255,0),#fff 94%) !important;
			}

			/* One complete six-product row on the desktop home page. */
			@media (min-width: 1280px) {
				body.home .emo-featured-products ul.products,
				body.home .emo-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(6,minmax(0,1fr)) !important;
					gap: 1rem !important;
				}
				body.home .emo-featured-products ul.products li.product,
				body.home .emo-products ul.products li.product {
					width: auto !important;
					margin: 0 !important;
				}
			}
			@media (min-width: 980px) and (max-width: 1279px) {
				body.home .emo-featured-products ul.products,
				body.home .emo-products ul.products {
					grid-template-columns: repeat(3,minmax(0,1fr)) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
