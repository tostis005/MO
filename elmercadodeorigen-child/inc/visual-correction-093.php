<?php
/**
 * Verified visual corrections after deployed screenshot review.
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
		<style id="elmercado-visual-correction-093">
			/* The WCFM tab container also wraps the products; space the links themselves. */
			body.wcfmmp-store-page #wcfmmp-store #tab_links_area {
				margin: 0 0 2.25rem !important;
				padding: 0 !important;
				border: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store #products {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count,
			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				margin-top: 1rem !important;
				margin-bottom: 2rem !important;
			}

			/* One complete row of six featured products on wide desktop. */
			@media (min-width: 1360px) {
				body .emo-featured-products ul.products.columns-3,
				body .emo-featured-products ul.products.columns-4,
				body .emo-featured-products ul.products.columns-6 {
					display: grid !important;
					grid-template-columns: repeat(6,minmax(0,1fr)) !important;
					gap: 1rem !important;
				}
				body .emo-featured-products ul.products > li.product {
					float: none !important;
					clear: none !important;
					width: auto !important;
					max-width: none !important;
					margin: 0 !important;
				}
			}
			@media (min-width: 1024px) and (max-width: 1359px) {
				body .emo-featured-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
				}
			}

			/* Product media touches the card edges: no beige/white inset frame. */
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper,
			body.elmercado-child-theme ul.products li.product .woocommerce-LoopProduct-link,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link {
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				position: relative !important;
				aspect-ratio: 3 / 4 !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				display: block !important;
				width: calc(100% + 2px) !important;
				height: calc(100% + 2px) !important;
				max-width: none !important;
				margin: -1px !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				object-fit: contain !important;
				object-position: center !important;
				background: transparent !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content,
			body.elmercado-child-theme ul.products li.product .product-content {
				position: relative !important;
				z-index: 2 !important;
				margin-top: -2.25rem !important;
				padding-top: 2.55rem !important;
				background: linear-gradient(180deg,rgba(255,255,255,0) 0,#fff 2.15rem,#fff 100%) !important;
			}

			/* Remove every inherited pseudo-icon, then draw only the two real buttons. */
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty::after,
			body.elmercado-child-theme #shop-cart-sidebar .quantity::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity::after,
			body.elmercado-child-theme #shop-cart-sidebar input.qty::before,
			body.elmercado-child-theme #shop-cart-sidebar input.qty::after {
				content: none !important;
				display: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:first-of-type::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:first-of-type::before {
				content: "−" !important;
				display: block !important;
				font: 700 21px/1 system-ui,sans-serif !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:last-of-type::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:last-of-type::before {
				content: "+" !important;
				display: block !important;
				font: 700 21px/1 system-ui,sans-serif !important;
				color: #173f32 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
