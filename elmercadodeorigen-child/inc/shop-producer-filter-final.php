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
 * deliberadamente corto y editorial. La clase real de WCFM es
 * .wcfmmp-store-lists-sorting; se oculta de forma directa en lugar de depender
 * de una clase histórica o de detectar los campos por heurística.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_page( 'productores' ) ) {
			return;
		}
		?>
		<style id="elmercado-producers-filter-bar-remove-010239">
			#wcfmmp-stores-wrap .wcfmmp-store-lists-sorting,
			#wcfmmp-stores-wrap .wcfmmp-store-search-form,
			#wcfmmp-stores-wrap .wcfmmp-store-search-form-box,
			#wcfmmp-stores-wrap [class*="store-search-form"],
			#wcfmmp-stores-wrap [name="wcfmmp_store_search"],
			#wcfmmp-stores-wrap [name="wcfmmp_store_category"] {
				display:none !important;
				visibility:hidden !important;
				width:0 !important;
				height:0 !important;
				min-width:0 !important;
				min-height:0 !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				overflow:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
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
		<script id="elmercado-producers-filter-bar-remove-script-010239">
		(() => {
			'use strict';
			const hideNode = (node) => {
				if (!node) return;
				node.hidden = true;
				node.setAttribute('aria-hidden', 'true');
				node.style.setProperty('display', 'none', 'important');
				node.style.setProperty('visibility', 'hidden', 'important');
				node.style.setProperty('width', '0', 'important');
				node.style.setProperty('height', '0', 'important');
				node.style.setProperty('min-width', '0', 'important');
				node.style.setProperty('min-height', '0', 'important');
				node.style.setProperty('margin', '0', 'important');
				node.style.setProperty('padding', '0', 'important');
				node.style.setProperty('border', '0', 'important');
				node.style.setProperty('opacity', '0', 'important');
				node.style.setProperty('pointer-events', 'none', 'important');
			};

			const removeFilterBar = () => {
				const root = document.querySelector('#wcfmmp-stores-wrap');
				if (!root) return;

				root.querySelectorAll('.wcfmmp-store-lists-sorting,.wcfmmp-store-search-form,[class*="store-search-form"],[name="wcfmmp_store_search"],[name="wcfmmp_store_category"]').forEach((node) => {
					const bar = node.closest('.wcfmmp-store-lists-sorting') || node;
					hideNode(bar);
				});
			};

			removeFilterBar();
			document.addEventListener('DOMContentLoaded', removeFilterBar, { once: true });
			requestAnimationFrame(removeFilterBar);
			setTimeout(removeFilterBar, 250);
			setTimeout(removeFilterBar, 900);
			setTimeout(removeFilterBar, 1800);
			new MutationObserver(removeFilterBar).observe(document.documentElement, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
