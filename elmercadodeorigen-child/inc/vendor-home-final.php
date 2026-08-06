<?php
/**
 * Acabado específico para la tienda de productor, minicart y bloque de origen.
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
		<style id="elmercado-vendor-home-final">
			/* La tienda individual debe comenzar por la identidad del productor, no por un título heredado. */
			body.wcfmmp-store-page .page-header,
			body.wcfmmp-store-page .woostify-page-header,
			body.wcfmmp-store-page .entry-header,
			body.wcfmmp-store-page .site-content > .container > .content-top,
			body.wcfmmp-store-page #secondary,
			body.wcfmmp-store-page #wcfmmp-store .left_sidebar {
				display: none !important;
			}

			body.wcfmmp-store-page .site-content,
			body.wcfmmp-store-page .site-main,
			body.wcfmmp-store-page #primary {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
			}

			body.wcfmmp-store-page #wcfmmp-store {
				width: min(calc(100% - 40px), 1320px) !important;
				margin: clamp(1.5rem, 4vw, 3rem) auto 0 !important;
			}

			/* Banner y logo forman una única cabecera editorial, sin franja negra residual. */
			body.wcfmmp-store-page #wcfmmp-store .banner_area {
				position: relative !important;
				height: clamp(240px, 25vw, 330px) !important;
				min-height: 240px !important;
				border-radius: 26px !important;
				background: #173f32 !important;
				overflow: hidden !important;
				box-shadow: 0 20px 55px rgba(13, 33, 27, 0.14) !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_area::after {
				content: "";
				position: absolute;
				inset: 0;
				background: linear-gradient(90deg, rgba(10, 35, 27, 0.72) 0%, rgba(10, 35, 27, 0.25) 55%, rgba(10, 35, 27, 0.38) 100%);
				pointer-events: none;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_img,
			body.wcfmmp-store-page #wcfmmp-store .banner_img img {
				width: 100% !important;
				height: 100% !important;
				min-height: 100% !important;
				object-fit: cover !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_text {
				position: absolute !important;
				z-index: 2 !important;
				top: 50% !important;
				right: clamp(1.5rem, 5vw, 4rem) !important;
				left: clamp(10rem, 20vw, 17rem) !important;
				transform: translateY(-50%) !important;
				text-align: left !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_text h1 {
				max-width: 14ch !important;
				margin: 0 !important;
				color: #fffdf8 !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(2.1rem, 5vw, 4.4rem) !important;
				font-weight: 700 !important;
				letter-spacing: -0.045em !important;
				line-height: 0.96 !important;
				text-transform: none !important;
			}

			body.wcfmmp-store-page #wcfm_store_header {
				position: relative !important;
				z-index: 4 !important;
				width: auto !important;
				min-height: 0 !important;
				margin: clamp(-7.2rem, -8vw, -5.5rem) 0 0 !important;
				padding: 0 clamp(1.25rem, 4vw, 3rem) !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			body.wcfmmp-store-page #wcfm_store_header .header_wrapper,
			body.wcfmmp-store-page #wcfm_store_header .header_area,
			body.wcfmmp-store-page #wcfm_store_header .header_left,
			body.wcfmmp-store-page #wcfm_store_header .logo_area_after {
				min-height: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			body.wcfmmp-store-page #wcfm_store_header .logo_area {
				position: relative !important;
				z-index: 5 !important;
				width: clamp(110px, 11vw, 150px) !important;
				height: clamp(110px, 11vw, 150px) !important;
				margin: 0 !important;
				padding: 8px !important;
				border-radius: 50% !important;
				background: #fffdf8 !important;
				box-shadow: 0 16px 42px rgba(10, 35, 27, 0.28) !important;
				overflow: hidden !important;
			}

			body.wcfmmp-store-page #wcfm_store_header .logo_area img {
				width: 100% !important;
				height: 100% !important;
				border-radius: 50% !important;
				object-fit: contain !important;
			}

			body.wcfmmp-store-page #wcfm_store_header .address,
			body.wcfmmp-store-page #wcfm_store_header .header_right,
			body.wcfmmp-store-page #wcfm_store_header .wcfm_store_enquiry {
				display: none !important;
			}

			/* Sin filtros locales: catálogo amplio, limpio y equivalente a la tienda global. */
			body.wcfmmp-store-page #wcfmmp-store .body_area {
				display: block !important;
				margin-top: clamp(2.5rem, 5vw, 4.5rem) !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .right_side,
			body.wcfmmp-store-page #wcfmmp-store .tab_area,
			body.wcfmmp-store-page #wcfmmp-store .products-wrapper,
			body.wcfmmp-store-page #wcfmmp-store .wcfmmp-store-product {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				float: none !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .tab_area {
				padding: clamp(1.1rem, 2.5vw, 1.75rem) !important;
				border-radius: 22px !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .tab_links_area {
				margin: 0 0 1.25rem !important;
				padding: 0 0 1.15rem !important;
				border-bottom: 1px solid rgba(23, 63, 50, 0.12) !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .tab_links {
				gap: 0.65rem !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
				display: flex !important;
				min-height: 62px !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 1rem !important;
				margin: 0 0 1.4rem !important;
				padding: 0.75rem 0.9rem !important;
				border: 1px solid rgba(23, 63, 50, 0.1) !important;
				border-radius: 16px !important;
				background: #fff !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .woocommerce-result-count,
			body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering {
				margin: 0 !important;
			}

			body.wcfmmp-store-page #wcfmmp-store ul.products {
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(1.2rem, 2.4vw, 2rem) !important;
			}

			/* Metadato de vendedor con respiración y una sola línea legible. */
			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper,
			body.elmercado-child-theme .wcfmmp_sold_by_label,
			body.elmercado-child-theme [class*="sold_by"] {
				line-height: 1.45 !important;
			}

			body.elmercado-child-theme .wcfmmp_sold_by_container,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper {
				display: flex !important;
				align-items: center !important;
				flex-wrap: wrap !important;
				gap: 0.3rem 0.45rem !important;
				margin-top: 0.7rem !important;
			}

			body.elmercado-child-theme .wcfmmp_sold_by_container a,
			body.elmercado-child-theme .wcfmmp_sold_by_wrapper a {
				margin-left: 0.2rem !important;
			}

			/* Minicart: cuadrícula rígida para impedir que valor y botón + se solapen. */
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity,
			body.elmercado-child-theme #shop-cart-sidebar .quantity {
				display: grid !important;
				grid-template-columns: 36px 46px 36px !important;
				width: 118px !important;
				min-width: 118px !important;
				height: 38px !important;
				align-items: center !important;
				justify-items: center !important;
				column-gap: 0 !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .minus,
			body.elmercado-child-theme #shop-cart-sidebar .quantity .plus {
				position: static !important;
				display: grid !important;
				width: 36px !important;
				height: 36px !important;
				min-width: 36px !important;
				place-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1 !important;
				transform: none !important;
			}

			body.elmercado-child-theme #shop-cart-sidebar input.qty,
			body.elmercado-child-theme #shop-cart-sidebar .quantity input.qty {
				position: static !important;
				display: block !important;
				width: 46px !important;
				height: 36px !important;
				min-width: 46px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				font-size: 0.95rem !important;
				font-weight: 800 !important;
				line-height: 36px !important;
				text-align: center !important;
				text-indent: 0 !important;
				transform: none !important;
			}

			/* El texto secundario del bloque oscuro debe conservar contraste AA. */
			body.elmercado-child-theme .emo-origin-distance-card,
			body.elmercado-child-theme .emo-origin-distance-card p,
			body.elmercado-child-theme .emo-origin-distance-card a,
			body.elmercado-child-theme .emo-origin-distance-card .button {
				color: #fffdf8 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-card p {
				opacity: 0.86 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-card a:not(.button) {
				text-decoration-color: #f1cb82 !important;
				text-underline-offset: 0.2em !important;
			}

			@media (max-width: 900px) {
				body.wcfmmp-store-page #wcfmmp-store ul.products {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 640px) {
				body.wcfmmp-store-page #wcfmmp-store {
					width: min(calc(100% - 24px), 1320px) !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .banner_area {
					height: 260px !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .banner_text {
					top: 42% !important;
					right: 1.25rem !important;
					left: 1.25rem !important;
					text-align: center !important;
				}

				body.wcfmmp-store-page #wcfm_store_header {
					display: flex !important;
					justify-content: center !important;
					margin-top: -4.4rem !important;
				}

				body.wcfmmp-store-page #wcfm_store_header .logo_area {
					width: 108px !important;
					height: 108px !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .body_area {
					margin-top: 2.5rem !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .woostify-sorting {
					align-items: stretch !important;
					flex-direction: column !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering,
				body.wcfmmp-store-page #wcfmmp-store .woocommerce-ordering select {
					width: 100% !important;
				}

				body.wcfmmp-store-page #wcfmmp-store ul.products {
					grid-template-columns: minmax(0, 1fr) !important;
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
		<script id="elmercado-origin-distance-class">
		(() => {
			const needle = 'Acortamos la distancia entre quienes producen y quienes quieren elegir mejor';
			const nodes = [...document.querySelectorAll('h1,h2,h3,h4,p')];
			const heading = nodes.find((node) => (node.textContent || '').replace(/\s+/g, ' ').trim().includes(needle));
			if (!heading) return;
			let card = heading;
			while (card.parentElement && card.parentElement !== document.body) {
				card = card.parentElement;
				const style = getComputedStyle(card);
				const rect = card.getBoundingClientRect();
				const background = style.backgroundColor;
				if (rect.width > 240 && rect.height > 220 && background !== 'rgba(0, 0, 0, 0)') break;
			}
			card.classList.add('emo-origin-distance-card');
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
