<?php
/**
 * Eliminación definitiva del trigger nativo redundante de filtros 0.10.96.
 *
 * El catálogo ya dispone de rail visible en escritorio y del trigger canónico
 * del child theme en compacto. El botón `.filter` de Woostify no aporta una
 * segunda acción útil y puede reaparecer con estilos inline, por lo que se
 * retira una vez por carga sin observar mutaciones globales del documento.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
			return;
		}
		?>
		<style id="elmercado-native-filter-remove-01096">
			html body.elmercado-child-theme:not(.wcfmmp-store-page) .woostify-sorting.woostify-sorting > button.filter.filter,
			html body.elmercado-child-theme:not(.wcfmmp-store-page) .woostify-sorting.woostify-sorting > a.filter.filter,
			html body.elmercado-child-theme:not(.wcfmmp-store-page) .woostify-sorting.woostify-sorting button.filter.filter.show,
			html body.elmercado-child-theme:not(.wcfmmp-store-page) .woostify-sorting.woostify-sorting a.filter.filter.show {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				width: 0 !important;
				height: 0 !important;
				min-width: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
				pointer-events: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
			return;
		}
		?>
		<script id="elmercado-native-filter-remove-js-01096">
		(() => {
			'use strict';
			const selector = '.woostify-sorting button.filter, .woostify-sorting a.filter, .woostify-sorting .filter.show';
			const removeNativeFilter = () => {
				document.querySelectorAll(selector).forEach((control) => {
					if (control.id === 'emo-premium-filter-toggle' || control.classList.contains('emo-mobile-filter-toggle')) return;
					control.remove();
				});
			};

			removeNativeFilter();
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', removeNativeFilter, { once: true });
			}
			window.addEventListener('pageshow', removeNativeFilter, { passive: true });
			window.addEventListener('popstate', removeNativeFilter, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
