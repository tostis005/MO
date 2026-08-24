<?php
/**
 * Plugin Name: MDO Catalog Top Controls Parity Final Owner
 * Description: Final presentation owner for identical destination and ordering controls on global and producer catalogues. Uses inline-important geometry to safely outrank historical catalogue CSS without changing behaviour.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_parity_final_is_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_is_surface_20260824' ) ) {
		return mdo_catalog_top_controls_parity_is_surface_20260824();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function mdo_catalog_top_controls_parity_final_output_20260824(): void {
	if ( ! mdo_catalog_top_controls_parity_final_is_surface_20260824() ) {
		return;
	}
	?>
	<style id="mdo-catalog-top-controls-parity-final-20260824">
		/* The ID is assigned by the script below. This removes historical theme
		 * pseudo-arrows so the native ordering pill has exactly one chevron. */
		#mdo-catalog-parity-final-20260824 > .woocommerce-ordering::before,
		#mdo-catalog-parity-final-20260824 > .woocommerce-ordering::after,
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering::before,
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
			content:none !important;
			display:none !important;
		}
		#mdo-catalog-parity-final-20260824 .mdo-catalog-destination__trigger:hover,
		#mdo-catalog-parity-final-20260824 .mdo-catalog-destination__trigger:focus-visible,
		#mdo-catalog-parity-final-20260824 .mdo-ps-destination__trigger:hover,
		#mdo-catalog-parity-final-20260824 .mdo-ps-destination__trigger:focus-visible {
			background:#f2f6f3 !important;
			border-color:rgba(23,63,50,.30) !important;
			box-shadow:0 0 0 3px rgba(23,63,50,.055) !important;
			outline:none !important;
		}
	</style>
	<script id="mdo-catalog-top-controls-parity-final-20260824-js">
	(() => {
		'use strict';

		const ARROW = 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%278%27 viewBox=%270 0 12 8%27%3E%3Cpath d=%27M1 1.5 6 6.5 11 1.5%27 fill=%27none%27 stroke=%27%23173f32%27 stroke-width=%271.5%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E")';
		const set = (el, props) => {
			if (!el) return;
			Object.entries(props).forEach(([name, value]) => el.style.setProperty(name, value, 'important'));
		};
		const direct = (root, selector) => {
			if (!root) return null;
			try { return root.querySelector(`:scope > ${selector}`); }
			catch (e) { return root.querySelector(selector); }
		};

		const pill = (el, mobile, isOrder = false) => {
			set(el, {
				'display': isOrder ? 'block' : 'grid',
				'grid-template-columns': isOrder ? 'none' : 'minmax(0,1fr) 16px',
				'column-gap': isOrder ? '0' : '9px',
				'visibility':'visible',
				'opacity':'1',
				'box-sizing':'border-box',
				'width':'100%',
				'min-width':'0',
				'max-width':'100%',
				'height': mobile ? '40px' : '42px',
				'min-height': mobile ? '40px' : '42px',
				'max-height': mobile ? '40px' : '42px',
				'align-items':'center',
				'margin':'0',
				'padding': isOrder ? (mobile ? '0 36px 0 13px' : '0 36px 0 13px') : '0 13px',
				'border':'1px solid rgba(23,63,50,.15)',
				'border-radius':'999px',
				'background-color':'#f8faf8',
				'box-shadow':'none',
				'color':'#173f32',
				'font-family':'inherit',
				'font-size': mobile ? '11.75px' : '12.5px',
				'font-weight':'700',
				'letter-spacing':'0',
				'line-height':'1',
				'white-space':'nowrap',
				'cursor':'pointer',
				'pointer-events':'auto',
			});
			if (isOrder) {
				set(el, {
					'-webkit-appearance':'none',
					'appearance':'none',
					'background-image':ARROW,
					'background-repeat':'no-repeat',
					'background-position': mobile ? 'right 12px center' : 'right 13px center',
					'background-size':'12px 8px',
				});
			}
		};

		const apply = () => {
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			if (!toolbar) return false;

			toolbar.id = 'mdo-catalog-parity-final-20260824';
			toolbar.dataset.mdoCatalogParityFinal = '20260824-v2';
			const mobile = window.matchMedia('(max-width:640px)').matches;

			let left = direct(toolbar, '.woostify-toolbar-left') || toolbar.querySelector('.woostify-toolbar-left');
			const count = toolbar.querySelector('.woocommerce-result-count');
			const orderForm = direct(toolbar, '.woocommerce-ordering') || toolbar.querySelector('.woocommerce-ordering');
			const order = orderForm?.querySelector('select[name="orderby"]') || null;
			const destTrigger = toolbar.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destWrap = destTrigger?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destTrigger?.parentElement || null;

			if (!left || !count || !orderForm || !order || !destTrigger || !destWrap) return false;

			set(toolbar, {
				'display': mobile ? 'grid' : 'flex',
				'grid-template-columns': mobile ? 'minmax(0,1fr)' : 'none',
				'grid-template-rows': mobile ? 'auto 40px 40px' : 'none',
				'box-sizing':'border-box',
				'width':'100%',
				'min-width':'0',
				'max-width':'100%',
				'height':'auto',
				'min-height': mobile ? '0' : '68px',
				'max-height':'none',
				'align-items': mobile ? 'stretch' : 'center',
				'justify-content':'space-between',
				'justify-items':'stretch',
				'gap': mobile ? '8px' : '18px',
				'overflow':'visible',
				'margin': mobile ? '0 0 12px' : '0 0 16px',
				'padding': mobile ? '11px' : '12px 14px',
				'border':'1px solid rgba(23,63,50,.11)',
				'border-radius': mobile ? '15px' : '16px',
				'background':'#fff',
				'box-shadow':'0 10px 28px rgba(17,42,34,.055)',
			});

			set(left, mobile ? {
				'display':'contents',
				'visibility':'visible',
				'opacity':'1',
			} : {
				'display':'flex',
				'visibility':'visible',
				'opacity':'1',
				'position':'static',
				'box-sizing':'border-box',
				'flex':'1 1 auto',
				'width':'auto',
				'min-width':'0',
				'max-width':'none',
				'height':'42px',
				'min-height':'42px',
				'max-height':'42px',
				'align-items':'center',
				'gap':'15px',
				'overflow':'visible',
				'margin':'0',
				'padding':'0',
				'float':'none',
				'transform':'none',
			});

			set(count, {
				'display':'inline-flex',
				'visibility':'visible',
				'opacity':'1',
				'position':'static',
				'box-sizing':'border-box',
				'grid-column': mobile ? '1' : 'auto',
				'grid-row': mobile ? '1' : 'auto',
				'width': mobile ? '100%' : 'auto',
				'min-width':'0',
				'height': mobile ? '18px' : '42px',
				'min-height': mobile ? '18px' : '42px',
				'max-height': mobile ? '18px' : '42px',
				'align-items':'center',
				'margin':'0',
				'padding':'0 2px',
				'float':'none',
				'color':'#53665f',
				'font-family':'inherit',
				'font-size': mobile ? '11px' : '12.5px',
				'font-weight':'700',
				'line-height':'1.25',
				'white-space':'nowrap',
			});

			set(destWrap, {
				'display':'flex',
				'visibility':'visible',
				'opacity':'1',
				'position':'static',
				'box-sizing':'border-box',
				'grid-column': mobile ? '1' : 'auto',
				'grid-row': mobile ? '2' : 'auto',
				'flex': mobile ? 'none' : '0 0 248px',
				'width': mobile ? '100%' : '248px',
				'min-width': mobile ? '0' : '248px',
				'max-width': mobile ? '100%' : '248px',
				'height': mobile ? '40px' : '42px',
				'min-height': mobile ? '40px' : '42px',
				'max-height': mobile ? '40px' : '42px',
				'align-items':'center',
				'margin':'0',
				'padding':'0',
				'float':'none',
				'transform':'none',
			});
			pill(destTrigger, mobile, false);

			const firstDirectSvg = direct(destTrigger, 'svg:first-child');
			const pin = destTrigger.querySelector('.mdo-catalog-destination__pin');
			set(firstDirectSvg, {'display':'none'});
			set(pin, {'display':'none'});
			const text = direct(destTrigger, 'span') || destTrigger.querySelector('span');
			set(text, {
				'display':'block','min-width':'0','overflow':'hidden','text-overflow':'ellipsis','white-space':'nowrap','line-height':'1.2'
			});
			const strong = text?.querySelector('strong');
			set(strong, {'color':'inherit','font-weight':'760'});
			const allSvgs = [...destTrigger.querySelectorAll('svg')];
			const chevron = destTrigger.querySelector('.mdo-catalog-destination__chevron') || allSvgs[allSvgs.length - 1] || null;
			set(chevron, {
				'display':'block','align-self':'center','justify-self':'center','width':'12px','height':'8px','min-width':'12px','max-width':'12px','margin':'0','padding':'0','opacity':'.72','pointer-events':'none'
			});
			if (chevron && chevron.tagName !== 'svg' && chevron.tagName !== 'SVG') {
				set(chevron.querySelector('svg'), {'display':'block','width':'12px','height':'8px','margin':'0'});
			}

			set(orderForm, {
				'display':'flex',
				'visibility':'visible',
				'opacity':'1',
				'position':'static',
				'box-sizing':'border-box',
				'grid-column': mobile ? '1' : 'auto',
				'grid-row': mobile ? '3' : 'auto',
				'flex': mobile ? 'none' : '0 0 248px',
				'width': mobile ? '100%' : '248px',
				'min-width': mobile ? '0' : '248px',
				'max-width': mobile ? '100%' : '248px',
				'height': mobile ? '40px' : '42px',
				'min-height': mobile ? '40px' : '42px',
				'max-height': mobile ? '40px' : '42px',
				'align-items':'center',
				'margin': mobile ? '0' : '0 0 0 auto',
				'padding':'0',
				'border':'0',
				'border-radius':'0',
				'background':'transparent',
				'box-shadow':'none',
				'float':'none',
				'clear':'none',
				'transform':'none',
				'overflow':'visible',
			});
			pill(order, mobile, true);
			return true;
		};

		let observer = null;
		const run = () => {
			if (apply() && observer) {
				observer.disconnect();
				observer = null;
			}
		};
		run();
		if (!document.querySelector('[data-mdo-catalog-parity-final="20260824-v2"]')) {
			observer = new MutationObserver(run);
			observer.observe(document.documentElement, {childList:true, subtree:true});
			window.setTimeout(() => { if (observer) observer.disconnect(); observer = null; }, 6000);
		}
		window.addEventListener('pageshow', run, {passive:true});
		window.addEventListener('resize', run, {passive:true});
	})();
	</script>
	<?php
}

/* Loaded after the first parity owner; register at the same late stage so this
 * output is appended last among the catalogue presentation layers. */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_top_controls_parity_final_is_surface_20260824() ) {
			return;
		}
		add_action( 'wp_footer', 'mdo_catalog_top_controls_parity_final_output_20260824', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
