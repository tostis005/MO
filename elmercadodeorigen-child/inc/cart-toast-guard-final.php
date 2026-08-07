<?php
/**
 * Evita confirmaciones de carrito espurias en cargas o refrescos de fragmentos.
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
		<script id="elmercado-cart-toast-guard-final">
		(() => {
			'use strict';

			let lastAddIntent = 0;
			const intentWindow = 6000;

			const markIntent = (target) => {
				if (!(target instanceof Element)) return;
				if (!target.closest('.add_to_cart_button, .single_add_to_cart_button, [name="add-to-cart"]')) return;
				lastAddIntent = Date.now();
			};

			document.addEventListener('pointerdown', (event) => markIntent(event.target), true);
			document.addEventListener('click', (event) => markIntent(event.target), true);
			document.addEventListener('submit', (event) => {
				if (event.target instanceof Element && event.target.matches('form.cart')) {
					lastAddIntent = Date.now();
				}
			}, true);

			const hasRecentIntent = () => Date.now() - lastAddIntent <= intentWindow;
			const guard = (scope = document) => {
				const toasts = [];
				if (scope instanceof Element && scope.matches('.emo-cart-toast')) toasts.push(scope);
				if (scope.querySelectorAll) toasts.push(...scope.querySelectorAll('.emo-cart-toast'));
				if (hasRecentIntent()) return;
				toasts.forEach((toast) => toast.remove());
			};

			/* Limpia cualquier confirmación heredada antes del primer repintado útil. */
			guard();
			new MutationObserver((mutations) => {
				if (hasRecentIntent()) return;
				for (const mutation of mutations) {
					for (const node of mutation.addedNodes) {
						if (node.nodeType === Node.ELEMENT_NODE) guard(node);
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
