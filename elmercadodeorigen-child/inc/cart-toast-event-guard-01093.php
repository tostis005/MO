<?php
/**
 * Guardia de confirmaciones de carrito basada en intención y eventos WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) return;
		?>
		<script id="elmercado-cart-toast-event-guard-01093">
		(() => {
			'use strict';
			let lastAddIntent = 0;
			const intentWindow = 6000;
			const markIntent = (target) => {
				if (!(target instanceof Element)) return;
				if (target.closest('.add_to_cart_button,.single_add_to_cart_button,[name="add-to-cart"]')) lastAddIntent = Date.now();
			};
			const hasRecentIntent = () => Date.now() - lastAddIntent <= intentWindow;
			const guard = () => {
				if (hasRecentIntent()) return;
				document.querySelectorAll('.emo-cart-toast').forEach((toast) => toast.remove());
				[...document.querySelectorAll('.woocommerce-message,.woocommerce-notice,[class*="toast"],[class*="snackbar"]')].forEach((element) => {
					const text = (element.textContent || '').toLowerCase();
					if (text.includes('producto añadido al carrito')) element.remove();
				});
			};
			document.addEventListener('pointerdown', (event) => markIntent(event.target), true);
			document.addEventListener('click', (event) => markIntent(event.target), true);
			document.addEventListener('submit', (event) => {
				if (event.target instanceof Element && event.target.matches('form.cart')) lastAddIntent = Date.now();
			}, true);
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', guard, { once:true });
			else guard();
			window.addEventListener('pageshow', guard);
			window.addEventListener('load', guard, { once:true });
			[80,700].forEach((delay) => window.setTimeout(guard, delay));
			if (window.jQuery) {
				window.jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed updated_wc_div updated_cart_totals removed_from_cart', () => {
					window.setTimeout(guard, 0);
					window.setTimeout(guard, 120);
				});
				window.jQuery(document.body).on('adding_to_cart added_to_cart', () => { lastAddIntent = Date.now(); });
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
