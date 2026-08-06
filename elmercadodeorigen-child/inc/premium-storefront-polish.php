<?php
/**
 * Premium storefront polish: cart contrast, product cards, vendor layout and interactions.
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
		<style id="elmercado-premium-storefront-polish">
			/* CART: never inherit dark text over the dark summary panel. */
			body.woocommerce-cart .cart-collaterals,
			body.woocommerce-cart .cart_totals,
			body.woocommerce-cart .cart_totals h2,
			body.woocommerce-cart .cart_totals table,
			body.woocommerce-cart .cart_totals th,
			body.woocommerce-cart .cart_totals td,
			body.woocommerce-cart .cart_totals p,
			body.woocommerce-cart .cart_totals label,
			body.woocommerce-cart .cart_totals .amount,
			body.woocommerce-cart .cart_totals .includes_tax,
			body.woocommerce-cart .cart_totals .woocommerce-shipping-destination,
			body.woocommerce-cart .cart_totals .shipping-calculator-button {
				color: #fffdf8 !important;
			}
			body.woocommerce-cart .cart_totals .shipping-calculator-button,
			body.woocommerce-cart .cart_totals a:not(.checkout-button) {
				text-decoration-color: rgba(255,253,248,.55) !important;
			}
			body.woocommerce-cart .cart_totals tr,
			body.woocommerce-cart .cart_totals th,
			body.woocommerce-cart .cart_totals td {
				border-color: rgba(255,255,255,.24) !important;
			}
			body.woocommerce-cart .cart_totals .checkout-button {
				color: #173f32 !important;
			}

			/* VENDOR TOOLBAR: clear separation below tabs and no ornamental line. */
			body.wcfmmp-store-page #wcfmmp-store .tab_area,
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area,
			body.wcfmmp-store-page #wcfmmp-store .tab_links,
			body.wcfmmp-store-page #wcfmmp-store .tab_links::before,
			body.wcfmmp-store-page #wcfmmp-store .tab_links::after {
				border: 0 !important;
				box-shadow: none !important;
				background-image: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .tab_area {
				margin-bottom: 2rem !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count,
			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				margin-top: .75rem !important;
				margin-bottom: 1.8rem !important;
			}

			/* SOLD BY: preserve a visible gap regardless of plugin whitespace. */
			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper,
			body.elmercado-child-theme [class*="sold_by"] {
				column-gap: .45rem !important;
			}
			body.elmercado-child-theme .wcfmmp_sold_by_label::after {
				content: "\00a0";
			}

			/* MINICART: explicit three-column control, with the input above plugin icons. */
			body.elmercado-child-theme #shop-cart-sidebar .quantity,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity {
				display: grid !important;
				grid-template-columns: 38px 48px 38px !important;
				width: 124px !important;
				height: 40px !important;
				min-width: 124px !important;
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 999px !important;
				background: #fff !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity .minus,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .plus,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty {
				position: relative !important;
				z-index: 1 !important;
				display: grid !important;
				width: 38px !important;
				height: 38px !important;
				place-items: center !important;
				font-size: 0 !important;
				line-height: 1 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity .minus::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty.minus::after {
				content: "−" !important;
				font-size: 20px !important;
				font-weight: 600 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity .plus::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty.plus::after {
				content: "+" !important;
				font-size: 20px !important;
				font-weight: 600 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity input.qty,
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				position: relative !important;
				z-index: 3 !important;
				grid-column: 2 !important;
				width: 48px !important;
				height: 38px !important;
				padding: 0 !important;
				border: 0 !important;
				border-inline: 1px solid rgba(23,63,50,.12) !important;
				background: #fff !important;
				color: #173f32 !important;
				font-size: 16px !important;
				font-weight: 700 !important;
				line-height: 38px !important;
				text-align: center !important;
				appearance: textfield !important;
			}

			/* PRODUCT CARDS: softer media stage prevents coloured source backgrounds looking cut out. */
			body.elmercado-child-theme ul.products li.product {
				position: relative !important;
				border-radius: 16px !important;
				transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease !important;
			}
			body.elmercado-child-theme ul.products li.product:hover {
				transform: translateY(-4px) !important;
				border-color: rgba(23,63,50,.22) !important;
				box-shadow: 0 18px 42px rgba(17,42,34,.13) !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				margin: .65rem .65rem 0 !important;
				padding: clamp(.45rem,.9vw,.7rem) !important;
				border-radius: 12px !important;
				background: linear-gradient(145deg,#faf8f2,#f2eee4) !important;
				aspect-ratio: 4 / 5 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				border-radius: 9px !important;
				mix-blend-mode: multiply;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content,
			body.elmercado-child-theme ul.products li.product .product-content {
				border-top: 0 !important;
				padding-top: .85rem !important;
			}
			body.elmercado-child-theme ul.products li.product:focus-within {
				outline: 3px solid rgba(202,171,92,.55) !important;
				outline-offset: 3px !important;
			}

			/* Four balanced columns on desktop; three on medium screens. */
			@media (min-width: 1180px) {
				body.woocommerce-shop ul.products,
				body.tax-product_cat ul.products,
				body.tax-product_tag ul.products,
				body.wcfmmp-store-page #wcfmmp-store ul.products,
				body.home .emo-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
					gap: 1.35rem !important;
				}
			}
			@media (min-width: 768px) and (max-width: 1179px) {
				body.woocommerce-shop ul.products,
				body.tax-product_cat ul.products,
				body.wcfmmp-store-page #wcfmmp-store ul.products,
				body.home .emo-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(3,minmax(0,1fr)) !important;
				}
			}

			/* HOME category cards: portrait framing and contain the whole subject. */
			body.home .emo-categories__grid {
				align-items: stretch !important;
			}
			body.home .emo-category-card {
				min-height: clamp(360px,38vw,500px) !important;
				aspect-ratio: 3 / 4 !important;
			}
			body.home .emo-category-card img,
			body.home .emo-category-card picture,
			body.home .emo-category-card picture img {
				width: 100% !important;
				height: 100% !important;
				object-fit: contain !important;
				object-position: center !important;
				background: #f4f0e7 !important;
			}

			@media (max-width: 767px) {
				body.home .emo-category-card { min-height: 390px !important; }
				body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper { margin: .5rem .5rem 0 !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-clickable-product-cards">
		(() => {
			'use strict';
			const interactive = 'a,button,input,select,textarea,label,[role="button"]';
			const wire = (root = document) => {
				root.querySelectorAll?.('ul.products li.product:not([data-emo-card-wired])').forEach((card) => {
					const link = card.querySelector('a.woocommerce-LoopProduct-link,a.woocommerce-loop-product__link,a[href*="/producto/"]');
					if (!link) return;
					card.dataset.emoCardWired = '1';
					card.style.cursor = 'pointer';
					card.addEventListener('click', (event) => {
						if (event.target.closest(interactive)) return;
						window.location.assign(link.href);
					});
				});
			};
			wire();
			new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
				if (node.nodeType === 1) wire(node);
			}))).observe(document.body,{childList:true,subtree:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
