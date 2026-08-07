<?php
/**
 * Cierre robusto y explícito del menú móvil de Woostify.
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
		<style id="elmercado-mobile-menu-close-final-style">
			@media (max-width: 991px) {
				body.elmercado-child-theme .sidebar-menu {
					position: fixed !important;
			}

				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					position: absolute !important;
					top: 14px !important;
					right: 14px !important;
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					color: transparent !important;
					font-size: 0 !important;
					line-height: 0 !important;
					box-shadow: 0 6px 20px rgba(23,63,50,.18) !important;
					cursor: pointer !important;
					z-index: 10002 !important;
				}

				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before,
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					content: "" !important;
					position: absolute !important;
					width: 19px !important;
					height: 2px !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: #fff !important;
					transform-origin: center !important;
				}

				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before {
					transform: rotate(45deg) !important;
				}

				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					transform: rotate(-45deg) !important;
				}

				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close:focus-visible {
					outline: 2px solid #d6a738 !important;
					outline-offset: 3px !important;
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
		<script id="elmercado-mobile-menu-close-final">
		(() => {
			'use strict';

			const root = document.documentElement;
			let closeGuardUntil = 0;

			const getMenu = () => document.querySelector('.sidebar-menu');
			const getToggle = () => document.querySelector('.site-header .toggle-sidebar-menu-btn');

			const ensureCloseButton = () => {
				const menu = getMenu();
				if (!menu) return null;
				let button = menu.querySelector('.elmercado-mobile-menu-close');
				if (!button) {
					button = document.createElement('button');
					button.type = 'button';
					button.className = 'elmercado-mobile-menu-close';
					button.setAttribute('aria-label', 'Cerrar menú');
					button.setAttribute('title', 'Cerrar menú');
					menu.prepend(button);
				}
				return button;
			};

			const syncA11y = (open) => {
				const menu = getMenu();
				const toggle = getToggle();
				if (menu) menu.setAttribute('aria-hidden', open ? 'false' : 'true');
				if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			};

			const closeMenu = () => {
				root.classList.remove('sidebar-menu-open');
				document.body?.classList.remove('sidebar-menu-open');
				syncA11y(false);
			};

			const forceClose = () => {
				closeGuardUntil = Date.now() + 1200;
				closeMenu();
				[40, 120, 260, 520, 900].forEach((delay) => window.setTimeout(closeMenu, delay));
			};

			const isCloseTarget = (target) => Boolean(target?.closest?.(
				'.sidebar-menu .elmercado-mobile-menu-close, .sidebar-menu .close-sidebar-menu-btn, .sidebar-menu .close-sidebar-menu, .sidebar-menu [class*="close-sidebar"], .sidebar-menu-overlay'
			));

			const init = () => {
				ensureCloseButton();
				const toggle = getToggle();
				if (toggle && !toggle.dataset.elmercadoCloseBound) {
					toggle.dataset.elmercadoCloseBound = '1';
					toggle.addEventListener('click', () => {
						window.setTimeout(() => {
							ensureCloseButton();
							syncA11y(root.classList.contains('sidebar-menu-open'));
						}, 30);
					});
				}
			};

			document.addEventListener('pointerup', (event) => {
				const target = event.target instanceof Element ? event.target : null;
				if (isCloseTarget(target)) forceClose();
			}, true);

			document.addEventListener('click', (event) => {
				const target = event.target instanceof Element ? event.target : null;
				if (!target) return;
				if (isCloseTarget(target)) {
					forceClose();
					return;
				}
				const menuLink = target.closest('.sidebar-menu .menu-item > a');
				if (menuLink && !menuLink.parentElement?.classList.contains('menu-item-has-children')) forceClose();
			}, true);

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && root.classList.contains('sidebar-menu-open')) {
					forceClose();
					getToggle()?.focus();
				}
			});

			const classObserver = new MutationObserver(() => {
				if (Date.now() < closeGuardUntil && root.classList.contains('sidebar-menu-open')) closeMenu();
				if (root.classList.contains('sidebar-menu-open')) ensureCloseButton();
			});
			classObserver.observe(root, { attributes: true, attributeFilter: ['class'] });

			const domObserver = new MutationObserver(init);
			domObserver.observe(document.documentElement, { childList: true, subtree: true });
			init();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
