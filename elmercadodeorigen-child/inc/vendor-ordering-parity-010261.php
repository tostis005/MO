<?php
/**
 * Paridad definitiva del selector de ordenación en tiendas de productor.
 *
 * WCFM puede enriquecer el select de WooCommerce con Select2/Chosen u otros
 * wrappers. La capa de catálogo compartida fuerza a la vez la visibilidad del
 * select original, lo que puede dejar dos superficies superpuestas y hacer que
 * el clic no abra correctamente las opciones. En las tiendas de productor
 * conservamos un único select nativo de WooCommerce, igual que en Tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limita el ajuste exclusivamente al catálogo de una tienda WCFM.
 */
function elmercado_vendor_ordering_parity_target_010261(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) ) {
		return elmercado_vendor_store_is_request_010225();
	}

	if ( function_exists( 'wcfmmp_is_store_page' ) ) {
		return (bool) wcfmmp_is_store_page();
	}

	return function_exists( 'wcfm_is_store_page' ) && (bool) wcfm_is_store_page();
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_vendor_ordering_parity_target_010261() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-ordering-parity-010261">
			/*
			 * La Tienda general usa el select nativo de WooCommerce. En productor
			 * eliminamos cualquier segunda superficie visual creada por WCFM.
			 */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > :is(
				.select2,
				.select2-container,
				.chosen-container,
				.nice-select,
				.selectize-control
			) {
				display:none !important;
				visibility:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.emo-vendor-orderby-native-010261 {
				position:static !important;
				inset:auto !important;
				display:block !important;
				visibility:visible !important;
				opacity:1 !important;
				clip:auto !important;
				clip-path:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
				transform:none !important;
				box-shadow:none !important;
				-webkit-appearance:auto !important;
				appearance:auto !important;
				cursor:pointer !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_vendor_ordering_parity_target_010261() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-ordering-parity-010261">
		(() => {
			'use strict';

			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;

			const enhancerSelector = '.select2,.select2-container,.chosen-container,.nice-select,.selectize-control';
			let normalizing = false;

			const stripEnhancerState = (select) => {
				select.classList.remove('select2-hidden-accessible', 'select2-offscreen', 'chosen-select');
				select.removeAttribute('aria-hidden');
				select.removeAttribute('data-select2-id');
				select.removeAttribute('tabindex');
				select.removeAttribute('style');
			};

			const submitOrdering = (form, select) => {
				if (form instanceof HTMLFormElement) {
					HTMLFormElement.prototype.submit.call(form);
					return;
				}

				const url = new URL(window.location.href);
				url.searchParams.set('orderby', select.value);
				url.searchParams.delete('paged');
				url.searchParams.delete('product-page');
				window.location.assign(url.toString());
			};

			const normalize = () => {
				if (normalizing) return;
				normalizing = true;

				try {
					store.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering').forEach((form) => {
						let select = form.querySelector('select[name="orderby"],select.orderby');
						if (!select) return;

						/*
						 * Un clon limpio rompe cualquier instancia Select2/Chosen ligada al
						 * nodo anterior sin tocar opciones, valor, name ni traducciones.
						 */
						if (select.dataset.emoVendorNativeOrderby !== '010261') {
							const clean = select.cloneNode(true);
							clean.value = select.value;
							stripEnhancerState(clean);
							clean.classList.add('orderby', 'emo-vendor-orderby-native-010261');
							clean.dataset.emoVendorNativeOrderby = '010261';
							select.replaceWith(clean);
							select = clean;

							select.addEventListener('change', () => submitOrdering(form, select));
						} else {
							stripEnhancerState(select);
							select.classList.add('orderby', 'emo-vendor-orderby-native-010261');
						}

						form.querySelectorAll(enhancerSelector).forEach((node) => node.remove());
					});
				} finally {
					normalizing = false;
				}
			};

			const start = () => {
				normalize();
				requestAnimationFrame(normalize);
				setTimeout(normalize, 120);
				setTimeout(normalize, 500);
				setTimeout(normalize, 1200);

				/* WCFM puede inicializar su enhancer después del ready. */
				const observer = new MutationObserver(normalize);
				observer.observe(store, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style', 'aria-hidden'] });
				setTimeout(() => observer.disconnect(), 2500);
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
