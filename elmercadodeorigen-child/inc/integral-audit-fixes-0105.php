<?php
/**
 * Correcciones verificadas tras la auditoría integral 0.10.5.
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
		<style id="elmercado-integral-audit-fixes-0105">
			/* El número del carrito sólo existe visualmente cuando es mayor que cero. */
			body.elmercado-child-theme .elmercado-cart-direct-count[data-empty="true"],
			body.elmercado-child-theme .site-header .elmercado-cart-count-empty {
				display: none !important;
			}

			/* El enlace que contiene el título, no sólo un h2 opcional, ocupa dos líneas completas. */
			body.elmercado-child-theme ul.products li.product > a.woocommerce-loop-product__link:not(:has(img)),
			body.elmercado-child-theme ul.products li.product .elmercado-product-title-link,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__title {
				display: -webkit-box !important;
				box-sizing: border-box !important;
				height: 3em !important;
				min-height: 3em !important;
				max-height: 3em !important;
				margin: 0 0 10px !important;
				padding: 0 !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-clamp: 2 !important;
				line-height: 1.5 !important;
				white-space: normal !important;
				text-overflow: ellipsis !important;
			}

			/* Al retirar productor, resultado y ordenación ocupan los extremos del toolbar. */
			body.elmercado-child-theme .elmercado-vendor-filter-hidden {
				display: none !important;
			}

			body.elmercado-child-theme :is(.woostify-sorting,.wcfmmp-store-content .woostify-sorting,.wcfm_store_content .woostify-sorting) {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 18px !important;
			}

			body.elmercado-child-theme :is(.woocommerce-result-count,.woostify-toolbar-left),
			body.elmercado-child-theme .woocommerce-ordering {
				margin: 0 !important;
			}

			body.elmercado-child-theme .woocommerce-ordering select {
				border-top: 1px solid rgba(23,63,50,.22) !important;
				border-bottom: 1px solid rgba(23,63,50,.22) !important;
			}

			/* Las flechas pertenecen al área de fichas, no al encabezado de la sección. */
			@media (max-width: 991px) {
				body.elmercado-premium-home .emo-featured-products .elmercado-carousel-stage {
					position: relative !important;
				}

				body.elmercado-premium-home .emo-featured-products .elmercado-carousel-stage > .emo-carousel-controls {
					position: absolute !important;
					inset: 0 !important;
					display: block !important;
					pointer-events: none !important;
					z-index: 12 !important;
				}

				body.elmercado-premium-home .emo-featured-products .elmercado-carousel-stage .emo-carousel-control {
					top: 50% !important;
					width: 28px !important;
					height: 42px !important;
					min-width: 28px !important;
					background: rgba(255,255,255,.82) !important;
					box-shadow: 0 3px 12px rgba(23,63,50,.12) !important;
					opacity: .78 !important;
				}
			}

			/* Aire equivalente al resto de pestañas/secciones de la tienda del productor. */
			body.elmercado-child-theme :is(.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab) + *,
			body.elmercado-child-theme :is(.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab) ~ :is(.wcfmmp-store-content,.wcfm_store_content) {
				margin-top: 24px !important;
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
		<script id="elmercado-integral-audit-fixes-0105-script">
		(() => {
			'use strict';

			const parseCount = (value) => {
				const match = String(value || '').match(/\d+/);
				return match ? Number.parseInt(match[0], 10) : 0;
			};

			const normalizeCartCounters = () => {
				document.querySelectorAll('.site-header .shopping-bag-button, .site-header .shopping-cart').forEach((button) => {
					button.querySelectorAll(':scope > .elmercado-cart-direct-count').forEach((node) => {
						const count = parseCount(node.textContent);
						node.dataset.empty = count > 0 ? 'false' : 'true';
						node.setAttribute('aria-hidden', count > 0 ? 'false' : 'true');
					});

					[...button.childNodes].forEach((node) => {
						if (node.nodeType !== Node.TEXT_NODE || !/^\s*\d+\s*$/.test(node.textContent || '')) return;
						const badge = document.createElement('span');
						badge.className = 'elmercado-cart-direct-count';
						badge.textContent = String(parseCount(node.textContent));
						badge.dataset.empty = parseCount(node.textContent) > 0 ? 'false' : 'true';
						badge.setAttribute('aria-hidden', badge.dataset.empty === 'true' ? 'true' : 'false');
						node.replaceWith(badge);
					});
				});
			};

			const identifyProductTitles = () => {
				document.querySelectorAll('ul.products li.product a.woocommerce-loop-product__link').forEach((link) => {
					if (!link.querySelector('img') && (link.textContent || '').trim()) {
						link.classList.add('elmercado-product-title-link');
					}
				});
			};

			const hideProducerControl = () => {
				document.querySelectorAll('select').forEach((select) => {
					const text = [...select.options].map((option) => option.textContent || '').join(' ').toLowerCase();
					if (!/(todos los productores|todos los vendedores)/.test(text)) return;
					const field = select.closest('.wcfmmp-product-filter-wrap,.product-filter,.filter-item,.form-row,.woostify-toolbar-left > *,label,div') || select;
					field.classList.add('elmercado-vendor-filter-hidden');
					field.setAttribute('aria-hidden', 'true');
				});
			};

			const normalizeResultText = () => {
				document.querySelectorAll('.woocommerce-result-count').forEach((node) => {
					const value = (node.textContent || '')
						.replace(/(\d)\s*de\s*(\d)/gi, '$1 de $2')
						.replace(/(\d)(resultados?)/gi, '$1 $2')
						.replace(/\s+/g, ' ')
						.trim();
					if (value) node.textContent = value;
				});
			};

			const positionCarouselControls = () => {
				const section = document.querySelector('.emo-featured-products');
				const track = section?.querySelector('ul.products');
				const controls = section?.querySelector('.emo-carousel-controls');
				const stage = track?.parentElement;
				if (!stage || !controls) return;
				stage.classList.add('elmercado-carousel-stage');
				if (controls.parentElement !== stage) stage.appendChild(controls);
			};

			const refresh = () => {
				normalizeCartCounters();
				identifyProductTitles();
				hideProducerControl();
				normalizeResultText();
				positionCarouselControls();
			};

			document.addEventListener('DOMContentLoaded', () => {
				refresh();
				setTimeout(refresh, 300);
				setTimeout(refresh, 1200);
				new MutationObserver(() => requestAnimationFrame(refresh)).observe(document.body, {
					subtree: true,
					childList: true,
					characterData: true
				});
				if (window.jQuery) {
					window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', refresh);
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
