<?php
/**
 * Cierre visual de navegación móvil y geometría uniforme de herramientas.
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
		<style id="elmercado-mobile-menu-visual-final">
			@media (max-width: 991px) {
				/* Tres controles, tres huellas idénticas y el mismo paso horizontal. */
				html body.elmercado-child-theme .site-header-inner > .woostify-container {
					grid-template-columns: 28px minmax(0, 1fr) 98px !important;
				}
				html body.elmercado-child-theme .site-header .site-tools {
					display: grid !important;
					grid-template-columns: repeat(3, 30px) !important;
					grid-auto-columns: 30px !important;
					grid-auto-flow: column !important;
					gap: 4px !important;
					width: 98px !important;
					min-width: 98px !important;
					height: 40px !important;
					align-items: center !important;
					justify-items: center !important;
					justify-content: end !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > * {
					display: grid !important;
					width: 30px !important;
					min-width: 30px !important;
					max-width: 30px !important;
					height: 30px !important;
					min-height: 30px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					align-self: center !important;
					justify-self: center !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: transparent !important;
					box-shadow: none !important;
					line-height: 1 !important;
					transform: none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > * > :is(a,button),
				html body.elmercado-child-theme .site-header .site-tools > :is(a,button,[role="button"]) {
					display: grid !important;
					width: 30px !important;
					min-width: 30px !important;
					max-width: 30px !important;
					height: 30px !important;
					min-height: 30px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 999px !important;
					background: transparent !important;
					box-shadow: none !important;
					line-height: 1 !important;
					transform: none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > *:hover {
					background: transparent !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > * > :is(a,button):hover,
				html body.elmercado-child-theme .site-header .site-tools > :is(a,button,[role="button"]):hover {
					background: rgba(23, 63, 50, .075) !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools :is(svg,.woostify-svg-icon) {
					width: 18px !important;
					height: 18px !important;
					max-width: 18px !important;
					max-height: 18px !important;
					margin: 0 !important;
					transform: none !important;
				}

				/* Woostify puede imprimir el cierre por ID fuera del drawer. */
				html.sidebar-menu-open body.elmercado-child-theme :is(
					#close-sidebar-menu-btn,
					#close-sidebar-menu,
					[id*="close-sidebar"],
					.close-sidebar-menu-btn,
					.close-sidebar-menu,
					.sidebar-menu-close,
					[class*="close-sidebar"]
				) {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
				}

				/* Algunos cierres/iconos de Woostify se pintan como pseudo-elementos del panel. */
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu::before,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu::after,
				html.sidebar-menu-open body.elmercado-child-theme .emo-mobile-menu-overlay::before,
				html.sidebar-menu-open body.elmercado-child-theme .emo-mobile-menu-overlay::after,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn::before,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn::after,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn *::before,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn *::after {
					content: none !important;
					display: none !important;
			}

				/* Nunca se muestra una segunda lupa independiente del campo completo. */
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-menu-search-artifact-hidden,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-empty-menu-artifact-hidden {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-empty-menu-artifact-hidden::before,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-empty-menu-artifact-hidden::after,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-empty-menu-artifact-hidden *::before,
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-empty-menu-artifact-hidden *::after {
					content: none !important;
					display: none !important;
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
		<script id="elmercado-mobile-menu-visual-final-js">
		(() => {
			'use strict';
			const html = document.documentElement;
			const menu = document.querySelector('.sidebar-menu');
			if (!menu) return;

			const isInsidePrimarySearch = (node, searchRoot) => Boolean(
				searchRoot && (searchRoot.contains(node) || node.contains(searchRoot))
			);

			const cleanMenuArtifacts = () => {
				if (!html.classList.contains('sidebar-menu-open')) return;

				document.querySelectorAll('#close-sidebar-menu-btn,#close-sidebar-menu,[id*="close-sidebar"],.close-sidebar-menu-btn,.close-sidebar-menu,.sidebar-menu-close,[class*="close-sidebar"]').forEach((node) => {
					if (node.classList?.contains('elmercado-mobile-menu-close')) return;
					node.classList?.add('emo-native-menu-close-hidden');
					node.setAttribute?.('aria-hidden', 'true');
					node.style?.setProperty('display', 'none', 'important');
				});

				const searchRoot = menu.querySelector('.dgwt-wcas-search-wrapp,.aws-container,form.search-form');
				const candidates = menu.querySelectorAll([
					'.header-search-icon',
					'.search-icon',
					'.site-search-toggle',
					'.search-toggle',
					'.woostify-search-toggle',
					'.toggle-search',
					'[class*="search-toggle"]',
					'[class*="search-icon"]',
					'button[aria-label*="Buscar" i]',
					'a[aria-label*="Buscar" i]',
					'button[aria-label*="Search" i]',
					'a[aria-label*="Search" i]',
					'button[title*="Buscar" i]',
					'a[title*="Buscar" i]',
					'button[title*="Search" i]',
					'a[title*="Search" i]'
				].join(','));

				candidates.forEach((node) => {
					if (isInsidePrimarySearch(node, searchRoot)) return;
					if (node.closest('.elmercado-mobile-menu-close')) return;
					node.classList.add('emo-menu-search-artifact-hidden');
					node.setAttribute('aria-hidden', 'true');
				});

				/* El icono suelto de búsqueda llega como un ítem sin campo real. */
				const navRoot = menu.querySelector('.primary-navigation,.primary-menu,ul.menu');
				if (navRoot) {
					[...navRoot.children].forEach((item) => {
						if (!(item instanceof Element)) return;
						if (item.contains(searchRoot)) return;
						if (item.querySelector('input,textarea,select,form')) return;
						const text = (item.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
						const control = item.querySelector(':scope > a,:scope > button');
						const accessible = `${control?.getAttribute('aria-label') || ''} ${control?.getAttribute('title') || ''}`.trim().toLowerCase();
						const empty = text === '' && accessible === '';
						const searchOnly = /^(buscar|search|escribe para buscar|type to search)$/.test(text || accessible);
						if (!empty && !searchOnly) return;
						item.classList.add('emo-empty-menu-artifact-hidden');
						item.setAttribute('aria-hidden', 'true');
					});
				}
			};

			let timers = [];
			const scheduleClean = () => {
				timers.forEach(clearTimeout);
				timers = [];
				cleanMenuArtifacts();
				if (!html.classList.contains('sidebar-menu-open')) return;
				timers.push(setTimeout(cleanMenuArtifacts, 60));
				timers.push(setTimeout(cleanMenuArtifacts, 240));
			};

			new MutationObserver(scheduleClean).observe(html, { attributes: true, attributeFilter: ['class'] });
			scheduleClean();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
