<?php
/**
 * Última capa de control visual para corregir defectos pequeños detectados en
 * las capturas reales de desarrollo.
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
		<style id="elmercado-premium-qa">
			/* Minicart: el valor debe quedar ópticamente centrado en todos los navegadores. */
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity {
				position: relative !important;
				display: grid !important;
				grid-template-columns: 34px 44px 34px !important;
				align-items: center !important;
				justify-items: center !important;
				height: 36px !important;
				min-height: 36px !important;
				line-height: 1 !important;
			}

			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity input.qty,
			body.elmercado-child-theme #shop-cart-sidebar input.input-text.qty {
				position: static !important;
				display: block !important;
				box-sizing: border-box !important;
				width: 44px !important;
				height: 34px !important;
				min-width: 44px !important;
				min-height: 34px !important;
				margin: 0 !important;
				padding: 0 0 1px !important;
				border-radius: 0 !important;
				font-family: inherit !important;
				font-size: 0.92rem !important;
				font-weight: 800 !important;
				font-variant-numeric: tabular-nums;
				line-height: 33px !important;
				text-align: center !important;
				text-indent: 0 !important;
				vertical-align: middle !important;
				transform: none !important;
				appearance: textfield !important;
				-webkit-appearance: none !important;
			}

			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty {
				display: grid !important;
				width: 34px !important;
				height: 34px !important;
				place-items: center !important;
				line-height: 1 !important;
				transform: none !important;
			}

			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty .woostify-svg-icon,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty svg {
				display: block !important;
				margin: 0 !important;
			}

			/* Catálogo: tarjetas con altura y jerarquía consistentes. */
			body.elmercado-child-theme ul.products li.product {
				display: flex !important;
				min-width: 0 !important;
				flex-direction: column !important;
			}

			body.elmercado-child-theme ul.products li.product .product-loop-content,
			body.elmercado-child-theme ul.products li.product .product-content,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link {
				min-width: 0 !important;
			}

			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__title,
			body.elmercado-child-theme ul.products li.product h2,
			body.elmercado-child-theme ul.products li.product h3 {
				display: -webkit-box !important;
				min-height: 2.6em !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical;
				-webkit-line-clamp: 2;
			}

			body.elmercado-child-theme ul.products li.product .price {
				margin-top: auto !important;
				font-variant-numeric: tabular-nums;
			}

			/* Blog: respaldo visible aunque el contenedor incluya un enlace vacío. */
			body.elmercado-child-theme .emo-article-card:not(:has(img)) .emo-article-card__media,
			body.elmercado-child-theme .emo-article-card:not(:has(img)) .emo-article-card__image,
			body.elmercado-child-theme .emo-article-card:not(:has(img)) > a:first-child {
				position: relative !important;
				display: flex !important;
				min-height: 250px !important;
				align-items: flex-end !important;
				padding: 1.6rem !important;
				background: linear-gradient(135deg, #173f32 0%, #0d2b22 72%, #21483a 100%) !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme .emo-article-card:not(:has(img)) .emo-article-card__media::before,
			body.elmercado-child-theme .emo-article-card:not(:has(img)) .emo-article-card__image::before,
			body.elmercado-child-theme .emo-article-card:not(:has(img)) > a:first-child::before {
				content: "El cuaderno de origen" !important;
				position: relative;
				z-index: 1;
				max-width: 11ch;
				color: #fffdf8;
				font-family: Georgia, "Times New Roman", serif;
				font-size: clamp(1.45rem, 3vw, 2.1rem);
				font-weight: 700;
				letter-spacing: -0.035em;
				line-height: 1.02;
			}

			/* Controles y formularios: evita saltos de altura entre plugins. */
			body.elmercado-child-theme select,
			body.elmercado-child-theme input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
			body.elmercado-child-theme button,
			body.elmercado-child-theme .button {
				box-sizing: border-box !important;
			}

			body.elmercado-child-theme .woocommerce-result-count,
			body.elmercado-child-theme .woocommerce-ordering {
				align-self: center !important;
			}

			/* El pie no debe crecer por márgenes internos heredados. */
			body.elmercado-child-theme .site-footer .widget:last-child,
			body.elmercado-child-theme .site-footer p:last-child,
			body.elmercado-child-theme .site-footer ul:last-child {
				margin-bottom: 0 !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-infor {
					align-items: center !important;
				}

				body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity {
					flex: 0 0 auto !important;
				}

				body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__title,
				body.elmercado-child-theme ul.products li.product h2,
				body.elmercado-child-theme ul.products li.product h3 {
					min-height: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
