<?php
/**
 * Inicialización estable del filtro de precio 0.10.90.
 *
 * WooCommerce expone el evento init_price_filter para construir su slider.
 * Si una carga deja el widget renderizado pero sin jQuery UI inicializado,
 * reutilizamos ese evento oficial. No se realizan mediciones, transforms ni
 * ciclos requestAnimationFrame.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-price-filter-init-final-01090">
		(() => {
			'use strict';

			const pageIsCatalog = document.body && (
				document.body.classList.contains('woocommerce-shop') ||
				document.body.classList.contains('tax-product_cat') ||
				document.body.classList.contains('tax-product_tag')
			);
			if (!pageIsCatalog) return;

			const ensurePriceFilter = () => {
				const roots = [...document.querySelectorAll('.widget_price_filter')];
				const needsInit = roots.some((root) => {
					const slider = root.querySelector('.price_slider');
					return !!slider && !slider.classList.contains('ui-slider');
				});
				if (!needsInit || !window.jQuery) return;
				window.jQuery(document.body).trigger('init_price_filter');
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', ensurePriceFilter, { once: true });
			} else {
				ensurePriceFilter();
			}
			window.addEventListener('load', ensurePriceFilter, { once: true });
			document.addEventListener('click', (event) => {
				if (event.target.closest('#emo-premium-filter-toggle')) ensurePriceFilter();
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);