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
				margin: 0 0 2rem !important;
				padding: 0 !important;
				border: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store #products {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/* Home shortcode is emitted as columns-3: override its exact component. */
			@media (min-width: 1180px) {
				body .emo-featured-products ul.products.columns-3 {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
					gap: 1.2rem !important;
				}
				body .emo-featured-products ul.products.columns-3 > li.product {
					float: none !important;
					clear: none !important;
					width: auto !important;
					max-width: none !important;
					margin: 0 !important;
				}
			}

			/* Keep product imagery dominant and blend it into the information area. */
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				padding: .15rem .15rem 0 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content {
				margin-top: -1.55rem !important;
				padding-top: 1.65rem !important;
			}

			/* Empty WCFM controls receive explicit visible signs independent of classes. */
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
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty::after {
				content: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
