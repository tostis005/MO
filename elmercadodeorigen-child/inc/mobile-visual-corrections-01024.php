<?php
/**
 * Correcciones visuales 0.10.30: drawer móvil, copy genérico y estabilidad de home.
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
		<style id="elmercado-mobile-visual-corrections-01030">
			@media (max-width: 991px) {
				html body.elmercado-child-theme .sidebar-menu { overflow-x: hidden !important; }
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
				html.sidebar-menu-open body.elmercado-child-theme .woostify-search-wrap .icon-close { display: none !important; }
				html body.elmercado-child-theme .sidebar-menu .emo-duplicate-search-item,
				html body.elmercado-child-theme .sidebar-menu li.menu-item .dgwt-wcas-search-wrapp {
					display: none !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
				}
				html body.elmercado-child-theme .sidebar-menu form.woocommerce-product-search.search-form {
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
				html body.elmercado-child-theme .sidebar-menu form.woocommerce-product-search.search-form > input.search-field {
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
				html body.elmercado-child-theme .sidebar-menu form.woocommerce-product-search.search-form > button {
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
		<script id="elmercado-mobile-visual-corrections-01030-js">
		(() => {
			'use strict';
			const root = document.documentElement;
			const normalizePrimarySearch = () => {
				const menu = document.querySelector('.sidebar-menu');
				if (!menu) return null;
				const forms = [...menu.querySelectorAll('form.woocommerce-product-search')];
				const primary = forms.find((form) => !form.closest('.emo-duplicate-search-item')) || forms[0] || null;
				if (primary && !primary.classList.contains('search-form')) primary.classList.add('search-form');
				return primary;
			};
			const suppressGlobalSearch = () => {
				document.querySelectorAll('.site-dialog-search,.woostify-search-wrap').forEach((node) => {
					if (node.closest('.sidebar-menu')) return;
					node.dataset.emoMenuSuppressed = '1';
					node.style.setProperty('display', 'none', 'important');
					node.style.setProperty('visibility', 'hidden', 'important');
					node.style.setProperty('opacity', '0', 'important');
					node.style.setProperty('pointer-events', 'none', 'important');
					node.setAttribute('aria-hidden', 'true');
				});
			};
			const restoreGlobalSearch = () => {
				document.querySelectorAll('[data-emo-menu-suppressed="1"]').forEach((node) => {
					node.style.removeProperty('display');
					node.style.removeProperty('visibility');
					node.style.removeProperty('opacity');
					node.style.removeProperty('pointer-events');
					node.removeAttribute('aria-hidden');
					delete node.dataset.emoMenuSuppressed;
				});
			};
			const clean = () => {
				normalizePrimarySearch();
				const open = root.classList.contains('sidebar-menu-open');
				if (!open) {
					restoreGlobalSearch();
					return;
				}
				const menu = document.querySelector('.sidebar-menu');
				if (!menu) return;
				suppressGlobalSearch();
				menu.querySelectorAll('.emo-duplicate-search-item').forEach((node) => {
					node.setAttribute('aria-hidden', 'true');
					node.style.setProperty('display', 'none', 'important');
				});
			};
			const settle = () => {
				clean();
				requestAnimationFrame(clean);
				[25, 75, 160, 300].forEach((delay) => setTimeout(clean, delay));
			};
			new MutationObserver(settle).observe(root, { attributes: true, attributeFilter: ['class'] });
			document.addEventListener('DOMContentLoaded', normalizePrimarySearch, { once: true });
			[0, 80, 220].forEach((delay) => setTimeout(normalizePrimarySearch, delay));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
