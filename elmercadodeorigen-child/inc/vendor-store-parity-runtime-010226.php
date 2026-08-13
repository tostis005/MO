<?php
/**
 * Runtime final para la paridad de filtros de las tiendas de productor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-store-parity-runtime-010226">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;
			const target = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa';
			let frame = 0;
			const normalize = (value) => (value || '').replace(/\s+/g, ' ').trim().replace(/[.!?…]+$/u, '');
			const sync = () => {
				if (frame) cancelAnimationFrame(frame);
				frame = requestAnimationFrame(() => {
					frame = 0;
					store.querySelectorAll('p').forEach((node) => {
						if (normalize(node.textContent) === target) node.remove();
					});
					const sidebar = store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225');
					if (!sidebar || !window.matchMedia('(max-width:1100px)').matches) return;
					const open = document.documentElement.classList.contains('emo-vendor-filters-open-010225');
					sidebar.style.setProperty('transform', open ? 'translateX(0)' : 'translateX(105%)', 'important');
				});
			};
			new MutationObserver(sync).observe(document.documentElement, { attributes:true, attributeFilter:['class'] });
			new MutationObserver(sync).observe(store, { childList:true, subtree:true, characterData:true });
			document.addEventListener('click', (event) => {
				if (event.target.closest('.emo-vendor-filter-toggle-010225, .emo-vendor-filters__close, .emo-vendor-filter-overlay-010225')) requestAnimationFrame(sync);
			});
			window.addEventListener('resize', sync, { passive:true });
			window.addEventListener('pageshow', sync, { passive:true });
			sync();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
