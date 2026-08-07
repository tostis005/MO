<?php
/**
 * Correcciones visuales 0.10.25: drawer móvil y copy genérico de tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'gettext',
	static function ( string $translation, string $text, string $domain ): string {
		$replacements = array(
			'Descubre aceites, ibéricos, fruta y otros productos con origen, seleccionados por su calidad y elaborados por productores que conocemos.' => 'Descubre productos directamente desde su origen, para acercar lo que se produce a quienes quieren disfrutarlo en casa.',
			'Descubre aceites, ibéricos y especialidades de despensa elegidos por su procedencia, su calidad y el trabajo de quienes los elaboran.' => 'Descubre productos directamente desde su origen, para acercar lo que se produce a quienes quieren disfrutarlo en casa.',
		);
		return $replacements[ $translation ] ?? $replacements[ $text ] ?? $translation;
	},
	999,
	3
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) return;
		?>
		<style id="elmercado-mobile-visual-corrections-01025">
			@media (max-width: 991px) {
				html.sidebar-menu-open .toggle-sidebar-menu-btn,
				html.sidebar-menu-open body .toggle-sidebar-menu-btn,
				body.sidebar-menu-open .toggle-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				body.elmercado-child-theme.sidebar-menu-open .site-header .toggle-sidebar-menu-btn {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}

				html body.elmercado-child-theme .sidebar-menu {
					overflow-x: hidden !important;
				}
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					top: 14px !important;
					right: 14px !important;
					z-index: 20 !important;
				}

				html body.elmercado-child-theme .sidebar-menu :is(.dgwt-wcas-search-wrapp,.aws-container,form.search-form) {
					position: relative !important;
					left: auto !important;
					right: auto !important;
					transform: none !important;
					box-sizing: border-box !important;
					width: calc(100% - 36px) !important;
					max-width: calc(100% - 36px) !important;
					margin: 72px 18px 18px !important;
					padding: 0 !important;
					border: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .sidebar-menu :is(.dgwt-wcas-sf-wrapp,.aws-search-form) {
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: 100% !important;
					margin: 0 !important;
					padding: 0 !important;
					border: 0 !important;
					border-radius: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .sidebar-menu :is(.dgwt-wcas-search-input,.aws-search-field,input[type="search"]) {
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: 100% !important;
					margin: 0 !important;
					border: 1px solid rgba(23,63,50,.22) !important;
					border-radius: 999px !important;
					background: #fff !important;
					box-shadow: none !important;
				}

				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact::before,
				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact::after,
				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact *::before,
				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact *::after {
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
		if ( is_admin() ) return;
		?>
		<script id="elmercado-mobile-visual-corrections-01025-js">
		(() => {
			'use strict';
			const root = document.documentElement;
			const clean = () => {
				if (!root.classList.contains('sidebar-menu-open')) return;
				const menu = document.querySelector('.sidebar-menu');
				if (!menu) return;
				document.querySelectorAll('.toggle-sidebar-menu-btn').forEach((toggle) => {
					toggle.setAttribute('aria-hidden', 'true');
					toggle.style.setProperty('display', 'none', 'important');
				});
				menu.querySelectorAll('ul > li, nav > a, nav > button').forEach((item) => {
					if (!(item instanceof Element)) return;
					if (item.closest('.elmercado-mobile-menu-close')) return;
					if (item.querySelector('input,textarea,select,form,ul,ol')) return;
					const text = (item.textContent || '').replace(/\s+/g, ' ').trim();
					const control = item.matches('a,button') ? item : item.querySelector(':scope > a,:scope > button');
					const aria = `${control?.getAttribute('aria-label') || ''} ${control?.getAttribute('title') || ''}`.trim();
					const href = control?.getAttribute('href') || '';
					const meaningfulHref = href && href !== '#' && !/^javascript:/i.test(href);
					if (!text && !aria && !meaningfulHref) item.classList.add('emo-empty-nav-artifact');
				});
			};
			const observer = new MutationObserver(() => {
				clean();
				if (!root.classList.contains('sidebar-menu-open')) {
					document.querySelectorAll('.toggle-sidebar-menu-btn').forEach((toggle) => {
						toggle.removeAttribute('aria-hidden');
						toggle.style.removeProperty('display');
					});
				}
			});
			observer.observe(document.documentElement, { attributes: true, childList: true, subtree: true, attributeFilter: ['class'] });
			[0, 40, 100, 220, 450].forEach((delay) => setTimeout(clean, delay));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
