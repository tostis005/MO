<?php
/**
 * Oculta de forma definitiva el selector de productor de la tienda y la barra
 * de búsqueda/categoría de la página de Productores.
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
		<style id="elmercado-shop-producer-filter-final">
			body.elmercado-child-theme.woocommerce-shop .woostify-sorting > *:has(select):not(.woocommerce-ordering),
			body.elmercado-child-theme.post-type-archive-product .woostify-sorting > *:has(select):not(.woocommerce-ordering),
			body.elmercado-child-theme.woocommerce-shop .woostify-sorting > label:has(select),
			body.elmercado-child-theme.post-type-archive-product .woostify-sorting > label:has(select) {
				display: none !important;
				visibility: hidden !important;
			}

			body.elmercado-child-theme.woocommerce-shop .woostify-sorting,
			body.elmercado-child-theme.post-type-archive-product .woostify-sorting {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
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
		<script id="elmercado-shop-producer-filter-final-script">
		(() => {
			'use strict';
			const hide = () => {
				document.querySelectorAll('.woocommerce-shop .woostify-sorting select, .post-type-archive-product .woostify-sorting select').forEach((select) => {
					if ((select.name || '').toLowerCase() === 'orderby' || select.closest('.woocommerce-ordering')) return;
					const direct = [...(select.closest('.woostify-sorting')?.children || [])].find((child) => child.contains(select));
					if (!direct) return;
					direct.classList.add('elmercado-vendor-filter-hidden');
					direct.hidden = true;
					direct.setAttribute('aria-hidden', 'true');
				});
			};
			hide();
			document.addEventListener('DOMContentLoaded', hide, { once: true });
			setTimeout(hide, 250);
			setTimeout(hide, 900);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

/*
 * En /productores/ no necesitamos herramientas de descubrimiento: el listado es
 * deliberadamente corto y editorial. Ocultamos la barra completa que WCFM
 * construye alrededor de "Buscar tienda" y "Seleccionar categoría".
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_page( 'productores' ) ) {
			return;
		}
		?>
		<style id="elmercado-producers-filter-bar-remove-010238">
			#wcfmmp-stores-wrap .wcfmmp-store-search-form,
			#wcfmmp-stores-wrap .wcfmmp-store-search-form-box,
			#wcfmmp-stores-wrap [class*="store-search-form"] {
				display:none !important;
				visibility:hidden !important;
				height:0 !important;
				min-height:0 !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				overflow:hidden !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_page( 'productores' ) ) {
			return;
		}
		?>
		<script id="elmercado-producers-filter-bar-remove-script-010238">
		(() => {
			'use strict';
			const removeFilterBar = () => {
				const root = document.querySelector('#wcfmmp-stores-wrap');
				if (!root) return;

				const candidates = new Set(root.querySelectorAll('.wcfmmp-store-search-form,[class*="store-search-form"]'));
				root.querySelectorAll('input,select').forEach((control) => {
					const signature = [control.id, control.name, control.placeholder, control.getAttribute('aria-label')]
						.filter(Boolean).join(' ').toLowerCase();
					if (!/(store[_-]?name|store[_-]?category|buscar tienda|search store)/.test(signature)) return;

					let bar = control.closest('.wcfmmp-store-search-form,[class*="store-search-form"]');
					if (!bar) {
						let node = control.parentElement;
						for (let depth = 0; node && node !== root && depth < 5; depth += 1, node = node.parentElement) {
							if (node.querySelector('select') && node.querySelector('input[type="text"],input[type="search"]')) {
								bar = node;
								break;
							}
						}
					}
					if (bar) candidates.add(bar);
				});

				candidates.forEach((bar) => {
					bar.hidden = true;
					bar.setAttribute('aria-hidden', 'true');
					bar.style.setProperty('display', 'none', 'important');
					bar.style.setProperty('visibility', 'hidden', 'important');
					bar.style.setProperty('height', '0', 'important');
					bar.style.setProperty('margin', '0', 'important');
					bar.style.setProperty('padding', '0', 'important');
				});
			};

			removeFilterBar();
			document.addEventListener('DOMContentLoaded', removeFilterBar, { once: true });
			setTimeout(removeFilterBar, 250);
			setTimeout(removeFilterBar, 900);
			new MutationObserver(removeFilterBar).observe(document.documentElement, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
