<?php
/**
 * Reliable mobile ordering popover for WCFM vendor stores 0.10.272.
 *
 * Keeps the exact WooCommerce ordering values but avoids the vendor-store
 * native select picker conflict on touch devices. Desktop remains native.
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
			.mdo-vendor-order-menu { display:none; }

			@media (max-width:991px) {
				body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:relative !important;
					z-index:30 !important;
					border:0 !important;
					background:transparent !important;
					box-shadow:none !important;
					overflow:visible !important;
				}

				body.wcfmmp-store-page .emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select {
					position:absolute !important;
					inset:auto !important;
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
					pointer-events:none !important;
					clip-path:inset(50%) !important;
				}

				body.wcfmmp-store-page .mdo-vendor-order-button {
					position:relative;
					display:flex;
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

				body.wcfmmp-store-page .mdo-vendor-order-menu {
					position:fixed;
					z-index:2147483000;
					dis:block;
					box-sizing:border-box;
					min-width:180px;
					max-width:calc(100vw - 24px);
					margin:0;
					padding:6px;
					border:1px solid rgba(23,63,50,.14);
					border-radius:14px;
					background:#fff;
					box-shadow:0 14px 38px rgba(17,42,34,.16);
				}

				body.wcfmmp-store-page .mdo-vendor-order-menu[hidden] { display:none !important; }
				body.wcfmmp-store-page .mdo-vendor-order-menu:not([hidden]) { display:block !important; }

				body.wcfmmp-store-page .mdo-vendor-order-option {
					display:flex;
					width:100%;
					min-height:42px;
					align-items:center;
					margin:0;
					padding:0 12px;
					border:0;
					border-radius:10px;
					background:transparent;
					color:#173f32;
					font:700 12px/1.2 inherit;
					text-align:left;
					cursor:pointer;
					touch-action:manipulation;
				}

				body.wcfmmp-store-page .mdo-vendor-order-option[aria-current="true"],
				body.wcfmmp-store-page .mdo-vendor-order-option:focus-visible {
					background:#f1f5f1;
					outline:none;
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
			let openInstance = null;

			const closeMenu = (instance, restoreFocus = false) => {
				if (!instance) return;
				instance.menu.hidden = true;
				instance.button.setAttribute('aria-expanded', 'false');
				if (restoreFocus) instance.button.focus({ preventScroll: true });
				if (openInstance === instance) openInstance = null;
			};

			const positionMenu = instance => {
				const r = instance.button.getBoundingClientRect();
				const gap = 6;
				const menuWidth = Math.max(180, r.width);
				instance.menu.style.width = `${menuWidth}px`;
				const maxLeft = Math.max(12, window.innerWidth - menuWidth - 12);
				instance.menu.style.left = `${Math.min(Math.max(12, r.left), maxLeft)}px`;
				instance.menu.style.top = `${Math.min(window.innerHeight - 12, r.bottom + gap)}px`;
			};

			const sync = instance => {
				const option = instance.select.options[instance.select.selectedIndex];
				instance.button.textContent = option ? option.textContent.trim() : '';
				instance.menu.querySelectorAll('.mdo-vendor-order-option').forEach(item => {
					item.setAttribute('aria-current', item.dataset.value === instance.select.value ? 'true' : 'false');
				});
			};

			const openMenu = instance => {
				if (openInstance && openInstance !== instance) closeMenu(openInstance);
				sync(instance);
				instance.menu.hidden = false;
				instance.button.setAttribute('aria-expanded', 'true');
				positionMenu(instance);
				openInstance = instance;
			};

			const makeInstance = select => {
				if (!(select instanceof HTMLSelectElement) || select.dataset.mdoPopover010272 === '1') return;
				select.dataset.mdoPopover010272 = '1';
				const form = select.closest('.woocommerce-ordering');
				if (!form) return;

				const id = `mdo-vendor-order-menu-${Math.random().toString(36).slice(2)}`;
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'mdo-vendor-order-button';
				button.setAttribute('aria-haspopup', 'listbox');
				button.setAttribute('aria-expanded', 'false');
				button.setAttribute('aria-controls', id);

				const menu = document.createElement('div');
				menu.id = id;
				menu.className = 'mdo-vendor-order-menu';
				menu.setAttribute('role', 'listbox');
				menu.hidden = true;

				[...select.options].forEach(option => {
					const item = document.createElement('button');
					item.type = 'button';
					item.className = 'mdo-vendor-order-option';
					item.setAttribute('role', 'option');
					item.dataset.value = option.value;
					item.textContent = option.textContent.trim();
					item.addEventListener('click', () => {
						const changed = select.value !== item.dataset.value;
						select.value = item.dataset.value;
						sync(instance);
						closeMenu(instance);
						if (changed) {
							select.dispatchEvent(new Event('change', { bubbles: true }));
							setTimeout(() => {
								if (document.contains(select) && select.form && select.value === item.dataset.value) {
									try { select.form.requestSubmit ? select.form.requestSubmit() : select.form.submit(); } catch (_) {}
								}
							}, 250);
						}
					});
					menu.appendChild(item);
				});

				form.appendChild(button);
				document.body.appendChild(menu);
				const instance = { select, form, button, menu };
				button.addEventListener('click', () => menu.hidden ? openMenu(instance) : closeMenu(instance));
				select.addEventListener('change', () => sync(instance));
				sync(instance);
			};

			const repair = () => {
				if (!media.matches) {
					if (openInstance) closeMenu(openInstance);
					return;
				}
				document.querySelectorAll(selectSelector).forEach(makeInstance);
			};

			document.addEventListener('pointerdown', event => {
				if (!openInstance) return;
				if (openInstance.button.contains(event.target) || openInstance.menu.contains(event.target)) return;
				closeMenu(openInstance);
			}, true);
			document.addEventListener('keydown', event => {
				if (event.key === 'Escape' && openInstance) closeMenu(openInstance, true);
			});
			window.addEventListener('resize', () => { if (openInstance) positionMenu(openInstance); });
			window.addEventListener('scroll', () => { if (openInstance) positionMenu(openInstance); }, true);
			media.addEventListener?.('change', repair);
			repair();
			window.addEventListener('load', repair, { once: true });
			new MutationObserver(m => { if (m.some(x => x.type === 'childList')) requestAnimationFrame(repair); }).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
