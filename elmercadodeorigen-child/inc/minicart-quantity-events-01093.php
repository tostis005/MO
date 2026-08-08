<?php
/**
 * Normalización del control de cantidad del mini-carrito mediante eventos.
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
		<script id="elmercado-minicart-quantity-events-01093">
		(() => {
			'use strict';
			const normalize = () => {
				document.querySelectorAll('#shop-cart-sidebar .quantity, #shop-cart-sidebar .mini-cart-quantity').forEach((control) => {
					const input = control.querySelector('input.qty');
					if (!input) return;
					const children = [...control.children];
					const buttons = children.filter((child) => child !== input && !child.contains(input));
					if (buttons.length < 2) return;
					const minus = buttons[0];
					const plus = buttons[buttons.length - 1];
					buttons.slice(1, -1).forEach((extra) => extra.remove());
					minus.textContent = '−';
					plus.textContent = '+';
					minus.setAttribute('aria-label', 'Reducir cantidad');
					plus.setAttribute('aria-label', 'Aumentar cantidad');
					input.type = 'text';
					input.inputMode = 'numeric';
					input.setAttribute('pattern', '[0-9]*');
					input.setAttribute('aria-label', 'Cantidad');
				});
			};
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', normalize, { once:true });
			else normalize();
			window.addEventListener('pageshow', normalize);
			document.addEventListener('click', (event) => {
				if (event.target.closest('.shopping-cart,.shopping-bag-button,#shop-cart-sidebar')) window.setTimeout(normalize, 0);
			}, true);
			if (window.jQuery) {
				window.jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart updated_wc_div updated_cart_totals', () => window.setTimeout(normalize, 0));
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
