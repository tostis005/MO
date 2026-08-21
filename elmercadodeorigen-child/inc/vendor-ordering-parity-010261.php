<?php
/**
 * Native WooCommerce ordering control for WCFM vendor stores.
 *
 * WCFM and legacy theme layers may enhance or rebuild the ordering <select>.
 * Instead of mutating that third-party node, this module mounts a fresh copy
 * of WooCommerce's native ordering form inside the shared vendor toolbar and
 * keeps ownership of that form if WCFM redraws the toolbar.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limit the fix to a WCFM vendor storefront request.
 */
function elmercado_vendor_ordering_parity_target_010262(): bool {
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

/**
 * Return WooCommerce's current ordering options, matching the global Shop.
 *
 * @return array<string,string>
 */
function elmercado_vendor_ordering_options_010262(): array {
	$options = array(
		'menu_order' => __( 'Default sorting', 'woocommerce' ),
		'popularity' => __( 'Sort by popularity', 'woocommerce' ),
		'rating'     => __( 'Sort by average rating', 'woocommerce' ),
		'date'       => __( 'Sort by latest', 'woocommerce' ),
		'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
		'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
	);

	if ( 'yes' !== get_option( 'woocommerce_enable_review_rating' ) ) {
		unset( $options['rating'] );
	}

	/** This is the same public filter WooCommerce uses for its Shop orderby. */
	return (array) apply_filters( 'woocommerce_catalog_orderby', $options );
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_vendor_ordering_parity_target_010262() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-ordering-native-010262">
			/* Only our Woo-native form is visible in the vendor toolbar. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering:not(.emo-vendor-ordering-native-010262),
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering:not(.emo-vendor-ordering-native-010262) {
				display: none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262 {
				position: static !important;
				inset: auto !important;
				display: flex !important;
				visibility: visible !important;
				opacity: 1 !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				outline: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				transform: none !important;
				pointer-events: auto !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262 > :is(.select2,.select2-container,.chosen-container,.nice-select,.selectize-control),
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262 > :is(.select2,.select2-container,.chosen-container,.nice-select,.selectize-control) {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262 select.orderby,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar .emo-vendor-ordering-native-010262 select.orderby {
				position: static !important;
				inset: auto !important;
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				clip: auto !important;
				clip-path: none !important;
				transform: none !important;
				pointer-events: auto !important;
				-webkit-appearance: auto !important;
				appearance: auto !important;
				cursor: pointer !important;
				z-index: 3 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_vendor_ordering_parity_target_010262() ) {
			return;
		}

		$options         = elmercado_vendor_ordering_options_010262();
		$default_orderby = (string) apply_filters(
			'woocommerce_default_catalog_orderby',
			get_option( 'woocommerce_default_catalog_orderby', 'menu_order' )
		);
		$current_orderby = isset( $_GET['orderby'] )
			? wc_clean( wp_unslash( $_GET['orderby'] ) )
			: $default_orderby;

		if ( ! isset( $options[ $current_orderby ] ) ) {
			$current_orderby = isset( $options[ $default_orderby ] ) ? $default_orderby : (string) array_key_first( $options );
		}
		?>
		<template id="emo-vendor-ordering-template-010262">
			<form class="woocommerce-ordering emo-vendor-ordering-native-010262" method="get" data-emo-ordering-version="010262">
				<select name="orderby" class="orderby" aria-label="<?php echo esc_attr__( 'Shop order', 'woocommerce' ); ?>">
					<?php foreach ( $options as $id => $name ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" name="paged" value="1" />
				<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
			</form>
		</template>
		<script id="elmercado-vendor-ordering-native-runtime-010262">
		(() => {
			'use strict';

			const store = document.querySelector('#wcfmmp-store');
			const template = document.querySelector('#emo-vendor-ordering-template-010262');
			if (!store || !(template instanceof HTMLTemplateElement)) return;

			const enhancerSelector = '.select2,.select2-container,.chosen-container,.nice-select,.selectize-control';
			let syncing = false;
			let frame = 0;

			const cleanOwnedSelect = (form) => {
				const select = form.querySelector('select[name="orderby"]');
				if (!(select instanceof HTMLSelectElement)) return;

				select.classList.remove('select2-hidden-accessible', 'select2-offscreen', 'chosen-select');
				select.removeAttribute('aria-hidden');
				select.removeAttribute('data-select2-id');
				select.removeAttribute('tabindex');
				select.style.removeProperty('display');
				select.style.removeProperty('visibility');
				select.style.removeProperty('opacity');
				select.style.removeProperty('pointer-events');
				form.querySelectorAll(enhancerSelector).forEach((node) => node.remove());
			};

			const bindOwnedForm = (form) => {
				if (form.dataset.emoOrderingBound === '010262') return;
				form.dataset.emoOrderingBound = '010262';

				form.addEventListener('change', (event) => {
					const select = event.target instanceof Element ? event.target.closest('select[name="orderby"]') : null;
					if (!(select instanceof HTMLSelectElement)) return;

					const paged = form.querySelector('input[name="paged"]');
					if (paged instanceof HTMLInputElement) paged.value = '1';

					HTMLFormElement.prototype.submit.call(form);
				});
			};

			const createOwnedForm = () => {
				const source = template.content.querySelector('form.woocommerce-ordering');
				if (!(source instanceof HTMLFormElement)) return null;
				const form = source.cloneNode(true);
				form.classList.add('emo-vendor-ordering-native-010262');
				form.dataset.emoOrderingVersion = '010262';
				cleanOwnedSelect(form);
				bindOwnedForm(form);
				return form;
			};

			const findToolbar = () => {
				return store.querySelector('.elmercado-vendor-toolbar') ||
					store.querySelector('.emo-catalog-toolbar-shared-010229') ||
					store.querySelector('.woocommerce-ordering')?.parentElement ||
					null;
			};

			const sync = () => {
				if (syncing) return;
				syncing = true;
				try {
					const toolbar = findToolbar();
					if (!(toolbar instanceof Element)) return;

					let owned = toolbar.querySelector('.emo-vendor-ordering-native-010262');
					if (!(owned instanceof HTMLFormElement)) {
						const fresh = createOwnedForm();
						if (!(fresh instanceof HTMLFormElement)) return;

						const legacy = toolbar.querySelector('.woocommerce-ordering');
						if (legacy) {
							legacy.replaceWith(fresh);
						} else {
							toolbar.appendChild(fresh);
						}
						owned = fresh;
					}

					cleanOwnedSelect(owned);
					bindOwnedForm(owned);

					toolbar.querySelectorAll('.woocommerce-ordering:not(.emo-vendor-ordering-native-010262)').forEach((node) => node.remove());
				} finally {
					syncing = false;
				}
			};

			const scheduleSync = () => {
				if (frame) return;
				frame = requestAnimationFrame(() => {
					frame = 0;
					sync();
				});
			};

			const start = () => {
				sync();
				requestAnimationFrame(sync);

				/* Keep our form if WCFM redraws the toolbar after AJAX/filter work. */
				const observer = new MutationObserver(scheduleSync);
				observer.observe(store, { childList: true, subtree: true });
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
