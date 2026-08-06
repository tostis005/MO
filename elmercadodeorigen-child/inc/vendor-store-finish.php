<?php
/**
 * Acabado final de la tienda individual de cada productor.
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
		<style id="elmercado-vendor-store-finish">
			body.elmercado-child-theme #wcfmmp-store {
				width: min(calc(100% - 40px), 1320px) !important;
				max-width: 1320px !important;
				margin: clamp(1.5rem, 4vw, 3.25rem) auto 0 !important;
				padding: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .banner_area,
			body.elmercado-child-theme #wcfmmp-store .store_info_parallal,
			body.elmercado-child-theme #wcfmmp-store .store_info {
				border-radius: 24px !important;
				overflow: hidden !important;
				box-shadow: 0 18px 48px rgba(13, 33, 27, 0.09) !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area {
				margin-top: clamp(1.25rem, 3vw, 2rem) !important;
				padding: clamp(1rem, 2vw, 1.4rem) !important;
				border: 1px solid rgba(23, 63, 50, 0.12) !important;
				border-radius: 20px !important;
				background: #fffdf8 !important;
				box-shadow: 0 12px 34px rgba(13, 33, 27, 0.055) !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links {
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 0.55rem !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li {
				min-width: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li::after {
				display: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li a {
				display: inline-flex !important;
				min-height: 42px !important;
				align-items: center !important;
				justify-content: center !important;
				padding: 0.62rem 1rem !important;
				border: 1px solid rgba(23, 63, 50, 0.14) !important;
				border-radius: 999px !important;
				background: #fff !important;
				color: #173f32 !important;
				font-size: 0.82rem !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
				text-decoration: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li.active a,
			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li a:hover,
			body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li a:focus-visible {
				border-color: #173f32 !important;
				background: #173f32 !important;
				color: #fff !important;
			}

			body.elmercado-child-theme #wcfmmp-store .body_area {
				display: grid !important;
				grid-template-columns: minmax(230px, 280px) minmax(0, 1fr) !important;
				align-items: start !important;
				gap: clamp(1.25rem, 3vw, 2rem) !important;
				margin-top: clamp(1.25rem, 3vw, 2rem) !important;
			}

			body.elmercado-child-theme #wcfmmp-store .left_sidebar {
				grid-column: 1 !important;
				grid-row: 1 !important;
				position: sticky !important;
				top: 112px !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 1.15rem !important;
				border: 1px solid rgba(23, 63, 50, 0.12) !important;
				border-radius: 18px !important;
				background: #fffdf8 !important;
				box-shadow: 0 10px 28px rgba(13, 33, 27, 0.05) !important;
			}

			body.elmercado-child-theme #wcfmmp-store .right_side,
			body.elmercado-child-theme #wcfmmp-store .products-wrapper,
			body.elmercado-child-theme #wcfmmp-store .wcfmmp-store-product {
				grid-column: 2 !important;
				grid-row: 1 !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				float: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .left_sidebar .widget,
			body.elmercado-child-theme #wcfmmp-store .left_sidebar .sidebar_heading {
				margin: 0 0 1rem !important;
				padding: 0 0 1rem !important;
				border-bottom: 1px solid rgba(23, 63, 50, 0.1) !important;
			}

			body.elmercado-child-theme #wcfmmp-store .left_sidebar .widget:last-child {
				margin-bottom: 0 !important;
				padding-bottom: 0 !important;
				border-bottom: 0 !important;
			}

			body.elmercado-child-theme #wcfmmp-store ul.products {
				display: grid !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(1.15rem, 2.3vw, 1.8rem) !important;
				width: 100% !important;
				margin: 0 !important;
			}

			body.elmercado-child-theme #wcfmmp-store ul.products::before,
			body.elmercado-child-theme #wcfmmp-store ul.products::after {
				display: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store ul.products li.product {
				width: auto !important;
				max-width: none !important;
				margin: 0 !important;
				float: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .woocommerce-result-count,
			body.elmercado-child-theme #wcfmmp-store .woocommerce-ordering,
			body.elmercado-child-theme #wcfmmp-store .wcfmmp-product-listing-filter-wrap {
				margin-top: 0 !important;
				margin-bottom: 1rem !important;
			}

			@media (max-width: 1100px) {
				body.elmercado-child-theme #wcfmmp-store ul.products {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 820px) {
				body.elmercado-child-theme #wcfmmp-store {
					width: min(calc(100% - 24px), 1320px) !important;
				}

				body.elmercado-child-theme #wcfmmp-store .body_area {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				body.elmercado-child-theme #wcfmmp-store .left_sidebar,
				body.elmercado-child-theme #wcfmmp-store .right_side,
				body.elmercado-child-theme #wcfmmp-store .products-wrapper,
				body.elmercado-child-theme #wcfmmp-store .wcfmmp-store-product {
					grid-column: 1 !important;
					position: static !important;
				}

				body.elmercado-child-theme #wcfmmp-store .left_sidebar {
					grid-row: 1 !important;
				}

				body.elmercado-child-theme #wcfmmp-store .right_side,
				body.elmercado-child-theme #wcfmmp-store .products-wrapper,
				body.elmercado-child-theme #wcfmmp-store .wcfmmp-store-product {
					grid-row: 2 !important;
				}
			}

			@media (max-width: 600px) {
				body.elmercado-child-theme #wcfmmp-store ul.products {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				body.elmercado-child-theme #wcfmmp-store .tab_area {
					padding: 0.8rem !important;
				}

				body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links {
					display: grid !important;
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}

				body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li a {
					width: 100% !important;
					padding-inline: 0.75rem !important;
				}
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
		<script id="elmercado-vendor-store-order">
		(() => {
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;

			const body = store.querySelector('.body_area');
			const filters = store.querySelector('.left_sidebar');
			const products = store.querySelector('.right_side, .products-wrapper, .wcfmmp-store-product');

			if (body && filters && filters.parentElement === body) {
				body.insertBefore(filters, body.firstElementChild);
			}

			if (body && products && products.parentElement === body && filters) {
				body.insertBefore(products, filters.nextSibling);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
