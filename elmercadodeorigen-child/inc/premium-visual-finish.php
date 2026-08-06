<?php
/**
 * Capa final y segura de acabado visual premium.
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
		<style id="elmercado-premium-visual-finish">
			/* Cabecera: controles homogéneos, circulares, sin subrayado. */
			body.elmercado-child-theme .site-header .site-tools {
				display: flex !important;
				align-items: center !important;
				gap: 0.55rem !important;
			}
			body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
				display: inline-grid !important;
				width: 44px !important;
				height: 44px !important;
				min-width: 44px !important;
				place-items: center !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 50% !important;
				background: transparent !important;
				box-shadow: none !important;
				color: #173f32 !important;
				text-decoration: none !important;
				transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease, transform 160ms ease !important;
			}
			body.elmercado-child-theme .site-header .site-tools > .header-search-icon::after,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon::after,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon::after {
				display: none !important;
				content: none !important;
			}
			body.elmercado-child-theme .site-header .site-tools > .header-search-icon:hover,
			body.elmercado-child-theme .site-header .site-tools > .header-search-icon:focus-visible,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon:hover,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon:focus-visible,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:hover,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:focus-visible {
				background: #eef6f1 !important;
				box-shadow: 0 7px 18px rgba(23, 63, 50, 0.12) !important;
				color: #1f674b !important;
				transform: translateY(-1px) !important;
			}
			body.elmercado-child-theme .site-header .site-tools > .header-search-icon:focus-visible,
			body.elmercado-child-theme .site-header .site-tools > a.tools-icon:focus-visible,
			body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:focus-visible {
				outline: 2px solid #2f7d5d !important;
				outline-offset: 2px !important;
			}

			/* Panel de búsqueda coherente con el resto de la interfaz. */
			body.elmercado-child-theme .header-search-form,
			body.elmercado-child-theme .search-form-wrapper,
			body.elmercado-child-theme .site-search,
			body.elmercado-child-theme .search-popup,
			body.elmercado-child-theme .search-overlay {
				background: rgba(255, 253, 248, 0.98) !important;
				backdrop-filter: blur(14px);
			}
			body.elmercado-child-theme .header-search-form form,
			body.elmercado-child-theme .search-form-wrapper form,
			body.elmercado-child-theme .site-search form,
			body.elmercado-child-theme .search-popup form,
			body.elmercado-child-theme .search-overlay form {
				width: min(680px, calc(100vw - 32px)) !important;
				margin-inline: auto !important;
				padding: clamp(1rem, 3vw, 1.5rem) !important;
				border: 1px solid rgba(23, 63, 50, 0.12) !important;
				border-radius: 22px !important;
				background: #fff !important;
				box-shadow: 0 24px 60px rgba(13, 33, 27, 0.16) !important;
			}
			body.elmercado-child-theme .header-search-form input[type="search"],
			body.elmercado-child-theme .search-form-wrapper input[type="search"],
			body.elmercado-child-theme .site-search input[type="search"],
			body.elmercado-child-theme .search-popup input[type="search"],
			body.elmercado-child-theme .search-overlay input[type="search"] {
				height: 50px !important;
				border: 1px solid #d8e2dc !important;
				border-radius: 999px !important;
				background: #fffdf8 !important;
				color: #173f32 !important;
			}

			/* Contraste seguro en superficies oscuras de home, carrito y checkout. */
			body.elmercado-child-theme .emo-dark,
			body.elmercado-child-theme [class*="dark-section"],
			body.elmercado-child-theme .emo-home-proof,
			body.elmercado-child-theme .emo-home-story,
			body.elmercado-child-theme .cart_totals,
			body.elmercado-child-theme .woocommerce-checkout-review-order,
			body.elmercado-child-theme .woocommerce-checkout-payment {
				color: #fffdf8;
			}
			body.elmercado-child-theme .emo-dark h1,
			body.elmercado-child-theme .emo-dark h2,
			body.elmercado-child-theme .emo-dark h3,
			body.elmercado-child-theme [class*="dark-section"] h1,
			body.elmercado-child-theme [class*="dark-section"] h2,
			body.elmercado-child-theme [class*="dark-section"] h3,
			body.elmercado-child-theme .emo-home-proof h2,
			body.elmercado-child-theme .emo-home-proof h3,
			body.elmercado-child-theme .emo-home-story h2,
			body.elmercado-child-theme .emo-home-story h3 {
				color: #fffdf8 !important;
			}
			body.elmercado-child-theme .emo-dark p,
			body.elmercado-child-theme [class*="dark-section"] p,
			body.elmercado-child-theme .emo-home-proof p,
			body.elmercado-child-theme .emo-home-story p {
				color: rgba(255, 253, 248, 0.84) !important;
			}

			/* Minicart: cantidad y totales alineados y centrados. */
			body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) auto !important;
				column-gap: 0.9rem !important;
				align-items: start !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item > a.emo-mini-cart-product-link,
			body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item > a:not(.remove):not(.remove_from_cart_button) {
				grid-column: 1 / -1 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity {
				display: inline-grid !important;
				grid-template-columns: 34px 44px 34px !important;
				align-items: center !important;
				width: 112px !important;
				height: 36px !important;
				min-height: 36px !important;
				margin: 0 !important;
				border: 1px solid #d8e2dc !important;
				border-radius: 999px !important;
				background: #fff !important;
				overflow: hidden !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity .qty,
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				display: block !important;
				width: 44px !important;
				height: 34px !important;
				min-height: 34px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: #fff !important;
				color: #173f32 !important;
				font-size: 0.95rem !important;
				font-weight: 800 !important;
				line-height: 34px !important;
				text-align: center !important;
				vertical-align: middle !important;
				transform: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty {
				display: grid !important;
				width: 34px !important;
				height: 34px !important;
				place-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item .amount {
				align-self: center !important;
				margin: 0 !important;
				color: #173f32 !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
				text-align: right !important;
			}

			/* Categorías: no mostrar descripciones extensas encima del catálogo. */
			body.elmercado-child-theme.tax-product_cat .term-description,
			body.elmercado-child-theme.tax-product_cat .woocommerce-products-header__description,
			body.elmercado-child-theme.tax-product_cat .page-description {
				display: none !important;
			}

			/* Productores: eliminar el gran vacío entre cabecera y directorio. */
			body.elmercado-child-theme.page-id-3697 .site-content,
			body.elmercado-child-theme .emo-producers-page {
				padding-top: clamp(1.25rem, 3vw, 2.5rem) !important;
			}
			body.elmercado-child-theme #wcfmmp-stores-wrap {
				margin-top: clamp(1rem, 2vw, 1.75rem) !important;
			}
			body.elmercado-child-theme #wcfmmp-stores-wrap .wcfmmp-store-lists-sorting,
			body.elmercado-child-theme #wcfmmp-stores-wrap .wcfmmp-store-search-form {
				margin-bottom: 0.85rem !important;
			}

			/* Blog: fallback visual para entradas sin imagen destacada. */
			body.elmercado-child-theme .emo-article-card__placeholder,
			body.elmercado-child-theme .post-card .post-thumbnail:empty,
			body.elmercado-child-theme article .post-thumbnail:empty {
				min-height: 240px !important;
				background: linear-gradient(135deg, #173f32 0%, #0d2b22 75%, #275545 100%) !important;
				border-radius: 18px 18px 0 0 !important;
			}

			/* Pie: compacto pero respirado. */
			body.elmercado-child-theme .site-footer {
				margin-top: clamp(2rem, 4vw, 4rem) !important;
			}
			body.elmercado-child-theme .site-footer .footer-widget-section,
			body.elmercado-child-theme .site-footer .footer-widgets {
				padding-block: clamp(1.75rem, 3vw, 2.75rem) !important;
			}
			body.elmercado-child-theme .site-footer .site-info,
			body.elmercado-child-theme .site-footer .footer-bottom {
				padding-block: 0.85rem !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme .site-header .site-tools { gap: 0.35rem !important; }
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
					width: 42px !important;
					height: 42px !important;
					min-width: 42px !important;
				}
				body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item {
					grid-template-columns: minmax(0, 1fr) !important;
				}
				body.elmercado-child-theme #shop-cart-sidebar .woocommerce-mini-cart-item .amount {
					text-align: left !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
