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
			body.woocommerce-cart .cart_totals .shipping-calculator-button,
			body.woocommerce-cart .cart_totals .woocommerce-shipping-methods,
			body.woocommerce-cart .cart_totals .woocommerce-shipping-methods * {
				color: #fffdf8 !important;
			}
			body.woocommerce-cart .cart_totals tr,
			body.woocommerce-cart .cart_totals th,
			body.woocommerce-cart .cart_totals td { border-color: rgba(255,255,255,.24) !important; }
			body.woocommerce-cart .cart_totals .checkout-button { color: #173f32 !important; }

			/* VENDOR: separate navigation from the results panel using the real WCFM containers. */
			body.wcfmmp-store-page #wcfmmp-store .tab_area,
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area,
			body.wcfmmp-store-page #wcfmmp-store .tab_links,
			body.wcfmmp-store-page #wcfmmp-store .tab_links::before,
			body.wcfmmp-store-page #wcfmmp-store .tab_links::after {
				border: 0 !important;
				box-shadow: none !important;
				background-image: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .tab_area { margin-bottom: 2.25rem !important; }
			body.wcfmmp-store-page #wcfmmp-store .right_side_full,
			body.wcfmmp-store-page #wcfmmp-store .right_side,
			body.wcfmmp-store-page #wcfmmp-store .product_area {
				padding-top: 1.1rem !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 1rem !important;
				margin: 0 0 2rem !important;
				padding: 1rem 1.15rem !important;
				border-radius: 14px !important;
				background: #fff !important;
				box-shadow: 0 10px 28px rgba(17,42,34,.06) !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count { margin: 0 !important; padding: 0 !important; }

			/* SOLD BY: preserve an actual visual gap. */
			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper,
			body.elmercado-child-theme [class*="sold_by"] {
				display: flex !important;
				align-items: baseline !important;
				flex-wrap: wrap !important;
				column-gap: .5rem !important;
				row-gap: .15rem !important;
			}
			body.elmercado-child-theme .wcfmmp_sold_by_label::after { content: "" !important; }

			/* MINICART: explicit signs on the actual first/last empty controls. */
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
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span {
				position: static !important;
				display: grid !important;
				width: 38px !important;
				height: 38px !important;
				place-items: center !important;
				font-size: 0 !important;
				line-height: 1 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:first-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:first-child::after {
				content: "−" !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > span:last-child::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > .mini-cart-product-qty:last-child::after {
				content: "+" !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				position: static !important;
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
				transform: none !important;
			}

			/* PRODUCT CARDS: large media, no inset frame, soft fade into content. */
			body.elmercado-child-theme ul.products li.product {
				position: relative !important;
				border: 1px solid rgba(23,63,50,.09) !important;
				border-radius: 17px !important;
				background: #fff !important;
				overflow: hidden !important;
				box-shadow: 0 10px 30px rgba(17,42,34,.07) !important;
				transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease !important;
			}
			body.elmercado-child-theme ul.products li.product:hover {
				transform: translateY(-4px) !important;
				border-color: rgba(23,63,50,.18) !important;
				box-shadow: 0 18px 42px rgba(17,42,34,.12) !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				position: relative !important;
				margin: 0 !important;
				padding: .35rem .35rem 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				background: linear-gradient(180deg,#faf9f5 0%,#f7f4ec 82%,#fff 100%) !important;
				aspect-ratio: 3 / 4 !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper::after {
				content: "" !important;
				position: absolute !important;
				left: 0 !important;
				right: 0 !important;
				bottom: 0 !important;
				height: 18% !important;
				background: linear-gradient(180deg,rgba(255,255,255,0),#fff 88%) !important;
				pointer-events: none !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				width: 100% !important;
				height: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				border-radius: 0 !important;
				object-fit: contain !important;
				background: transparent !important;
				mix-blend-mode: normal !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content,
			body.elmercado-child-theme ul.products li.product .product-content {
				position: relative !important;
				z-index: 2 !important;
				margin-top: -1.2rem !important;
				padding: 1.3rem 1rem 1.15rem !important;
				border: 0 !important;
				background: #fff !important;
			}

			/* Four products per row on desktop, including every home product section. */
			@media (min-width: 1180px) {
				body.woocommerce-shop ul.products,
				body.tax-product_cat ul.products,
				body.tax-product_tag ul.products,
				body.wcfmmp-store-page #wcfmmp-store ul.products,
				body.home ul.products {
					display: grid !important;
					grid-template-columns: repeat(4,minmax(0,1fr)) !important;
					gap: 1.25rem !important;
				}
				body.home ul.products li.product { width: auto !important; margin: 0 !important; }
			}
			@media (min-width: 768px) and (max-width: 1179px) {
				body.woocommerce-shop ul.products,
				body.tax-product_cat ul.products,
				body.wcfmmp-store-page #wcfmmp-store ul.products,
				body.home ul.products {
					display: grid !important;
					grid-template-columns: repeat(3,minmax(0,1fr)) !important;
				}
			}

			/* HOME categories: less oversized, portrait but not excessively tall. */
			body.home .emo-category-card {
				min-height: 0 !important;
				aspect-ratio: 4 / 5 !important;
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
				body.wcfmmp-store-page #wcfmmp-store .woostify-sorting { align-items: flex-start !important; flex-direction: column !important; }
				body.home .emo-category-card { aspect-ratio: 4 / 5 !important; }
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
