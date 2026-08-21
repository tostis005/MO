<?php
/**
 * Native WooCommerce ordering parity for WCFM vendor stores 0.10.278.
 *
 * The producer toolbar must expose one real WooCommerce <select>, with the
 * same visual surface as the global Shop and without Select2/NiceSelect or
 * legacy custom ordering controls sitting over it.
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
			body.wcfmmp-store-page .mdo-vendor-order-button,
			body.wcfmmp-store-page .mdo-vendor-order-menu,
			body.wcfmmp-store-page .mdo-vendor-order-sheet,
			body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > :is(.select2,.select2-container,.chosen-container,.nice-select,.selectize-control) {
				display:none !important;
				visibility:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				box-sizing:border-box !important;
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

			/* Target by name, not by a class WCFM can replace. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"] {
				box-sizing:border-box !important;
				position:static !important;
				inset:auto !important;
				display:block !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				outline:0 !important;
				background:#f8faf8 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:700 !important;
				letter-spacing:0 !important;
				line-height:1 !important;
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

			@media (max-width:991px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:relative !important;
					left:50% !important;
					width:calc(100vw - 58px) !important;
					min-width:calc(100vw - 58px) !important;
					max-width:calc(100vw - 58px) !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					transform:translateX(-50%) !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"] {
					width:100% !important;
					min-width:100% !important;
					max-width:100% !important;
					padding:0 34px 0 13px !important;
				}
			}

			@media (min-width:992px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"] {
					width:250px !important;
					min-width:250px !important;
					max-width:250px !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"] {
					padding:0 30px 0 12px !important;
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
