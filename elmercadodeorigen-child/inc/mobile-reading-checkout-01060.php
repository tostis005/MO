<?php
/**
 * Lectura móvil: descripción de producto desplegable.
 *
 * El checkout se deja en manos del flujo AJAX nativo de WooCommerce para que
 * los métodos de pago permanezcan visibles mientras se actualiza el pedido.
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
		<style id="elmercado-mobile-reading-checkout-01060">
			body.elmercado-child-theme.single-product .emo-product-description-toggle {
				display: none;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme.single-product .woocommerce-Tabs-panel--description.emo-description-collapsible {
					padding-bottom: 14px !important;
				}

				body.elmercado-child-theme.single-product .emo-product-description-content {
					position: relative;
					transition: max-height 240ms ease;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible:not(.is-expanded) .emo-product-description-content {
					max-height: 720px;
					overflow: hidden;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible:not(.is-expanded) .emo-product-description-content::after {
					position: absolute;
					right: 0;
					bottom: 0;
					left: 0;
					height: 110px;
					background: linear-gradient(to bottom, rgba(255,255,255,0), #fff 82%);
					content: "";
					pointer-events: none;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible.is-expanded .emo-product-description-content {
					max-height: none;
					overflow: visible;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible .emo-product-description-toggle {
					display: flex;
					width: calc(100% - 24px);
					min-height: 44px;
					align-items: center;
					justify-content: center;
					gap: 8px;
					margin: 12px auto 0;
					padding: 10px 14px;
					border: 1px solid rgba(23,63,50,.16);
					border-radius: 999px;
					background: #f6f2e9;
					color: #173f32;
					font-size: 12px;
					font-weight: 850;
					line-height: 1.2;
					cursor: pointer;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible .emo-product-description-toggle::after {
					content: "↓";
					font-size: 14px;
					line-height: 1;
				}

				body.elmercado-child-theme.single-product .emo-description-collapsible.is-expanded .emo-product-description-toggle::after {
					content: "↑";
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
		<script id="elmercado-mobile-reading-checkout-js-01060">
		(() => {
			'use strict';

			const setupDescription = () => {
				if (!document.body.classList.contains('single-product')) return;
				const panel = document.querySelector('.woocommerce-Tabs-panel--description,#tab-description');
				if (!panel || panel.dataset.emoCollapsible === '1') return;
				panel.dataset.emoCollapsible = '1';
				if (!panel.id) panel.id = 'emo-product-description';

				const inner = document.createElement('div');
				inner.className = 'emo-product-description-content';
				while (panel.firstChild) inner.append(panel.firstChild);
				panel.append(inner);

				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'emo-product-description-toggle';
				button.setAttribute('aria-controls', panel.id);
				button.setAttribute('aria-expanded', 'false');
				button.textContent = 'Leer descripción completa';
				panel.append(button);

				const sync = () => {
					const mobile = matchMedia('(max-width: 767px)').matches;
					const long = inner.scrollHeight > 850;
					panel.classList.toggle('emo-description-collapsible', mobile && long);
					if (!mobile || !long) {
						panel.classList.remove('is-expanded');
						button.setAttribute('aria-expanded', 'false');
						button.textContent = 'Leer descripción completa';
					}
				};

				button.addEventListener('click', () => {
					const expanded = panel.classList.toggle('is-expanded');
					button.setAttribute('aria-expanded', String(expanded));
					button.textContent = expanded ? 'Mostrar menos' : 'Leer descripción completa';
					if (!expanded) {
						const top = panel.getBoundingClientRect().top + scrollY - 110;
						scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
					}
				});

				const mobileQuery = matchMedia('(max-width: 767px)');
				if (mobileQuery.addEventListener) mobileQuery.addEventListener('change', sync);
				document.querySelectorAll('.wc-tabs a,[role="tab"]').forEach((tab) => tab.addEventListener('click', () => setTimeout(sync, 80)));
				setTimeout(sync, 80);
				setTimeout(sync, 500);
			};

			document.addEventListener('DOMContentLoaded', setupDescription);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
