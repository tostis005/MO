<?php
/**
 * Cierre de paridad de filtros para las tiendas de productor.
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
		<script id="elmercado-vendor-store-parity-final-010226">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;
			const sentence = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa';
			const normalized = (value) => (value || '').replace(/\s+/g, ' ').trim().replace(/[.!?…]+$/u, '');
			let scheduled = false;

			function apply() {
				scheduled = false;
				store.querySelectorAll('p').forEach((node) => {
					if (normalized(node.textContent) === sentence) node.remove();
				});

				const sidebar = store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225');
				const panel = sidebar && sidebar.querySelector('#emo-vendor-filters');
				if (!sidebar || !panel) return;

				const context = panel.querySelector('#emo-vendor-category-context');
				const price = panel.querySelector('.emo-vendor-price-filter');
				const categories = panel.querySelector('#emo-vendor-category-filter');
				const mobileHead = panel.querySelector('.emo-vendor-filters__mobile-head');

				if (context && price) {
					panel.insertBefore(context, mobileHead ? mobileHead.nextSibling : panel.firstChild);
					panel.insertBefore(price, context.nextSibling);
				} else if (!context && price && categories && price.nextElementSibling !== categories) {
					panel.insertBefore(categories, price.nextSibling);
				}

				if (context) {
					context.querySelector('.emo-vendor-category-context__eyebrow')?.remove();
					const remove = context.querySelector('.emo-vendor-category-context__row a');
					if (remove && !remove.querySelector('[aria-hidden="true"]')) {
						remove.innerHTML = '<span aria-hidden="true">×</span><span>Quitar</span>';
					}
				}

				if (window.matchMedia('(min-width:1101px)').matches) {
					sidebar.style.setProperty('width', '250px', 'important');
					sidebar.style.setProperty('min-width', '250px', 'important');
					sidebar.style.setProperty('max-width', '250px', 'important');
					sidebar.style.setProperty('height', 'auto', 'important');
					sidebar.style.setProperty('padding', '18px', 'important');
					sidebar.style.setProperty('border', '1px solid rgba(23,63,50,.11)', 'important');
					sidebar.style.setProperty('border-radius', '18px', 'important');
					sidebar.style.setProperty('background', '#fff', 'important');
					sidebar.style.setProperty('box-shadow', '0 12px 32px rgba(17,42,34,.07)', 'important');
					sidebar.style.setProperty('transform', 'none', 'important');
				} else {
					const open = document.documentElement.classList.contains('emo-vendor-filters-open-010225');
					sidebar.style.setProperty('transform', open ? 'translateX(0)' : 'translateX(105%)', 'important');
					sidebar.style.setProperty('background', '#fff', 'important');
				}
			}

			function sync() {
				if (scheduled) return;
				scheduled = true;
				requestAnimationFrame(apply);
			}

			new MutationObserver(sync).observe(document.documentElement, { attributes:true, attributeFilter:['class'] });
			new MutationObserver(sync).observe(store, { childList:true, subtree:true, characterData:true });
			window.addEventListener('resize', sync, { passive:true });
			window.addEventListener('pageshow', sync, { passive:true });
			document.addEventListener('click', sync);
			sync();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
