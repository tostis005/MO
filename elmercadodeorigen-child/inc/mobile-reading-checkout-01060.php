<?php
/**
 * Último pulido móvil: checkout en carga y descripción de producto desplegable.
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
			/* Chrome actual permite reaccionar al overlay real de WooCommerce sin
			 * depender del momento exacto en que se ejecuta el controlador JS. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review:has(.blockUI.blockOverlay),
			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading {
				position: relative !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: 210px !important;
				padding: 16px 18px 18px !important;
				overflow: hidden !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review:has(.blockUI.blockOverlay) > :not(.blockUI),
			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading > :not(.blockUI):not(.emo-checkout-loading-note) {
				visibility: hidden !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review:has(.blockUI.blockOverlay)::after {
				display: block;
				margin: 0;
				padding: 16px;
				border: 1px solid rgba(255,255,255,.14);
				border-radius: 14px;
				background: rgba(255,255,255,.055);
				color: #fffdf8;
				content: "Estamos actualizando el resumen y las opciones de pago con tus datos.";
				font-size: 13px;
				font-weight: 650;
				line-height: 1.55;
				visibility: visible;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review:has(.blockUI.blockOverlay) > .blockUI.blockOverlay,
			html body.elmercado-child-theme.woocommerce-checkout #order_review.emo-order-review-loading > .blockUI.blockOverlay {
				background: transparent !important;
				opacity: 0 !important;
			}

			/* Descripciones extensas: todo el contenido sigue en el DOM; en móvil
			 * sólo reducimos el recorrido inicial y dejamos una acción explícita. */
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

			const visible = (node) => {
				if (!node) return false;
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && rect.width > 0 && rect.height > 0;
			};

			const syncCheckout = () => {
				if (!document.body.classList.contains('woocommerce-checkout')) return;
				const review = document.querySelector('#order_review');
				if (!review) return;
				const overlay = [...review.querySelectorAll('.blockUI.blockOverlay')].some(visible);
				const placeOrder = review.querySelector('#place_order');
				const suspiciouslyTall = review.getBoundingClientRect().height > 720 && !visible(placeOrder);
				review.classList.toggle('emo-order-review-loading', overlay || suspiciouslyTall);
			};

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

				window.addEventListener('resize', () => requestAnimationFrame(sync), { passive: true });
				document.querySelectorAll('.wc-tabs a,[role="tab"]').forEach((tab) => tab.addEventListener('click', () => setTimeout(sync, 80)));
				setTimeout(sync, 80);
				setTimeout(sync, 500);
			};

			document.addEventListener('DOMContentLoaded', () => {
				syncCheckout();
				setupDescription();
				[120, 400, 900, 1600, 2800].forEach((delay) => setTimeout(syncCheckout, delay));
				const review = document.querySelector('#order_review');
				if (review) new MutationObserver(() => requestAnimationFrame(syncCheckout)).observe(review, { childList: true, subtree: true, attributes: true, attributeFilter: ['style','class'] });
				if (window.jQuery) jQuery(document.body).on('update_checkout updated_checkout checkout_error', () => requestAnimationFrame(syncCheckout));
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
