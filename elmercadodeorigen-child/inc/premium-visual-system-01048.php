<?php
/**
 * Sistema visual final: gutters, home, carouseles y estabilidad de cabeceras 0.10.49.
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
		<style id="elmercado-premium-visual-system-01049">
			body.elmercado-child-theme {
				--emo-page-gutter: clamp(16px, 2.5vw, 32px);
				--emo-page-max: 1180px;
			}

			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) #content > .woostify-container,
			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) .site-content > .woostify-container,
			body.woocommerce-shop.elmercado-child-theme #content > .woostify-container,
			body.tax-product_cat.elmercado-child-theme #content > .woostify-container,
			body.tax-product_tag.elmercado-child-theme #content > .woostify-container {
				box-sizing: border-box !important;
				width: min(calc(100% - (2 * var(--emo-page-gutter))), var(--emo-page-max)) !important;
				max-width: var(--emo-page-max) !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
			}

			body.wcfmmp-store-page.elmercado-child-theme #content > .woostify-container,
			body.wcfmmp-store-page.elmercado-child-theme .site-content > .woostify-container {
				box-sizing: border-box !important;
				width: min(calc(100% - (2 * var(--emo-page-gutter))), var(--emo-page-max)) !important;
				max-width: var(--emo-page-max) !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
			}
			body.wcfmmp-store-page.elmercado-child-theme #wcfmmp-store {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
			}

			body.elmercado-child-theme :is(
				.emo-journal-hero,
				.emo-journal-hero__inner,
				.emo-producers-intro,
				.emo-contact-layout,
				.emo-contact-aside
			) {
				position: relative !important;
				top: auto !important;
				bottom: auto !important;
				transform: none !important;
				translate: none !important;
				transition: none !important;
				animation: none !important;
			}
			body.elmercado-child-theme :is(.elmercado-compact-producers,.elmercado-contact-page,.elmercado-editorial-content) .site-content {
				transform: none !important;
				translate: none !important;
				transition: none !important;
			}

			body.home.elmercado-child-theme .emo-story__panel,
			body.home.elmercado-child-theme .emo-story__panel :is(h2,h3,strong,span,a),
			body.home.elmercado-child-theme .emo-story__panel .emo-kicker,
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				color: #fffdf8 !important;
			}
			body.home.elmercado-child-theme .emo-story__panel p {
				color: rgba(255,253,248,.86) !important;
			}
			body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
				text-decoration-color: rgba(255,253,248,.42) !important;
			}

			body.home.elmercado-child-theme .emo-featured-products :is(
				.slick-arrow,.swiper-button-prev,.swiper-button-next,.owl-nav,.tns-controls,
				.wc-block-components-product-carousel__button,.products-carousel-nav
			) {
				display: none !important;
			}

			@media (max-width: 1100px) {
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) {
					display: block !important;
					position: fixed !important;
					inset: 0 !important;
					width: 100vw !important;
					height: 100dvh !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
					z-index: 10020 !important;
					background: rgba(12,31,25,.38) !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-panel {
					display: flex !important;
					flex-direction: column !important;
					position: absolute !important;
					top: 0 !important;
					right: 0 !important;
					bottom: 0 !important;
					width: min(390px, calc(100vw - 32px)) !important;
					height: 100% !important;
					padding: 18px !important;
					background: #fffdf8 !important;
					box-shadow: -18px 0 50px rgba(12,31,25,.14) !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) .emo-mobile-filter-content {
					display: block !important;
					flex: 1 1 auto !important;
					overflow-y: auto !important;
					visibility: visible !important;
					opacity: 1 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell:not([hidden]) :is(.widget-area,.widget) {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme { --emo-page-gutter: 16px; }
				body.home.elmercado-child-theme .emo-featured-products .woocommerce {
					overflow: visible !important;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products {
					display: flex !important;
					flex-wrap: nowrap !important;
					gap: 14px !important;
					width: 100% !important;
					margin: 0 !important;
					padding: 2px 0 10px !important;
					overflow-x: auto !important;
					overflow-y: visible !important;
					scroll-snap-type: x proximity;
					scroll-padding-inline: 0;
					-webkit-overflow-scrolling: touch;
					scrollbar-width: none;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products::-webkit-scrollbar { display: none; }
				body.home.elmercado-child-theme .emo-featured-products ul.products > li.product {
					box-sizing: border-box !important;
					flex: 0 0 82% !important;
					width: 82% !important;
					max-width: 82% !important;
					margin: 0 !important;
					scroll-snap-align: start;
				}
				body.home.elmercado-child-theme .emo-featured-products ul.products > li.product:last-child {
					margin-right: 8px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
