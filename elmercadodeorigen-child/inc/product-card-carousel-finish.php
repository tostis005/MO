<?php
/**
 * Altura estable de títulos, carrusel de portada y acabado del catálogo.
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
		<style id="elmercado-product-card-carousel-finish">
			/* Dos líneas reales y completas en todos los títulos de tarjeta. */
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__title,
			body.elmercado-child-theme ul.products li.product .product-title,
			body.elmercado-child-theme ul.products li.product h2,
			body.elmercado-child-theme ul.products li.product h3 {
				display: -webkit-box !important;
				box-sizing: border-box !important;
				min-height: 3.2em !important;
				height: 3.2em !important;
				max-height: 3.2em !important;
				margin: 0 0 8px !important;
				padding: 0 !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-clamp: 2 !important;
				line-height: 1.6 !important;
				text-overflow: ellipsis !important;
			}

			/* Jerarquía clara del sidebar de filtros. */
			body.elmercado-child-theme.woocommerce-shop .widget-area .widget,
			body.elmercado-child-theme.tax-product_cat .widget-area .widget {
				margin: 0 0 18px !important;
				padding: 18px !important;
				border: 1px solid rgba(23, 63, 50, 0.12) !important;
				border-radius: 16px !important;
				background: #fff !important;
			}

			body.elmercado-child-theme.woocommerce-shop .widget-area .widget-title,
			body.elmercado-child-theme.woocommerce-shop .widget-area .widgettitle,
			body.elmercado-child-theme.tax-product_cat .widget-area .widget-title,
			body.elmercado-child-theme.tax-product_cat .widget-area .widgettitle {
				display: block !important;
				margin: 0 0 12px !important;
				padding: 0 0 10px !important;
				border-bottom: 1px solid rgba(23, 63, 50, 0.14) !important;
				color: #173f32 !important;
				font-size: 13px !important;
				font-weight: 800 !important;
				letter-spacing: .08em !important;
				line-height: 1.3 !important;
				text-transform: uppercase !important;
			}

			body.elmercado-child-theme.woocommerce-shop .widget-area .widget li,
			body.elmercado-child-theme.tax-product_cat .widget-area .widget li {
				margin: 0 !important;
				padding: 7px 0 !important;
			}

			/* Resultados y ordenación, global y productor, en una sola línea. */
			body.elmercado-child-theme :is(.woostify-sorting,.woocommerce-notices-wrapper + .woostify-sorting,.wcfmmp-store-content .woostify-sorting) {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 16px !important;
				min-height: 50px !important;
			}

			body.elmercado-child-theme :is(.woocommerce-result-count,.woostify-toolbar-left) {
				display: flex !important;
				align-items: center !important;
				min-height: 46px !important;
				margin: 0 !important;
				line-height: 1.4 !important;
			}

			body.elmercado-child-theme .woocommerce-ordering {
				display: flex !important;
				align-items: center !important;
				min-height: 46px !important;
				margin: 0 !important;
			}

			body.elmercado-child-theme .woocommerce-ordering select {
				height: 46px !important;
				min-height: 46px !important;
				padding: 0 42px 0 15px !important;
				border: 1px solid rgba(23, 63, 50, .20) !important;
				border-radius: 12px !important;
				background-color: #fff !important;
				box-shadow: inset 0 1px 0 rgba(255,255,255,.7) !important;
			}

			/* Separación estable entre pestañas del productor y catálogo. */
			body.elmercado-child-theme :is(.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab) {
				margin-bottom: 24px !important;
			}

			body.elmercado-child-theme :is(.wcfmmp-store-content,.wcfm_store_content) > :is(.woostify-sorting,.woocommerce-notices-wrapper,.store-products) {
				margin-top: 0 !important;
			}

			body.elmercado-premium-home .emo-featured-products .emo-carousel-controls {
				display: none;
			}

			@media (max-width: 991px) {
				body.elmercado-premium-home .emo-featured-products .emo-shell {
					position: relative !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-controls {
					position: absolute !important;
					inset: 0 !important;
					display: block !important;
					margin: 0 !important;
					pointer-events: none !important;
					z-index: 8 !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control {
					position: absolute !important;
					top: 50% !important;
					display: grid !important;
					width: 30px !important;
					height: 46px !important;
					min-width: 30px !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: rgba(255,255,255,.76) !important;
					color: #173f32 !important;
					box-shadow: 0 4px 16px rgba(23,63,50,.12) !important;
					backdrop-filter: blur(5px) !important;
					transform: translateY(-50%) !important;
					pointer-events: auto !important;
					cursor: pointer !important;
					opacity: .82 !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control--previous {
					left: 6px !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control--next {
					right: 6px !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control:focus-visible {
					outline: 2px solid #2f7d5d !important;
					outline-offset: 2px !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control[disabled] {
					opacity: .20 !important;
					cursor: default !important;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control svg {
					width: 17px !important;
					height: 17px !important;
					fill: none !important;
					stroke: currentColor !important;
					stroke-linecap: round !important;
					stroke-linejoin: round !important;
					stroke-width: 2 !important;
				}

				body.elmercado-child-theme :is(.woostify-sorting,.wcfmmp-store-content .woostify-sorting) {
					align-items: stretch !important;
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
		<script id="elmercado-storefront-final-ui">
		(() => {
			'use strict';

			const normalizeResults = () => {
				document.querySelectorAll('.woocommerce-result-count').forEach((node) => {
					node.childNodes.forEach((child) => {
						if (child.nodeType === Node.TEXT_NODE) {
							child.textContent = child.textContent
								.replace(/(\d)(de)(\d)/gi, '$1 $2 $3')
								.replace(/(\d)de\s+(\d)/gi, '$1 de $2')
								.replace(/\s+/g, ' ');
						}
					});
				});
			};

			const hideVendorFilter = () => {
				document.querySelectorAll('.widget-area .widget, .shop-widget, aside .widget').forEach((widget) => {
					const heading = widget.querySelector('.widget-title,.widgettitle,h2,h3,h4');
					const label = (heading?.textContent || '').trim().toLowerCase();
					if (/^(vendedor|vendedores|productor|productores)$/.test(label)) {
						widget.hidden = true;
						widget.setAttribute('aria-hidden', 'true');
					}
				});
			};

			const setupCarousel = () => {
				if (!document.body.classList.contains('elmercado-premium-home')) return;
				const section = document.querySelector('.emo-featured-products');
				const track = section?.querySelector('ul.products');
				const shell = section?.querySelector('.emo-shell') || section;
				if (!section || !track || !shell) return;

				let controls = section.querySelector('.emo-carousel-controls');
				if (!controls) {
					controls = document.createElement('div');
					controls.className = 'emo-carousel-controls';
					controls.setAttribute('aria-label', 'Navegación de productos destacados');

					const makeButton = (direction, label, path) => {
						const button = document.createElement('button');
						button.type = 'button';
						button.className = `emo-carousel-control emo-carousel-control--${direction}`;
						button.setAttribute('aria-label', label);
						button.innerHTML = `<svg aria-hidden="true" viewBox="0 0 24 24"><path d="${path}"/></svg>`;
						return button;
					};

					controls.append(
						makeButton('previous', 'Ver productos anteriores', 'M15 18l-6-6 6-6'),
						makeButton('next', 'Ver productos siguientes', 'M9 6l6 6-6 6')
					);
					shell.appendChild(controls);
				}

				const previous = controls.querySelector('.emo-carousel-control--previous');
				const next = controls.querySelector('.emo-carousel-control--next');
				if (!previous || !next || previous.dataset.bound === '1') return;
				previous.dataset.bound = '1';

				const cardStep = () => {
					const card = track.querySelector('li.product');
					if (!card) return Math.max(track.clientWidth * .82, 280);
					const styles = getComputedStyle(track);
					return card.getBoundingClientRect().width + parseFloat(styles.columnGap || styles.gap || '0');
				};

				const update = () => {
					const max = Math.max(0, track.scrollWidth - track.clientWidth);
					const active = max > 4 && matchMedia('(max-width: 991px)').matches;
					controls.hidden = !active;
					previous.disabled = !active || track.scrollLeft <= 4;
					next.disabled = !active || track.scrollLeft >= max - 4;
				};

				previous.addEventListener('click', () => track.scrollBy({ left: -cardStep(), behavior: 'smooth' }));
				next.addEventListener('click', () => track.scrollBy({ left: cardStep(), behavior: 'smooth' }));
				track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
				window.addEventListener('resize', update, { passive: true });
				new ResizeObserver(update).observe(track);
				requestAnimationFrame(update);
			};

			document.addEventListener('DOMContentLoaded', () => {
				normalizeResults();
				hideVendorFilter();
				setupCarousel();
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
