<?php
/**
 * Acabado final del carrusel de productos y cabeceras interiores 0.10.52.
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
		<style id="elmercado-home-product-card-finish-01052">
			/* Carrusel limpio: ningún control de flechas debe reaparecer por estilos del tema/plugin. */
			body.home.elmercado-child-theme .emo-featured-products :is(
				.slick-arrow,.slick-prev,.slick-next,.swiper-button-prev,.swiper-button-next,
				.owl-prev,.owl-next,.owl-nav,.tns-controls,
				.wc-block-components-product-carousel__button,.products-carousel-nav,
				.carousel-arrow,[class*="carousel-arrow"],[class*="slider-arrow"]
			) {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}

			/* El nombre de producto ocupa siempre exactamente el alto de dos líneas. */
			body.home.elmercado-child-theme .emo-featured-products li.product :is(
				.woocommerce-loop-product__title,.product-title,h2,h3
			) {
				display: -webkit-box !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-clamp: 2 !important;
				overflow: hidden !important;
				text-overflow: ellipsis !important;
				white-space: normal !important;
				line-height: 1.35 !important;
				height: 2.7em !important;
				min-height: 2.7em !important;
				max-height: 2.7em !important;
				margin-bottom: 8px !important;
			}

			/* Blog marca el ritmo de las cabeceras interiores. Productores y Contacto lo replican. */
			body.elmercado-child-theme {
				--emo-inner-top-gap: 20px;
			}
			body.elmercado-child-theme .emo-journal-hero {
				padding-top: var(--emo-inner-top-gap) !important;
			}
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-layout {
				margin-top: var(--emo-inner-top-gap) !important;
			}
			body.elmercado-child-theme .emo-journal-hero__inner,
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-layout {
				width: min(var(--emo-content-max, 1180px), calc(100% - (2 * var(--emo-page-gutter, 16px)))) !important;
				max-width: var(--emo-content-max, 1180px) !important;
				margin-inline: auto !important;
				box-sizing: border-box !important;
			}
			body.elmercado-child-theme .emo-contact-layout {
				margin-inline: auto !important;
			}

			/* Las entradas del blog comparten el mismo ancho útil que la cabecera y el listado. */
			body.elmercado-child-theme.elmercado-editorial-content :is(
				.emo-article-hero__inner,
				.emo-article-main > .emo-shell,
				.emo-related-reading > .emo-shell,
				.single-post .entry-content,
				.single-post article .entry-content
			) {
				width: min(var(--emo-content-max, 1180px), calc(100% - (2 * var(--emo-page-gutter, 16px)))) !important;
				max-width: var(--emo-content-max, 1180px) !important;
				margin-inline: auto !important;
				box-sizing: border-box !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme { --emo-inner-top-gap: 20px; }
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
		<script id="elmercado-home-product-card-finish-js-01052">
		(() => {
			'use strict';
			if (!document.body.classList.contains('home')) return;
			const root = document.querySelector('.emo-featured-products');
			if (!root) return;

			const clean = () => {
				root.querySelectorAll('.slick-arrow,.slick-prev,.slick-next,.swiper-button-prev,.swiper-button-next,.owl-prev,.owl-next,.owl-nav,.tns-controls,.wc-block-components-product-carousel__button,.products-carousel-nav,.carousel-arrow,[class*="carousel-arrow"],[class*="slider-arrow"]').forEach((node) => {
					node.hidden = true;
					node.setAttribute('aria-hidden', 'true');
				});
				root.querySelectorAll('li.product').forEach((card) => {
					card.querySelectorAll('span,small,div,p').forEach((node) => {
						const text = (node.textContent || '').replace(/\s+/g, ' ').trim();
						if (!text || text.length > 80) return;
						if (!/procedencia\s+(visible|clara)|con\s+procedencia/i.test(text)) return;
						const imageArea = node.closest('.product-loop-image-wrapper,.product-image,.product-thumbnail,.product-loop-image,.woocommerce-loop-product__link') || node.parentElement;
						if (imageArea && card.contains(imageArea)) node.remove();
					});
				});
			};

			clean();
			[80, 220, 500, 1000].forEach((delay) => setTimeout(clean, delay));
			new MutationObserver(() => clean()).observe(root, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
