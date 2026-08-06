<?php
/**
 * Final storefront pass: contrast, product cards, vendor tabs and minicart.
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
		<style id="elmercado-storefront-final-pass">
			/* HOME: every text over dark or photographic surfaces must remain readable. */
			body.home .emo-hero,
			body.home .emo-hero__copy,
			body.home .emo-hero__copy h1,
			body.home .emo-hero__copy h1 em,
			body.home .emo-hero__copy p,
			body.home .emo-hero__proof,
			body.home .emo-hero__proof strong,
			body.home .emo-kicker--light {
				color: #fffdf8 !important;
			}
			body.home .emo-hero__copy p,
			body.home .emo-hero__proof span {
				opacity: 1 !important;
			}
			body.home .emo-hero__copy p { color: rgba(255,253,248,.88) !important; }
			body.home .emo-hero__proof span { color: rgba(255,253,248,.78) !important; }
			body.home .emo-hero__proof strong { opacity: 1 !important; }
			body.home .emo-button--ghost {
				border-color: rgba(255,255,255,.58) !important;
				color: #fffdf8 !important;
				background: rgba(255,255,255,.06) !important;
			}
			body.home .emo-button--ghost:hover,
			body.home .emo-button--ghost:focus-visible {
				background: #fffdf8 !important;
				color: #173f32 !important;
			}

			body.home .emo-category-card::after {
				content: "";
				position: absolute;
				inset: 0;
				z-index: 1;
				background: linear-gradient(180deg, transparent 34%, rgba(7,27,21,.30) 60%, rgba(7,27,21,.90) 100%) !important;
				pointer-events: none;
			}
			body.home .emo-category-card__content,
			body.home .emo-category-card > svg {
				position: relative !important;
				z-index: 2 !important;
			}
			body.home .emo-category-card__content strong,
			body.home .emo-category-card__content small,
			body.home .emo-category-card > svg {
				color: #fffdf8 !important;
				fill: none !important;
				stroke: #fffdf8 !important;
				opacity: 1 !important;
				text-shadow: 0 1px 12px rgba(0,0,0,.45);
			}
			body.home .emo-category-card__content small { color: rgba(255,253,248,.88) !important; }

			/* PRODUCT CARDS: one complete white card, larger image, no beige inset frame. */
			body.elmercado-child-theme ul.products li.product {
				border: 1px solid rgba(23,63,50,.10) !important;
				border-radius: 18px !important;
				background: #fff !important;
				overflow: hidden !important;
				box-shadow: 0 10px 28px rgba(17,42,34,.07) !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-wrapper {
				display: flex !important;
				min-height: 100% !important;
				flex-direction: column !important;
				border: 0 !important;
				border-radius: 0 !important;
				background: #fff !important;
				box-shadow: none !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper,
			body.elmercado-child-theme ul.products li.product .woocommerce-LoopProduct-link,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link {
				background: #fff !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				padding: clamp(.55rem,1.2vw,.9rem) !important;
				aspect-ratio: 3 / 4 !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				width: 100% !important;
				height: 100% !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
				object-fit: contain !important;
				background: #fff !important;
				border-radius: 0 !important;
				transform: none !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-content,
			body.elmercado-child-theme ul.products li.product .product-content {
				padding: 1rem 1.1rem 1.15rem !important;
				border-top: 1px solid rgba(23,63,50,.08) !important;
				background: #fff !important;
			}

			/* VENDOR: remove dividing line and give each view/results toolbar breathing room. */
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area {
				margin: 0 0 1.8rem !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .tab_links {
				display: flex !important;
				gap: .65rem !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				list-style: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .tab_links li,
			body.wcfmmp-store-page #wcfmmp-store .tab_links li::before,
			body.wcfmmp-store-page #wcfmmp-store .tab_links li::after {
				border: 0 !important;
				box-shadow: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store #products,
			body.wcfmmp-store-page #wcfmmp-store .product_area,
			body.wcfmmp-store-page #wcfmmp-store #products-wrapper {
				padding-top: 0 !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				margin-top: .45rem !important;
				margin-bottom: 1.75rem !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count {
				padding-inline: .25rem !important;
			}

			/* Sold by: force an actual visual gap even when plugin markup has line breaks. */
			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper {
				display: flex !important;
				align-items: baseline !important;
				flex-wrap: wrap !important;
				column-gap: .5rem !important;
				row-gap: .15rem !important;
			}
			body.elmercado-child-theme .wcfmmp_sold_by_label { margin: 0 !important; }
			body.elmercado-child-theme .wcfmmp_sold_by_container a,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper a {
				display: inline-block !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			/* MINICART: isolate each control in its own grid cell; reset plugin pseudo/icons. */
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity,
			body.elmercado-child-theme #shop-cart-sidebar .quantity {
				position: relative !important;
				display: grid !important;
				grid-template-columns: 36px 46px 36px !important;
				width: 118px !important;
				min-width: 118px !important;
				height: 38px !important;
				min-height: 38px !important;
				align-items: center !important;
				justify-items: center !important;
				gap: 0 !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .minus,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .plus {
				position: static !important;
				inset: auto !important;
				display: grid !important;
				width: 36px !important;
				height: 36px !important;
				min-width: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				place-items: center !important;
				transform: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty *,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .minus *,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .plus * {
				position: static !important;
				inset: auto !important;
				margin: 0 !important;
				transform: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				position: static !important;
				grid-column: 2 !important;
				width: 46px !important;
				height: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 36px !important;
				text-align: center !important;
				transform: none !important;
			}

			/* Header cart badge: keep it attached to the cart icon. */
			body.elmercado-child-theme .site-tools .shopping-cart,
			body.elmercado-child-theme .site-tools .shopping-bag-button {
				position: relative !important;
			}
			body.elmercado-child-theme .site-tools .shopping-cart .shop-cart-count,
			body.elmercado-child-theme .site-tools .shopping-cart .cart-count,
			body.elmercado-child-theme .site-tools .shopping-bag-button .shop-cart-count,
			body.elmercado-child-theme .site-tools .shopping-bag-button .cart-count {
				position: absolute !important;
				top: 3px !important;
				right: 2px !important;
				margin: 0 !important;
				transform: translate(35%,-35%) !important;
			}

			@media (max-width: 767px) {
				body.wcfmmp-store-page #wcfmmp-store .tab_links { flex-wrap: wrap !important; }
				body.wcfmmp-store-page #wcfmmp-store .tab_links_area { margin-bottom: 1.25rem !important; }
				body.wcfmmp-store-page #wcfmmp-store .woostify-sorting { margin-bottom: 1.35rem !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
