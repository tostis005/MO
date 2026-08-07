<?php
/**
 * Correcciones visuales finales para móvil: menú, búsqueda, tarjetas y filtros.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Mensaje de portada estable y genérico: del origen al consumidor, sin enumerar categorías. */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$replacements = array(
			'Aceites, ibéricos y despensa de productores' => 'Productos directamente desde su origen',
			'Descubre aceites, ibéricos y especialidades de despensa elegidos por su procedencia, su calidad y el trabajo de quienes los elaboran.' => 'Descubre productos directamente desde su origen, acercando lo que se produce a quienes quieren disfrutarlo en casa.',
			'Aceites para cada día, ibéricos para compartir y productos con los que convertir una comida o un regalo en algo especial.' => 'Descubre productos de origen y encuentra nuevas formas de llevarlos directamente a tu mesa.',
			'Aceites, ibéricos y especialidades seleccionados por su calidad y su procedencia.' => 'Productos seleccionados por su calidad, su procedencia y todo lo que aporta conocer su origen.',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	},
	999
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-mobile-visual-corrections-01023">
			@media (max-width: 991px) {
				/* El drawer no debe sacar controles fuera de su propio ancho. */
				html body.elmercado-child-theme .sidebar-menu {
					overflow-x: hidden !important;
				}

				/* Un único cierre visible: el que queda dentro del drawer. */
				html body.elmercado-child-theme :is(
					#close-sidebar-menu-btn,
					#close-sidebar-menu,
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
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					position: absolute !important;
					top: 14px !important;
					right: 14px !important;
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
					width: 42px !important;
					height: 42px !important;
					min-width: 42px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					box-shadow: none !important;
					color: transparent !important;
					font-size: 0 !important;
					line-height: 0 !important;
					z-index: 4 !important;
				}
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before,
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					content: "" !important;
					position: absolute !important;
					width: 18px !important;
					height: 2px !important;
					border-radius: 999px !important;
					background: #fff !important;
				}
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before { transform: rotate(45deg) !important; }
				html body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after { transform: rotate(-45deg) !important; }

				/* Buscador: una sola caja, sin marcos ni sombras de wrappers anidados. */
				html body.elmercado-child-theme .sidebar-menu :is(
					.dgwt-wcas-search-wrapp,
					.dgwt-wcas-sf-wrapp,
					.aws-container,
					.aws-search-form,
					form.search-form,
					.site-search,
					.search-form
				) {
					width: 100% !important;
					max-width: 100% !important;
					box-sizing: border-box !important;
					margin-inline: 0 !important;
					padding: 0 !important;
					border: 0 !important;
					border-radius: 0 !important;
					background: transparent !important;
					box-shadow: none !important;
					outline: 0 !important;
				}
				html body.elmercado-child-theme .sidebar-menu :is(
					.dgwt-wcas-search-input,
					.aws-search-field,
					input[type="search"]
				) {
					display: block !important;
					width: 100% !important;
					max-width: 100% !important;
					height: 46px !important;
					min-height: 46px !important;
					box-sizing: border-box !important;
					margin: 0 !important;
					padding: 0 44px 0 14px !important;
					border: 1px solid rgba(23,63,50,.22) !important;
					border-radius: 12px !important;
					background: #fff !important;
					box-shadow: none !important;
					outline-offset: 2px !important;
				}

				/* Títulos de producto: dos líneas completas, sin cortar la segunda por altura insuficiente. */
				html body.elmercado-child-theme ul.products li.product :is(
					.woocommerce-loop-product__title,
					.woostify-loop-product__title,
					.product-title,
					h2,
					h3
				) {
					display: -webkit-box !important;
					-webkit-box-orient: vertical !important;
					-webkit-line-clamp: 2 !important;
					overflow: hidden !important;
					height: auto !important;
					min-height: calc(2 * 1.4em + 2px) !important;
					max-height: calc(2 * 1.4em + 2px) !important;
					padding-block: 0 2px !important;
					line-height: 1.4 !important;
					text-overflow: ellipsis !important;
				}

				/* Títulos de widgets dentro del panel de filtros: centrados y sin invadir el borde. */
				html body.elmercado-child-theme .emo-mobile-filter-panel :is(
					.widget-title,
					.widgettitle,
					h2,
					h3
				) {
					position: static !important;
					inset: auto !important;
					transform: none !important;
					display: block !important;
					width: 100% !important;
					max-width: 100% !important;
					box-sizing: border-box !important;
					margin: 0 0 12px !important;
					padding: 12px 14px !important;
					border: 1px solid rgba(23,63,50,.14) !important;
					border-radius: 10px !important;
					background: #f7f9f6 !important;
					color: #173f32 !important;
					line-height: 1.25 !important;
					text-align: center !important;
					box-shadow: none !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-panel .widget {
					overflow: visible !important;
					padding-top: 0 !important;
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
		<script id="elmercado-mobile-visual-corrections-01023-js">
		(() => {
			'use strict';
			const root = document.documentElement;
			const getMenu = () => document.querySelector('.sidebar-menu');

			const closeMenu = () => {
				root.classList.remove('sidebar-menu-open');
				document.body?.classList.remove('sidebar-menu-open');
				document.querySelector('.site-header .toggle-sidebar-menu-btn')?.setAttribute('aria-expanded', 'false');
			};

			const normalizeMenu = () => {
				const menu = getMenu();
				if (!menu) return;

				let close = menu.querySelector('.elmercado-mobile-menu-close');
				if (!close) {
					close = document.createElement('button');
					close.type = 'button';
					close.className = 'elmercado-mobile-menu-close';
					close.setAttribute('aria-label', 'Cerrar menú');
					close.setAttribute('title', 'Cerrar menú');
					menu.prepend(close);
				}

				document.querySelectorAll('#close-sidebar-menu-btn,#close-sidebar-menu,.close-sidebar-menu-btn,.close-sidebar-menu,.sidebar-menu-close,[class*="close-sidebar"]').forEach((node) => {
					if (node === close || node.contains(close)) return;
					node.setAttribute?.('aria-hidden', 'true');
					node.style?.setProperty('display', 'none', 'important');
				});
			};

			document.addEventListener('click', (event) => {
				const target = event.target instanceof Element ? event.target : null;
				if (target?.closest('.elmercado-mobile-menu-close')) {
					event.preventDefault();
					closeMenu();
				}
			}, true);

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && root.classList.contains('sidebar-menu-open')) closeMenu();
			});

			new MutationObserver(normalizeMenu).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
			normalizeMenu();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
