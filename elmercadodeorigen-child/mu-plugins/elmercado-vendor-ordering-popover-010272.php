<?php
/**
 * Reliable mobile ordering sheet for WCFM vendor stores 0.10.276.
 *
 * The vendor toolbar keeps the same compact button, but the options are shown
 * in a viewport-anchored bottom sheet. This avoids Safari visual-viewport and
 * clipping issues caused by the previous floating popover.
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
		<style id="elmercado-vendor-ordering-popover-010272">
			.mdo-vendor-order-button,
			.mdo-vendor-order-sheet { display:none; }

			@media (max-width:991px) {
				body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:relative !important;
					z-index:40 !important;
					border:0 !important;
					background:transparent !important;
					box-shadow:none !important;
					overflow:visible !important;
					pointer-events:auto !important;
				}

				body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select {
					position:absolute !important;
					left:0 !important;
					top:0 !important;
					width:1px !important;
					height:1px !important;
					min-width:1px !important;
					min-height:1px !important;
					max-width:1px !important;
					max-height:1px !important;
					margin:0 !important;
					padding:0 !important;
					border:0 !important;
					opacity:0 !important;
					visibility:hidden !important;
					pointer-events:none !important;
					overflow:hidden !important;
					clip-path:inset(50%) !important;
					z-index:-1 !important;
				}

				body.wcfmmp-store-page .mdo-vendor-order-button {
					position:relative !important;
					z-index:42 !important;
					display:flex !important;
					box-sizing:border-box;
					width:148px;
					height:42px;
					min-width:148px;
					min-height:42px;
					align-items:center;
					justify-content:flex-start;
					margin:0;
					padding:0 30px 0 10px;
					border:1px solid rgba(23,63,50,.14);
					border-radius:999px;
					background:#f7f9f6;
					box-shadow:none;
					color:#173f32;
					font:700 11.5px/1 inherit;
					letter-spacing:0;
					white-space:nowrap;
					overflow:hidden;
					text-overflow:ellipsis;
					cursor:pointer;
					pointer-events:auto !important;
					touch-action:manipulation;
					-webkit-tap-highlight-color:transparent;
				}

				body.wcfmmp-store-page .mdo-vendor-order-button::after {
					content:"";
					position:absolute;
					right:12px;
					top:50%;
					width:7px;
					height:7px;
					border-right:1.5px solid currentColor;
					border-bottom:1.5px solid currentColor;
					transform:translateY(-70%) rotate(45deg);
					pointer-events:none;
				}

				body.wcfmmp-store-page .mdo-vendor-order-button[aria-expanded="true"]::after {
					transform:translateY(-25%) rotate(225deg);
				}

				body.wcfmmp-store-page .mdo-vendor-order-sheet {
					position:fixed !important;
					inset:0 !important;
					z-index:2147483000 !important;
					display:flex !important;
					align-items:flex-end !important;
					justify-content:center !important;
					box-sizing:border-box !important;
					width:100vw !important;
					height:100vh !important;
					height:100dvh !important;
					margin:0 !important;
					padding:0 !important;
					background:rgba(9,24,19,.38) !important;
					visibility:visible !important;
					opacity:1 !important;
					pointer-events:auto !important;
				}

				body.wcfmmp-store-page .mdo-vendor-order-sheet[hidden] {
					display:none !important;
					visibility:hidden !important;
					opacity:0 !important;
					pointer-events:none !important;
				}

				body.wcfmmp-store-page .mdo-vendor-order-sheet__panel {
					box-sizing:border-box;
					width:100%;
					max-width:520px;
					max-height:min(76vh,620px);
					max-height:min(76dvh,620px);
					overflow:auto;
					overscroll-behavior:contain;
					-webkit-overflow-scrolling:touch;
					padding:10px 12px calc(12px + env(safe-area-inset-bottom));
					border-radius:22px 22px 0 0;
					background:#fff;
					box-shadow:0 -18px 44px rgba(17,42,34,.2);
				}

				body.wcfmmp-store-page .mdo-vendor-order-sheet__handle {
					display:block;
					width:42px;
					height:4px;
					margin:0 auto 10px;
					border-radius:999px;
					background:rgba(23,63,50,.2);
				}

				body.wcfmmp-store-page .mdo-vendor-order-sheet__title {
					margin:0;
					padding:4px 6px 10px;
					color:#173f32;
					font:800 16px/1.25 inherit;
				}

				body.wcfmmp-store-page .mdo-vendor-order-option {
					display:flex;
					width:100%;
					min-height:48px;
					align-items:center;
					justify-content:space-between;
					gap:12px;
					margin:0;
					padding:0 12px;
					border:0;
					border-radius:12px;
					background:transparent;
					color:#173f32;
					font:700 14px/1.2 inherit;
					text-align:left;
					cursor:pointer;
					touch-action:manipulation;
					-webkit-tap-highlight-color:transparent;
				}

				body.wcfmmp-store-page .mdo-vendor-order-option[aria-current="true"] {
					background:#f1f5f1;
				}

				body.wcfmmp-store-page .mdo-vendor-order-option[aria-current="true"]::after {
					content:"✓";
					flex:0 0 auto;
					font-size:16px;
					line-height:1;
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
		<script id="elmercado-vendor-ordering-popover-script-010272">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			const media = window.matchMedia('(max-width: 991px)');
			const selectSelector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select';
			let active = null;

			const setImportant = (node, name, value) => node?.style?.setProperty(name, value, 'important');

			const retireSelect = select => {
				select.setAttribute('aria-hidden', 'true');
				select.tabIndex = -1;
				[['position','absolute'],['left','0'],['top','0'],['width','1px'],['height','1px'],['min-width','1px'],['min-height','1px'],['max-width','1px'],['max-height','1px'],['margin','0'],['padding','0'],['border','0'],['opacity','0'],['visibility','hidden'],['pointer-events','none'],['overflow','hidden'],['clip-path','inset(50%)'],['z-index','-1']].forEach(([name,value]) => setImportant(select,name,value));
			};

			const hideSheet = instance => {
				if (!instance) return;
				setImportant(instance.sheet, 'display', 'none');
				setImportant(instance.sheet, 'visibility', 'hidden');
				setImportant(instance.sheet, 'opacity', '0');
				setImportant(instance.sheet, 'pointer-events', 'none');
				instance.sheet.hidden = true;
			};

			const showSheet = instance => {
				if (!instance) return;
				instance.sheet.hidden = false;
				[
					['display','flex'],['position','fixed'],['inset','0'],['z-index','2147483000'],
					['align-items','flex-end'],['justify-content','center'],['box-sizing','border-box'],
					['width','100vw'],['height','100dvh'],['margin','0'],['padding','0'],
					['background','rgba(9,24,19,.38)'],['visibility','visible'],['opacity','1'],['pointer-events','auto']
				].forEach(([name,value]) => setImportant(instance.sheet,name,value));
				[
					['display','block'],['box-sizing','border-box'],['width','100%'],['max-width','520px'],
					['max-height','min(76dvh,620px)'],['overflow','auto'],['padding','10px 12px calc(12px + env(safe-area-inset-bottom))'],
					['border-radius','22px 22px 0 0'],['background','#fff'],['box-shadow','0 -18px 44px rgba(17,42,34,.2)']
				].forEach(([name,value]) => setImportant(instance.panel,name,value));
			};

			const closeSheet = (instance, restoreFocus = false) => {
				if (!instance) return;
				hideSheet(instance);
				instance.button.setAttribute('aria-expanded', 'false');
				document.documentElement.style.removeProperty('overflow');
				if (restoreFocus) instance.button.focus({ preventScroll: true });
				if (active === instance) active = null;
			};

			const sync = instance => {
				const option = instance.select.options[instance.select.selectedIndex];
				instance.button.textContent = option ? option.textContent.trim() : 'Ordenar';
				instance.sheet.querySelectorAll('.mdo-vendor-order-option').forEach(item => {
					item.setAttribute('aria-current', item.dataset.value === instance.select.value ? 'true' : 'false');
				});
			};

			const openSheet = instance => {
				if (!media.matches) return;
				if (active && active !== instance) closeSheet(active);
				sync(instance);
				showSheet(instance);
				instance.button.setAttribute('aria-expanded', 'true');
				document.documentElement.style.setProperty('overflow', 'hidden');
				active = instance;
				requestAnimationFrame(() => instance.sheet.querySelector('[aria-current="true"]')?.focus({ preventScroll: true }));
			};

			const navigateToOrdering = value => {
				const url = new URL(window.location.href);
				url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/');
				['paged','product-page','product_page','page','_mdo_scroll'].forEach(key => url.searchParams.delete(key));
				url.searchParams.set('orderby', value);
				window.location.assign(url.href);
			};

			const makeInstance = select => {
				if (!(select instanceof HTMLSelectElement)) return;
				const form = select.closest('.woocommerce-ordering');
				if (!form) return;

				retireSelect(select);
				if (select.dataset.mdoSheet010276 === '1' && form.querySelector('.mdo-vendor-order-button')) return;
				select.dataset.mdoSheet010276 = '1';

				form.querySelectorAll('.mdo-vendor-order-button').forEach(node => node.remove());
				document.querySelectorAll('.mdo-vendor-order-menu').forEach(node => node.remove());

				const id = `mdo-vendor-order-sheet-${Math.random().toString(36).slice(2)}`;
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'mdo-vendor-order-button';
				button.setAttribute('aria-haspopup', 'dialog');
				button.setAttribute('aria-expanded', 'false');
				button.setAttribute('aria-controls', id);

				const sheet = document.createElement('div');
				sheet.id = id;
				sheet.className = 'mdo-vendor-order-sheet';
				sheet.hidden = true;
				sheet.setAttribute('role', 'dialog');
				sheet.setAttribute('aria-modal', 'true');
				sheet.setAttribute('aria-label', 'Ordenar productos');

				const panel = document.createElement('div');
				panel.className = 'mdo-vendor-order-sheet__panel';
				const handle = document.createElement('span');
				handle.className = 'mdo-vendor-order-sheet__handle';
				handle.setAttribute('aria-hidden', 'true');
				const title = document.createElement('h3');
				title.className = 'mdo-vendor-order-sheet__title';
				title.textContent = 'Ordenar productos';
				panel.append(handle, title);

				[...select.options].forEach(option => {
					const item = document.createElement('button');
					item.type = 'button';
					item.className = 'mdo-vendor-order-option';
					item.dataset.value = option.value;
					item.textContent = option.textContent.trim();
					item.addEventListener('click', event => {
						event.preventDefault();
						event.stopPropagation();
						const value = item.dataset.value || '';
						if (!value) return;
						select.value = value;
						sync(instance);
						closeSheet(instance);
						navigateToOrdering(value);
					});
					panel.appendChild(item);
				});

				sheet.appendChild(panel);
				form.appendChild(button);
				document.body.appendChild(sheet);
				const instance = { select, form, button, sheet, panel };
				hideSheet(instance);

				button.addEventListener('click', event => {
					event.preventDefault();
					event.stopPropagation();
					sheet.hidden ? openSheet(instance) : closeSheet(instance);
				});
				sheet.addEventListener('click', event => {
					if (event.target === sheet) closeSheet(instance, true);
				});
				select.addEventListener('change', () => sync(instance));
				sync(instance);
			};

			const repair = () => {
				if (!media.matches) {
					if (active) closeSheet(active);
					return;
				}
				document.querySelectorAll(selectSelector).forEach(select => {
					retireSelect(select);
					makeInstance(select);
				});
			};

			document.addEventListener('keydown', event => {
				if (event.key === 'Escape' && active) closeSheet(active, true);
			});
			media.addEventListener?.('change', repair);
			repair();
			window.addEventListener('load', repair, { once: true });
			window.addEventListener('pageshow', repair, { passive: true });
			setTimeout(repair, 300);
			setTimeout(repair, 1200);
			new MutationObserver(mutations => {
				if (mutations.some(m => m.type === 'childList')) requestAnimationFrame(repair);
			}).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
