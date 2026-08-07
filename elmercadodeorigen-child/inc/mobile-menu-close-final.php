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
			const closeMenu = () => {
				root.classList.remove('sidebar-menu-open');
				document.body?.classList.remove('sidebar-menu-open');
				const menu = document.querySelector('.sidebar-menu');
				if (menu) menu.setAttribute('aria-hidden', 'true');
				const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
				if (toggle) toggle.setAttribute('aria-expanded', 'false');
			};

			document.addEventListener('click', (event) => {
				const target = event.target instanceof Element ? event.target : null;
				if (!target) return;

				if (target.closest('.sidebar-menu .close-sidebar-menu-btn, .sidebar-menu .close-sidebar-menu, .sidebar-menu [class*="close-sidebar"], .sidebar-menu-overlay, .sidebar-menu .menu-item > a:not(.menu-item-has-children > a)')) {
					window.setTimeout(closeMenu, 0);
				}
			}, true);

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && root.classList.contains('sidebar-menu-open')) {
					closeMenu();
					document.querySelector('.site-header .toggle-sidebar-menu-btn')?.focus();
				}
			});

			const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
			if (toggle) {
				toggle.addEventListener('click', () => {
					window.setTimeout(() => {
						const open = root.classList.contains('sidebar-menu-open');
						toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
						const menu = document.querySelector('.sidebar-menu');
						if (menu) menu.setAttribute('aria-hidden', open ? 'false' : 'true');
					}, 0);
				});
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
