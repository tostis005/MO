<?php
/**
 * Bloqueo final del orden visual del sidebar de categoría 0.10.198.
 *
 * Algunas reglas históricas mantienen #secondary en display:block. Aplicamos
 * el layout final en línea para que las propiedades order sean efectivas y no
 * puedan ser anuladas por esa cascada heredada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-filter-layout-lock-010198">
		(() => {
			'use strict';

			const lock = () => {
				const context = document.getElementById('emo-category-context');
				const vendor = document.getElementById('emo-global-vendor-filter');
				const specific = document.getElementById('emo-category-attribute-filters');
				const sidebar = context?.parentElement || vendor?.parentElement || specific?.parentElement;
				if (!(sidebar instanceof Element)) return;

				const price = Array.from(sidebar.children).find((child) =>
					child.matches?.('.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter') ||
					child.querySelector?.('.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter')
				) || null;

				sidebar.style.setProperty('display', 'flex', 'important');
				sidebar.style.setProperty('flex-direction', 'column', 'important');
				sidebar.style.setProperty('flex-wrap', 'nowrap', 'important');

				if (context) context.style.setProperty('order', '0', 'important');
				if (price) price.style.setProperty('order', '1', 'important');
				if (vendor) vendor.style.setProperty('order', '2', 'important');
				if (specific) specific.style.setProperty('order', '3', 'important');

				const appliedSlot = document.getElementById('emo-category-applied-filters-slot-010196');
				if (appliedSlot) appliedSlot.style.setProperty('order', '3', 'important');
			};

			lock();
			requestAnimationFrame(lock);
			setTimeout(lock, 300);
			setTimeout(lock, 900);
			setTimeout(lock, 1900);
			window.addEventListener('pageshow', lock, { passive:true });
			window.addEventListener('resize', lock, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
