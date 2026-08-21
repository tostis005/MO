<?php
/**
 * Retire the conflicted native mobile vendor ordering control once the
 * reliable 0.10.272 popover is present.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) { return; }
		?>
		<script id="elmercado-vendor-ordering-native-retire-010274">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;
			const mq = window.matchMedia('(max-width: 991px)');
			const retire = () => {
				if (!mq.matches) return;
				document.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering').forEach(form => {
					const select = form.querySelector('select');
					const button = form.querySelector('.mdo-vendor-order-button');
					if (!(select instanceof HTMLSelectElement) || !button) return;
					select.setAttribute('aria-hidden', 'true');
					select.tabIndex = -1;
					const props = {
						position:'absolute', left:'0', top:'0', width:'1px', height:'1px', minWidth:'1px', minHeight:'1px', maxWidth:'1px', maxHeight:'1px',
						margin:'0', padding:'0', border:'0', opacity:'0', visibility:'hidden', pointerEvents:'none', overflow:'hidden', clipPath:'inset(50%)', zIndex:'-1'
					};
					Object.entries(props).forEach(([key,value]) => select.style.setProperty(key.replace(/[A-Z]/g,m=>'-'+m.toLowerCase()), value, 'important'));
				});
			};
			retire();
			window.addEventListener('load', retire, { once:true });
			new MutationObserver(m => { if (m.some(x => x.type === 'childList')) requestAnimationFrame(retire); }).observe(document.body,{childList:true,subtree:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
