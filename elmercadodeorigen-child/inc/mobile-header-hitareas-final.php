<?php
/**
 * Iguala las áreas interactivas reales de buscar, cuenta y carrito en móvil.
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
		<script id="elmercado-mobile-header-hitareas-final">
		(() => {
			'use strict';
			const tools = document.querySelector('.site-header .site-tools');
			if (!tools) return;

			const mobile = () => window.matchMedia('(max-width: 991px)').matches;
			const important = (node, property, value) => node?.style?.setProperty(property, value, 'important');
			const visible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
			};

			const sync = () => {
				if (!mobile()) return;
				important(tools, 'grid-template-columns', 'repeat(3, 30px)');
				important(tools, 'grid-auto-columns', '30px');
				important(tools, 'gap', '4px');
				important(tools, 'width', '98px');
				important(tools, 'min-width', '98px');

				[...tools.children].filter(visible).slice(0, 3).forEach((cell) => {
					for (const node of [cell]) {
						important(node, 'width', '30px');
						important(node, 'min-width', '30px');
						important(node, 'max-width', '30px');
						important(node, 'height', '30px');
						important(node, 'min-height', '30px');
						important(node, 'margin', '0');
						important(node, 'padding', '0');
						important(node, 'align-self', 'center');
						important(node, 'justify-self', 'center');
						important(node, 'transform', 'none');
					}

					const targets = [];
					if (cell.matches('a,button,[role="button"]')) targets.push(cell);
					targets.push(...cell.querySelectorAll('a,button,[role="button"]'));
					targets.forEach((node) => {
						important(node, 'display', 'grid');
						important(node, 'width', '30px');
						important(node, 'min-width', '30px');
						important(node, 'max-width', '30px');
						important(node, 'height', '30px');
						important(node, 'min-height', '30px');
						important(node, 'max-height', '30px');
						important(node, 'margin', '0');
						important(node, 'padding', '0');
						important(node, 'place-items', 'center');
						important(node, 'border-radius', '999px');
						important(node, 'box-shadow', 'none');
						important(node, 'line-height', '1');
						important(node, 'transform', 'none');
					});
				});
			};

			const schedule = () => requestAnimationFrame(sync);
			sync();
			window.addEventListener('load', schedule, { once: true });
			window.addEventListener('resize', schedule, { passive: true });
			setTimeout(sync, 250);
			setTimeout(sync, 900);
			if (window.jQuery) {
				window.jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart', schedule);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
