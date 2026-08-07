<?php
/**
 * Capa final de interacción y estabilidad 0.10.51.
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
		<style id="elmercado-interaction-layer-01051">
			/* El gutter del catálogo usa exactamente la misma referencia que el resto del sitio. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content > .woostify-container,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .site-content > .woostify-container {
				box-sizing: border-box !important;
				width: min(calc(100% - (2 * var(--emo-page-gutter))), var(--emo-page-max, 1180px)) !important;
				max-width: var(--emo-page-max, 1180px) !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
			}

			/* El sticky header no puede volver a inyectar una compensación vertical en cabeceras interiores. */
			body.elmercado-child-theme.elmercado-contact-page .site-content,
			body.elmercado-child-theme.elmercado-editorial-content .site-content,
			body.elmercado-child-theme.elmercado-compact-producers .site-content {
				padding-top: 0 !important;
				margin-top: 0 !important;
				transform: none !important;
				translate: none !important;
				transition: none !important;
			}
			body.elmercado-child-theme.elmercado-contact-page :is(.emo-contact-layout,.emo-contact-aside),
			body.elmercado-child-theme.elmercado-editorial-content :is(.emo-journal-hero,.emo-journal-hero__inner),
			body.elmercado-child-theme.elmercado-compact-producers .emo-producers-intro {
				transform: none !important;
				translate: none !important;
				transition: none !important;
				animation: none !important;
			}

			/* El trigger de filtros debe formar parte del hit-test real, no sólo ser visible. */
			@media (max-width: 1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #primary,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .content-area,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .site-main {
					position: relative !important;
					isolation: isolate !important;
				}
				body.elmercado-child-theme #emo-premium-filter-toggle {
					display: flex !important;
					position: relative !important;
					z-index: 80 !important;
					isolation: isolate !important;
					pointer-events: auto !important;
					touch-action: manipulation !important;
					user-select: none !important;
					-webkit-user-select: none !important;
				}
				body.elmercado-child-theme #emo-premium-filter-toggle,
				body.elmercado-child-theme #emo-premium-filter-toggle * {
					pointer-events: auto !important;
				}
				body.elmercado-child-theme #emo-premium-filter-toggle::before,
				body.elmercado-child-theme #emo-premium-filter-toggle::after {
					pointer-events: none !important;
				}
				body.elmercado-child-theme #emo-premium-filter-toggle:focus-visible {
					outline: 3px solid rgba(201,109,69,.42) !important;
					outline-offset: 3px !important;
				}
			}

			/* Targets táctiles mínimos para controles principales. */
			@media (max-width: 767px) {
				body.elmercado-child-theme :is(
					#emo-premium-filter-toggle,
					.emo-mobile-filter-close,
					.elmercado-mobile-menu-close,
					button,
					.button
				) {
					min-height: 44px;
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
		<script id="elmercado-interaction-layer-js-01051">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;
			const compact = () => matchMedia('(max-width:1100px)').matches;
			const getToggle = () => document.getElementById('emo-premium-filter-toggle');

			const reinforce = () => {
				if (!compact()) return;
				const toggle = getToggle();
				if (!toggle) return;
				toggle.style.setProperty('position','relative','important');
				toggle.style.setProperty('z-index','80','important');
				toggle.style.setProperty('pointer-events','auto','important');
				toggle.style.setProperty('isolation','isolate','important');
			};

			document.addEventListener('keydown', (event) => {
				const toggle = getToggle();
				if (!toggle || document.activeElement !== toggle) return;
				if (event.key !== 'Enter' && event.key !== ' ') return;
				event.preventDefault();
				toggle.click();
			}, true);

			[0, 50, 150, 350, 700, 1200].forEach((delay) => setTimeout(reinforce, delay));
			window.addEventListener('resize', () => requestAnimationFrame(reinforce), { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
