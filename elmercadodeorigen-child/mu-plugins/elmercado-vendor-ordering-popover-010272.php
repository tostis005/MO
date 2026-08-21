<?php
/**
 * Native WooCommerce ordering parity for WCFM vendor stores 0.10.278.
 *
 * The shared catalogue layer already gives Shop and producer stores the same
 * ordering geometry. This MU layer only removes third-party select enhancers
 * and makes the real WooCommerce <select> the single visible/clickable control
 * at every breakpoint.
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
		<style id="elmercado-vendor-ordering-native-010278">
			/* Historical custom ordering UIs and third-party visual wrappers are retired. */
			body.wcfmmp-store-page .mdo-vendor-order-button,
			body.wcfmmp-store-page .mdo-vendor-order-menu,
			body.wcfmmp-store-page .mdo-vendor-order-sheet,
			body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > :is(.select2,.select2-container,.chosen-container,.nice-select,.selectize-control) {
				display:none !important;
				visibility:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
			}

			/* The form itself never draws a second surface around the shared native select. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				border:0 !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::before,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after {
				content:none !important;
				display:none !important;
				border:0 !important;
				box-shadow:none !important;
				pointer-events:none !important;
			}

			/* Visibility/interactivity only; visual geometry comes from the shared Shop CSS. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select.orderby {
				position:static !important;
				inset:auto !important;
				display:block !important;
				visibility:visible !important;
				opacity:1 !important;
				clip:auto !important;
				clip-path:none !important;
				transform:none !important;
				pointer-events:auto !important;
				touch-action:manipulation !important;
				cursor:pointer !important;
				z-index:3 !important;
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
		<script id="elmercado-vendor-ordering-native-script-010278">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"]';
			const enhancerSelector = '.select2,.select2-container,.chosen-container,.nice-select,.selectize-control';
			let queued = false;

			const navigate = value => {
				if (!value) return;
				const url = new URL(window.location.href);
				url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/');
				['paged','product-page','product_page','page','_mdo_scroll'].forEach(key => url.searchParams.delete(key));
				url.searchParams.set('orderby', value);
				window.location.assign(url.href);
			};

			const destroyEnhancer = select => {
				try {
					if (window.jQuery) {
						const $select = window.jQuery(select);
						if ($select.data('select2') && typeof $select.select2 === 'function') {
							$select.select2('destroy');
						}
					}
				} catch (_) {}
			};

			const ownSelect = select => {
				if (!(select instanceof HTMLSelectElement)) return;
				const form = select.closest('.woocommerce-ordering');
				if (!(form instanceof HTMLFormElement)) return;

				destroyEnhancer(select);
				form.querySelectorAll(enhancerSelector).forEach(node => node.remove());
				document.querySelectorAll('.mdo-vendor-order-button,.mdo-vendor-order-menu,.mdo-vendor-order-sheet').forEach(node => node.remove());

				select.classList.remove('select2-hidden-accessible', 'select2-offscreen', 'chosen-select');
				select.removeAttribute('aria-hidden');
				select.removeAttribute('data-select2-id');
				select.removeAttribute('hidden');
				select.disabled = false;
				select.tabIndex = 0;
				['display','visibility','opacity','pointer-events','position','inset','clip','clip-path','transform','z-index'].forEach(name => select.style.removeProperty(name));

				/* Keep the established marker for deployment compatibility and add the real revision. */
				select.dataset.mdoNative010277 = '1';
				select.dataset.mdoNativeParity = '010278';
				delete select.dataset.mdoPopover010272;
				delete select.dataset.mdoSheet010276;

				if (select.dataset.mdoNativeBound010278 !== '1') {
					select.dataset.mdoNativeBound010278 = '1';
					select.addEventListener('change', event => {
						event.preventDefault();
						event.stopImmediatePropagation();
						navigate(select.value);
					}, true);
				}
			};

			const repair = () => {
				queued = false;
				document.querySelectorAll(selector).forEach(ownSelect);
			};

			const queueRepair = () => {
				if (queued) return;
				queued = true;
				requestAnimationFrame(repair);
			};

			repair();
			requestAnimationFrame(repair);
			window.addEventListener('load', repair, { once:true });
			window.addEventListener('pageshow', repair, { passive:true });
			setTimeout(repair, 250);
			setTimeout(repair, 1000);
			setTimeout(repair, 2500);

			new MutationObserver(mutations => {
				if (mutations.some(mutation => mutation.type === 'childList')) queueRepair();
			}).observe(document.body, { childList:true, subtree:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
