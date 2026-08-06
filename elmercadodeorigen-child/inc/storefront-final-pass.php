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
			/* HOME: permanent high contrast on photographic and dark surfaces. */
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
			body.home .emo-hero__copy p {
				color: rgba(255,253,248,.94) !important;
				opacity: 1 !important;
				text-shadow: 0 1px 12px rgba(0,0,0,.28);
			}
			body.home .emo-hero__proof span {
				color: rgba(255,253,248,.88) !important;
				opacity: 1 !important;
			}
			body.home .emo-hero__proof strong { opacity: 1 !important; }
			body.home .emo-button--ghost {
				border-color: rgba(255,255,255,.65) !important;
				color: #fffdf8 !important;
				background: rgba(255,255,255,.08) !important;
			}
			body.home .emo-button--ghost:hover,
			body.home .emo-button--ghost:focus-visible {
				background: #fffdf8 !important;
				color: #173f32 !important;
			}

			body.home .emo-category-card {
				isolation: isolate !important;
			}
			body.home .emo-category-card::after {
				content: "";
				position: absolute;
				inset: 0;
				z-index: 1;
				background: linear-gradient(180deg, rgba(7,27,21,.02) 22%, rgba(7,27,21,.42) 58%, rgba(7,27,21,.94) 100%) !important;
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
				text-shadow: 0 2px 14px rgba(0,0,0,.62);
			}
			body.home .emo-category-card__content small {
				color: rgba(255,253,248,.96) !important;
				font-weight: 600 !important;
			}

			/* PRODUCT CARDS: one complete white card with a larger integrated image. */
			body.elmercado-child-theme ul.products li.product {
				border: 1px solid rgba(23,63,50,.12) !important;
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
				padding: clamp(.3rem,.7vw,.55rem) !important;
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
				border-top: 1px solid rgba(23,63,50,.09) !important;
				background: #fff !important;
			}

			/* VENDOR: clean tab navigation and clear separation from result controls. */
			body.wcfmmp-store-page #wcfmmp-store .tab_area,
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area,
			body.wcfmmp-store-page #wcfmmp-store #tab_links_area {
				margin: 0 0 1.35rem !important;
				padding: 0 !important;
				border: 0 !important;
				border-top: 0 !important;
				border-bottom: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .tab_area::before,
			body.wcfmmp-store-page #wcfmmp-store .tab_area::after,
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area::before,
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area::after {
				display: none !important;
				content: none !important;
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
				margin-top: .95rem !important;
				margin-bottom: 1.75rem !important;
				padding-top: .15rem !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count {
				margin: 0 !important;
				padding: .45rem .25rem !important;
				line-height: 1.45 !important;
			}

			/* Sold by: ensure a visible gap independent of plugin line breaks. */
			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper {
				display: flex !important;
				align-items: baseline !important;
				flex-wrap: wrap !important;
				column-gap: .5rem !important;
				row-gap: .15rem !important;
			}
			body.elmercado-child-theme .wcfmmp_sold_by_label {
				display: inline-block !important;
				margin: 0 .45rem 0 0 !important;
				white-space: nowrap !important;
			}
			body.elmercado-child-theme .wcfmmp_sold_by_container a,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper a {
				display: inline-block !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			/* MINICART: fixed three-cell control with explicit minus/plus glyphs. */
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
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 999px !important;
				background: #fff !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty {
				position: relative !important;
				inset: auto !important;
				display: grid !important;
				width: 36px !important;
				height: 36px !important;
				min-width: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				place-items: center !important;
				transform: none !important;
				font-size: 0 !important;
				line-height: 1 !important;
				color: #173f32 !important;
				background: transparent !important;
				z-index: 2 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty::after {
				position: static !important;
				inset: auto !important;
				display: block !important;
				width: auto !important;
				height: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: none !important;
				transform: none !important;
				font: 600 18px/1 system-ui,sans-serif !important;
				color: #173f32 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:first-child::before { content: "−" !important; }
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:first-child::after { content: none !important; }
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:last-child::before { content: "+" !important; }
			body.elmercado-child-theme #shop-cart-sidebar .quantity > .mini-cart-product-qty:last-child::after { content: none !important; }
			body.elmercado-child-theme #shop-cart-sidebar .quantity input.qty {
				position: relative !important;
				grid-column: 2 !important;
				width: 46px !important;
				height: 36px !important;
				min-height: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-right: 1px solid rgba(23,63,50,.12) !important;
				border-left: 1px solid rgba(23,63,50,.12) !important;
				border-radius: 0 !important;
				line-height: 36px !important;
				text-align: center !important;
				font-weight: 700 !important;
				color: #173f32 !important;
				background: #fff !important;
				transform: none !important;
				z-index: 3 !important;
				appearance: textfield !important;
			}

			/* Header cart badge attached closely to the icon. */
			body.elmercado-child-theme .site-tools .shopping-cart,
			body.elmercado-child-theme .site-tools .shopping-bag-button {
				position: relative !important;
			}
			body.elmercado-child-theme .site-tools .shopping-cart .shop-cart-count,
			body.elmercado-child-theme .site-tools .shopping-cart .cart-count,
			body.elmercado-child-theme .site-tools .shopping-bag-button .shop-cart-count,
			body.elmercado-child-theme .site-tools .shopping-bag-button .cart-count {
				position: absolute !important;
				top: 1px !important;
				right: 1px !important;
				margin: 0 !important;
				transform: translate(28%,-28%) !important;
			}

			@media (max-width: 767px) {
				body.wcfmmp-store-page #wcfmmp-store .tab_links { flex-wrap: wrap !important; }
				body.wcfmmp-store-page #wcfmmp-store .tab_links_area { margin-bottom: 1.1rem !important; }
				body.wcfmmp-store-page #wcfmmp-store .woostify-sorting { margin-top: .75rem !important; margin-bottom: 1.35rem !important; }
				body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper { padding: .2rem !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
