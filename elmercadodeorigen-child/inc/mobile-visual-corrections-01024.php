<?php
/**
 * Correcciones visuales 0.10.24: drawer móvil y copy genérico de tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Evita mensajes de catálogo ligados a categorías concretas o a una selección fija. */
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
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-mobile-visual-corrections-01024">
			@media (max-width: 991px) {
				/* Al abrir el drawer desaparece por completo el disparador del header.
				 * Woostify lo transforma en una segunda X fuera del panel. */
				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				body.elmercado-child-theme.sidebar-menu-open .site-header .toggle-sidebar-menu-btn {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}

				/* El cierre propio queda completamente dentro del drawer y separado del buscador. */
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					top: 14px !important;
					right: 14px !important;
					z-index: 20 !important;
				}

				/* La búsqueda ocupa el ancho interior real del drawer y comienza debajo del cierre. */
				html body.elmercado-child-theme .sidebar-menu :is(
					.dgwt-wcas-search-wrapp,
					.aws-container,
					form.search-form
				) {
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
					overflow: visible !important;
				}
				html body.elmercado-child-theme .sidebar-menu :is(
					.dgwt-wcas-sf-wrapp,
					.aws-search-form
				) {
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
				html body.elmercado-child-theme .sidebar-menu :is(
					.dgwt-wcas-search-input,
					.aws-search-field,
					input[type="search"]
				) {
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: 100% !important;
					margin: 0 !important;
					border: 1px solid rgba(23,63,50,.22) !important;
					border-radius: 999px !important;
					background: #fff !important;
					box-shadow: none !important;
				}

				/* Cualquier elemento vacío detectado por JS desaparece, incluidos SVG/pseudo-elementos. */
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
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-mobile-visual-corrections-01024-js">
		(() => {
			'use strict';
			const root = document.documentElement;

			const clean = () => {
				if (!root.classList.contains('sidebar-menu-open')) return;
				const menu = document.querySelector('.sidebar-menu');
				if (!menu) return;

				/* El disparador del header es la segunda X de Woostify cuando el menú está abierto. */
				const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
				if (toggle) {
					toggle.setAttribute('aria-hidden', 'true');
					toggle.style.setProperty('display', 'none', 'important');
				}

				/* Limpia ítems de navegación sin contenido semántico, aunque sólo lleven SVG o pseudo-elementos. */
				menu.querySelectorAll('ul > li, nav > a, nav > button').forEach((item) => {
					if (!(item instanceof Element)) return;
					if (item.closest('.elmercado-mobile-menu-close')) return;
					if (item.querySelector('input,textarea,select,form')) return;
					if (item.querySelector('ul,ol')) return;
					const text = (item.textContent || '').replace(/\s+/g, ' ').trim();
					const control = item.matches('a,button') ? item : item.querySelector(':scope > a,:scope > button');
					const aria = `${control?.getAttribute('aria-label') || ''} ${control?.getAttribute('title') || ''}`.trim();
					const href = control?.getAttribute('href') || '';
					const meaningfulHref = href && href !== '#' && !/^javascript:/i.test(href);
					if (!text && !aria && !meaningfulHref) item.classList.add('emo-empty-nav-artifact');
				});
			};

			new MutationObserver(() => {
				clean();
				if (!root.classList.contains('sidebar-menu-open')) {
					const toggle = document.querySelector('.site-header .toggle-sidebar-menu-btn');
					if (toggle) {
						toggle.removeAttribute('aria-hidden');
						toggle.style.removeProperty('display');
					}
				}
			}).observe(root, { attributes: true, attributeFilter: ['class'] });

			[0, 60, 180, 400].forEach((delay) => setTimeout(clean, delay));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
