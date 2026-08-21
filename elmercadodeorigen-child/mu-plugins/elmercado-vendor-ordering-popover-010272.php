<?php
/**
 * Native WooCommerce ordering parity for WCFM vendor stores 0.10.279.
 *
 * The producer toolbar exposes one real WooCommerce <select>. WCFM/legacy
 * scripts may write inline !important styles after wp_head, so this owner also
 * restores the final Shop geometry after those scripts have run and whenever
 * they try to restyle the ordering control later.
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
		<style id="elmercado-vendor-ordering-native-010279">
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
				font-size:11.75px !important;
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
		<script id="elmercado-vendor-ordering-native-script-010279">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"]';
			const formSelector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering';
			const enhancerSelector = '.select2,.select2-container,.chosen-container,.nice-select,.selectize-control';
			const mobile = window.matchMedia('(max-width: 991px)');
			let queued = false;
			let observer = null;

			const important = (node, name, value) => node?.style?.setProperty(name, value, 'important');

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
						if ($select.data('select2') && typeof $select.select2 === 'function') $select.select2('destroy');
					}
				} catch (_) {}
			};

			const forceFinalGeometry = (form, select) => {
				[
					['box-sizing','border-box'],['border','0'],['outline','0'],['background','transparent'],
					['box-shadow','none'],['overflow','visible'],['pointer-events','auto']
				].forEach(([name,value]) => important(form,name,value));

				[
					['box-sizing','border-box'],['position','static'],['inset','auto'],['display','block'],
					['height','40px'],['min-height','40px'],['max-height','40px'],['margin','0'],
					['border','1px solid rgba(23,63,50,.14)'],['border-radius','999px'],['outline','0'],
					['background','#f8faf8'],['box-shadow','none'],['color','#173f32'],['font-family','inherit'],
					['font-size','11.75px'],['font-weight','700'],['letter-spacing','0'],['line-height','1'],
					['visibility','visible'],['opacity','1'],['clip','auto'],['clip-path','none'],['transform','none'],
					['pointer-events','auto'],['touch-action','manipulation'],['cursor','pointer'],['z-index','3']
				].forEach(([name,value]) => important(select,name,value));

				if (mobile.matches) {
					[
						['position','relative'],['left','50%'],['width','calc(100vw - 58px)'],
						['min-width','calc(100vw - 58px)'],['max-width','calc(100vw - 58px)'],
						['height','40px'],['min-height','40px'],['max-height','40px'],['transform','translateX(-50%)']
					].forEach(([name,value]) => important(form,name,value));
					[['width','100%'],['min-width','100%'],['max-width','100%'],['padding','0 42px 0 12px']]
						.forEach(([name,value]) => important(select,name,value));
				} else {
					[['position','static'],['left','auto'],['transform','none'],['width','250px'],['min-width','250px'],['max-width','250px']]
						.forEach(([name,value]) => important(form,name,value));
					[['width','250px'],['min-width','250px'],['max-width','250px'],['padding','0 30px 0 12px']]
						.forEach(([name,value]) => important(select,name,value));
				}
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
				select.dataset.mdoNative010277 = '1';
				select.dataset.mdoNativeParity = '010279';
				delete select.dataset.mdoPopover010272;
				delete select.dataset.mdoSheet010276;

				forceFinalGeometry(form, select);

				if (select.dataset.mdoNativeBound010279 !== '1') {
					select.dataset.mdoNativeBound010279 = '1';
					select.addEventListener('change', event => {
						event.preventDefault();
						event.stopImmediatePropagation();
						navigate(select.value);
					}, true);
				}
			};

			const startObserving = () => {
				if (!observer || !document.body) return;
				observer.observe(document.body, {
					childList:true,
					subtree:true,
					attributes:true,
					attributeFilter:['style','class','hidden','aria-hidden','disabled']
				});
			};

			const repair = () => {
				queued = false;
				observer?.disconnect();
				document.querySelectorAll(selector).forEach(ownSelect);
				startObserving();
			};

			const queueRepair = () => {
				if (queued) return;
				queued = true;
				requestAnimationFrame(repair);
			};

			observer = new MutationObserver(mutations => {
				for (const mutation of mutations) {
					if (mutation.type === 'childList') {
						queueRepair();
						return;
					}
					const target = mutation.target;
					if (!(target instanceof Element)) continue;
					if (target.matches(selector) || target.matches(formSelector) || target.matches(enhancerSelector) || target.closest(formSelector)) {
						queueRepair();
						return;
					}
				}
			});

			repair();
			requestAnimationFrame(repair);
			window.addEventListener('load', repair, { once:true });
			window.addEventListener('pageshow', repair, { passive:true });
			mobile.addEventListener?.('change', repair);
			setTimeout(repair, 100);
			setTimeout(repair, 300);
			setTimeout(repair, 750);
			setTimeout(repair, 1250);
			setTimeout(repair, 2500);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
