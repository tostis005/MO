<?php
/**
 * Oculta de forma definitiva el selector de productor de la tienda.
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
