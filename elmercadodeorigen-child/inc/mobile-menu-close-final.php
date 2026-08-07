<?php
/**
 * Cierre robusto del menú móvil de Woostify.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

			const syncA11y = (open) => {
				const menu = document.querySelector('.sidebar-menu');
				const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
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
				'.sidebar-menu .close-sidebar-menu-btn, .sidebar-menu .close-sidebar-menu, .sidebar-menu [class*="close-sidebar"], .sidebar-menu-overlay'
			));

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
				if (menuLink && !menuLink.parentElement?.classList.contains('menu-item-has-children')) {
					forceClose();
				}
			}, true);

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && root.classList.contains('sidebar-menu-open')) {
					forceClose();
				document.querySelector('.site-header .toggle-sidebar-menu-btn')?.focus();
				}
			});

			const classObserver = new MutationObserver(() => {
				if (Date.now() < closeGuardUntil && root.classList.contains('sidebar-menu-open')) {
					closeMenu();
			}
			});
			classObserver.observe(root, { attributes: true, attributeFilter: ['class'] });

			const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
			if (toggle) {
				toggle.addEventListener('click', () => {
					window.setTimeout(() => syncA11y(root.classList.contains('sidebar-menu-open')), 30);
				});
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
