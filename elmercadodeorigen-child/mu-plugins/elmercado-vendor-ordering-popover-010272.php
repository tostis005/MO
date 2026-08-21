<?php
/**
 * Native mobile ordering parity for WCFM vendor stores 0.10.277.
 *
 * Vendor stores use the same real WooCommerce <select> as the global shop.
 * This file deliberately removes the historical custom button/popover/sheet
 * implementations so there is one visual border and one native touch target.
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
		<style id="elmercado-vendor-ordering-native-010277">
			/* Historical custom ordering UIs are permanently retired. */
			body.wcfmmp-store-page .mdo-vendor-order-button,
			body.wcfmmp-store-page .mdo-vendor-order-menu,
			body.wcfmmp-store-page .mdo-vendor-order-sheet {
				display:none !important;
				visibility:hidden !important;
				pointer-events:none !important;
			}

			@media (max-width:991px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:static !important;
					inset:auto !important;
					z-index:auto !important;
					display:flex !important;
					flex:0 0 148px !important;
					box-sizing:border-box !important;
					width:148px !important;
					min-width:148px !important;
					max-width:148px !important;
					height:44px !important;
					min-height:44px !important;
					max-height:44px !important;
					align-items:center !important;
					justify-content:flex-end !important;
					margin:0 !important;
					padding:0 !important;
					border:0 !important;
					outline:0 !important;
					border-radius:0 !important;
					background:transparent !important;
					box-shadow:none !important;
					overflow:visible !important;
					pointer-events:auto !important;
					transform:none !important;
				}

				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::before,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after {
					content:none !important;
					display:none !important;
					border:0 !important;
					box-shadow:none !important;
					pointer-events:none !important;
				}

				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select {
					position:static !important;
					inset:auto !important;
					z-index:auto !important;
					display:block !important;
					box-sizing:border-box !important;
					width:148px !important;
					min-width:148px !important;
					max-width:148px !important;
					height:42px !important;
					min-height:42px !important;
					max-height:42px !important;
					margin:0 !important;
					padding:0 26px 0 10px !important;
					border:1px solid rgba(23,63,50,.14) !important;
					border-radius:999px !important;
					outline:0 !important;
					background:#f7f9f6 !important;
					box-shadow:none !important;
					color:#173f32 !important;
					font-family:inherit !important;
					font-size:11.5px !important;
					font-weight:700 !important;
					letter-spacing:0 !important;
					line-height:1 !important;
					visibility:visible !important;
					opacity:1 !important;
					pointer-events:auto !important;
					touch-action:manipulation !important;
					clip:auto !important;
					clip-path:none !important;
					cursor:pointer !important;
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
		<script id="elmercado-vendor-ordering-native-script-010277">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			const media = window.matchMedia('(max-width: 991px)');
			const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select';
			let queued = false;

			const important = (node, name, value) => node?.style?.setProperty(name, value, 'important');

			const navigate = value => {
				if (!value) return;
				const url = new URL(window.location.href);
				url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/');
				['paged','product-page','product_page','page','_mdo_scroll'].forEach(key => url.searchParams.delete(key));
				url.searchParams.set('orderby', value);
				window.location.assign(url.href);
			};

			const ownSelect = select => {
				if (!media.matches || !(select instanceof HTMLSelectElement)) return;
				const form = select.closest('.woocommerce-ordering');
				if (!form) return;

				form.querySelectorAll('.mdo-vendor-order-button').forEach(node => node.remove());
				document.querySelectorAll('.mdo-vendor-order-menu, .mdo-vendor-order-sheet').forEach(node => node.remove());

				select.disabled = false;
				select.hidden = false;
				select.removeAttribute('aria-hidden');
				select.tabIndex = 0;
				delete select.dataset.mdoPopover010272;
				delete select.dataset.mdoSheet010276;
				select.dataset.mdoNative010277 = '1';

				[
					['position','static'],['inset','auto'],['z-index','auto'],['display','block'],
					['width','148px'],['min-width','148px'],['max-width','148px'],
					['height','42px'],['min-height','42px'],['max-height','42px'],
					['margin','0'],['padding','0 26px 0 10px'],
					['border','1px solid rgba(23,63,50,.14)'],['border-radius','999px'],
					['outline','0'],['background','#f7f9f6'],['box-shadow','none'],
					['visibility','visible'],['opacity','1'],['pointer-events','auto'],
					['clip','auto'],['clip-path','none'],['touch-action','manipulation']
				].forEach(([name, value]) => important(select, name, value));

				[
					['position','static'],['z-index','auto'],['border','0'],['outline','0'],
					['border-radius','0'],['background','transparent'],['box-shadow','none'],
					['overflow','visible'],['pointer-events','auto']
				].forEach(([name, value]) => important(form, name, value));

				if (select.dataset.mdoNativeBound010277 !== '1') {
					select.dataset.mdoNativeBound010277 = '1';
					select.addEventListener('change', event => {
						event.preventDefault();
						event.stopImmediatePropagation();
						navigate(select.value);
					}, true);
				}
			};

			const repair = () => {
				queued = false;
				if (!media.matches) return;
				document.querySelectorAll(selector).forEach(ownSelect);
			};

			const queueRepair = () => {
				if (queued) return;
				queued = true;
				requestAnimationFrame(repair);
			};

			repair();
			window.addEventListener('load', repair, { once:true });
			window.addEventListener('pageshow', repair, { passive:true });
			media.addEventListener?.('change', repair);
			setTimeout(repair, 250);
			setTimeout(repair, 1000);
			new MutationObserver(mutations => {
				if (mutations.some(m => m.type === 'childList')) queueRepair();
			}).observe(document.body, { childList:true, subtree:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
