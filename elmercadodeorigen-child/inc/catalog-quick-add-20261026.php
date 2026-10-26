<?php
/**
 * Compra rápida en tarjetas del catálogo y tiendas WCFM.
 *
 * Reutiliza el enlace nativo de WooCommerce para respetar las reglas de compra,
 * integraciones AJAX, stock y productos con opciones. No duplica botones.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return bool */
function elmercado_is_quick_add_catalog_page_20261026(): bool {
	if ( is_admin() || ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop()
		|| is_product_category()
		|| is_product_tag()
		|| ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() )
		|| ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() )
		|| (bool) get_query_var( 'store' );
}

/* El AJAX de WooCommerce actualiza el contador y los fragmentos del minicart. */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( elmercado_is_quick_add_catalog_page_20261026() && function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
	},
	30
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_is_quick_add_catalog_page_20261026() ) {
			return;
		}
		?>
		<style id="emo-catalog-quick-add-20261026">
			body.elmercado-child-theme ul.products li.product:has(> a.emo-card-quick-add) {
				position: relative !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
				box-sizing: border-box !important;
				position: absolute !important;
				top: 11px !important;
				right: 11px !important;
				bottom: auto !important;
				left: auto !important;
				z-index: 8 !important;
				display: inline-flex !important;
				justify-content: center !important;
				align-items: center !important;
				gap: 6px !important;
				width: auto !important;
				max-width: calc(100% - 22px) !important;
				min-width: 0 !important;
				min-height: 40px !important;
				height: auto !important;
				margin: 0 !important;
				padding: 8px 11px !important;
				border: 1px solid rgba(23, 63, 50, .24) !important;
				border-radius: 999px !important;
				background: rgba(255, 255, 255, .97) !important;
				box-shadow: 0 3px 12px rgba(16, 43, 34, .13) !important;
				color: #173f32 !important;
				font-size: 12px !important;
				font-weight: 750 !important;
				line-height: 1.2 !important;
				letter-spacing: 0 !important;
				text-align: center !important;
				text-decoration: none !important;
				text-transform: none !important;
				white-space: nowrap !important;
				visibility: visible !important;
				opacity: 1 !important;
				pointer-events: auto !important;
				overflow: hidden !important;
				transform: none !important;
				transition: background-color .16s ease, color .16s ease, box-shadow .16s ease !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add::before {
				content: none !important;
				display: none !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add > .emo-card-quick-add__symbol {
				display: inline-block !important;
				flex: 0 0 auto !important;
				margin: 0 !important;
				color: inherit !important;
				font-family: Arial, sans-serif !important;
				font-size: 20px !important;
				font-weight: 500 !important;
				line-height: 1 !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:hover,
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:focus-visible,
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add--confirmed {
				background: #173f32 !important;
				border-color: #173f32 !important;
				color: #fff !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:focus-visible {
				outline: 3px solid #f1b95c !important;
				outline-offset: 2px !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add.loading {
				opacity: .75 !important;
				pointer-events: none !important;
			}
			/* WooCommerce inserta "Ver carrito" después del AJAX; el acceso a carrito
			   ya existe en la cabecera y este enlace no debe tapar la tarjeta. */
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add ~ a.added_to_cart.wc-forward {
				display: none !important;
			}
			.emo-card-quick-add__announcer {
				position: absolute !important;
				width: 1px !important;
				height: 1px !important;
				padding: 0 !important;
				margin: -1px !important;
				border: 0 !important;
				overflow: hidden !important;
				clip: rect(0, 0, 0, 0) !important;
				white-space: nowrap !important;
			}
			@media (max-width: 767px) {
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
					top: 8px !important;
					right: 8px !important;
					width: 42px !important;
					min-width: 42px !important;
					max-width: 42px !important;
					height: 42px !important;
					min-height: 42px !important;
					padding: 0 !important;
					gap: 0 !important;
					font-size: 0 !important;
				}
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add > :not(.emo-card-quick-add__symbol) {
					display: none !important;
				}
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add > .emo-card-quick-add__symbol {
					font-size: 24px !important;
				}
			}
			@media (prefers-reduced-motion: reduce) {
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
					transition: none !important;
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
		if ( ! elmercado_is_quick_add_catalog_page_20261026() ) {
			return;
		}
		?>
		<script id="emo-catalog-quick-add-js-20261026">
		(() => {
			'use strict';
			if (!document.body.matches('.woocommerce-shop,.post-type-archive-product,.tax-product_cat,.tax-product_tag,.wcfmmp-store-page')) return;

			const sourceSelector = [
				'a.add_to_cart_button',
				'a.product_type_variable.button',
				'a.product_type_grouped.button',
				'a.product_type_external.button'
			].join(',');

			const enhanceCard = (card) => {
				if (card.querySelector(':scope > a.emo-card-quick-add')) return;

				const controls = [...card.querySelectorAll(sourceSelector)]
					.filter((anchor) => !anchor.matches('.emo-card-quick-add,.added_to_cart,.wc-forward') && anchor.getAttribute('href'));
				const control = controls.find((anchor) => anchor.classList.contains('add_to_cart_button')) || controls[0];
				if (!control) return;

				const label = (control.getAttribute('aria-label') || control.textContent || '').replace(/\s+/g, ' ').trim();
				if (!label) return;
				const productName = (card.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3')?.textContent || '').replace(/\s+/g, ' ').trim();
				if (!control.getAttribute('aria-label')) {
					control.setAttribute('aria-label', productName ? label + ': ' + productName : label);
				}

				const options = control.matches('.product_type_variable,.product_type_grouped,.product_type_external');
				/* Producto simple + ID nativo: WooCommerce gestiona el AJAX y el stock. */
				if (!options && control.matches('.product_type_simple.add_to_cart_button') &&
					control.dataset.product_id && window.wc_add_to_cart_params) {
					control.classList.add('ajax_add_to_cart');
				}

				control.classList.add('emo-card-quick-add');
				control.dataset.emoQuickAction = options ? 'options' : 'add';
				control.setAttribute('title', control.getAttribute('aria-label'));

				const symbol = document.createElement('span');
				symbol.className = 'emo-card-quick-add__symbol';
				symbol.setAttribute('aria-hidden', 'true');
				symbol.textContent = options ? '›' : '+';
				control.prepend(symbol);

				/* Mover, no clonar: conserva el enlace nativo y evita compras duplicadas.
				   Fuera de la imagen enlazada, el clic no abre la ficha accidentalmente. */
				card.append(control);
			};

			const enhance = () => {
				document.querySelectorAll('ul.products li.product').forEach(enhanceCard);
			};

			const start = () => {
				enhance();
				let scheduled = false;
				const schedule = () => {
					if (scheduled) return;
					scheduled = true;
					requestAnimationFrame(() => {
						scheduled = false;
						enhance();
					});
				};

				/* La carga continua y los filtros insertan tarjetas sin recargar la página. */
				new MutationObserver((mutations) => {
					const addedProducts = mutations.some((mutation) =>
						[...mutation.addedNodes].some((node) =>
							node.nodeType === 1 &&
							(node.matches?.('li.product,ul.products') || node.querySelector?.('li.product'))
						)
					);
					if (addedProducts) schedule();
				}).observe(document.body, { childList: true, subtree: true });

				const announce = document.createElement('span');
				announce.className = 'emo-card-quick-add__announcer';
				announce.setAttribute('role', 'status');
				announce.setAttribute('aria-live', 'polite');
				document.body.append(announce);

				if (window.jQuery) {
					window.jQuery(document.body).on('added_to_cart', (_event, _fragments, _hash, $button) => {
						const control = $button?.[0];
						if (!control?.classList?.contains('emo-card-quick-add')) return;
						const symbol = control.querySelector('.emo-card-quick-add__symbol');
						if (!symbol) return;
						symbol.textContent = '✓';
						control.classList.add('emo-card-quick-add--confirmed');
						announce.textContent = document.documentElement.lang.startsWith('en')
							? 'Added to cart' : 'Producto añadido al carrito';
						window.setTimeout(() => {
							if (!control.isConnected) return;
							symbol.textContent = '+';
							control.classList.remove('emo-card-quick-add--confirmed');
						}, 1600);
					});
				}
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', start, { once: true });
			} else {
				start();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
