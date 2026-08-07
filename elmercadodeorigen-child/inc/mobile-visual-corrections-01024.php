<?php
/**
 * Correcciones visuales 0.10.26: drawer móvil y copy genérico de tienda.
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
		<style id="elmercado-mobile-visual-corrections-01026">
			@media (max-width: 991px) {
				html body.elmercado-child-theme .sidebar-menu {
					overflow-x: hidden !important;
				}

				/* El icono X que se veía fuera del drawer pertenece al diálogo global de búsqueda,
				 * no al disparador del menú. Mientras el menú está abierto, ese diálogo permanece
				 * inactivo y ninguno de sus controles debe dibujarse. */
				html.sidebar-menu-open body.elmercado-child-theme .site-dialog-search,
				html.sidebar-menu-open body.elmercado-child-theme .woostify-search-wrap,
				html.sidebar-menu-open body.elmercado-child-theme .site-dialog-search .woostify-svg-icon.icon-close,
				html.sidebar-menu-open body.elmercado-child-theme .site-dialog-search > .icon-close,
				html.sidebar-menu-open body.elmercado-child-theme .woostify-search-wrap .icon-close {
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .site-dialog-search .woostify-svg-icon.icon-close,
				html.sidebar-menu-open body.elmercado-child-theme .site-dialog-search > .icon-close,
				html.sidebar-menu-open body.elmercado-child-theme .woostify-search-wrap .icon-close {
					display: none !important;
				}

				/* Sólo un buscador en el drawer: el formulario superior de WooCommerce.
				 * El ítem de menú duplicado de FiboSearch es el que generaba la lupa suelta. */
				html body.elmercado-child-theme .sidebar-menu .emo-duplicate-search-item,
				html body.elmercado-child-theme .sidebar-menu li.menu-item .dgwt-wcas-search-wrapp {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}

				/* Geometría del buscador real: 18 px de margen a cada lado dentro del drawer. */
				html body.elmercado-child-theme .sidebar-menu > form.woocommerce-product-search,
				html body.elmercado-child-theme .sidebar-menu > .woocommerce-product-search {
					position: relative !important;
					left: auto !important;
					right: auto !important;
					transform: none !important;
					display: block !important;
					box-sizing: border-box !important;
					width: calc(100% - 36px) !important;
					max-width: calc(100% - 36px) !important;
					height: 52px !important;
					min-height: 52px !important;
					margin: 74px 18px 18px !important;
					padding: 0 !important;
					border: 0 !important;
					border-radius: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
					overflow: visible !important;
				}
				html body.elmercado-child-theme .sidebar-menu > form.woocommerce-product-search > input.search-field {
					position: static !important;
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: 100% !important;
					height: 52px !important;
					min-height: 52px !important;
					margin: 0 !important;
					padding: 0 54px 0 16px !important;
					border: 1px solid rgba(23,63,50,.22) !important;
					border-radius: 999px !important;
					background: #fff !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .sidebar-menu > form.woocommerce-product-search > button {
					position: absolute !important;
					top: 4px !important;
					right: 4px !important;
					left: auto !important;
					display: grid !important;
					width: 44px !important;
					height: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
				}

				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					top: 14px !important;
					right: 14px !important;
					z-index: 30 !important;
				}

				html body.elmercado-child-theme .sidebar-menu .emo-empty-nav-artifact {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
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
		<script id="elmercado-mobile-visual-corrections-01026-js">
		(() => {
			'use strict';
			const root = document.documentElement;
			const clean = () => {
				if (!root.classList.contains('sidebar-menu-open')) return;
				const menu = document.querySelector('.sidebar-menu');
				if (!menu) return;

				/* Retira el cierre huérfano del diálogo de búsqueda global. */
				document.querySelectorAll('.site-dialog-search .icon-close,.woostify-search-wrap .icon-close').forEach((node) => {
					node.setAttribute('aria-hidden', 'true');
					node.style.setProperty('display', 'none', 'important');
					node.style.setProperty('visibility', 'hidden', 'important');
				});

				/* Retira el clon FiboSearch del menú; el buscador WooCommerce superior se conserva. */
				menu.querySelectorAll('.emo-duplicate-search-item').forEach((node) => {
					node.setAttribute('aria-hidden', 'true');
					node.style.setProperty('display', 'none', 'important');
				});
			};

			new MutationObserver(clean).observe(document.documentElement, {
				attributes: true,
				childList: true,
				subtree: true,
				attributeFilter: ['class']
			});
			[0, 40, 100, 220, 450].forEach((delay) => setTimeout(clean, delay));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
