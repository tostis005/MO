<?php
/**
 * Plugin Name: MDO Catalogue Control Widths 2026-08-28
 * Description: Presentation-only refinement for destination hierarchy, desktop sizing and full-width mobile catalogue controls.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_control_widths_is_surface_20260828(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

/**
 * Critical first-paint styling for the destination trigger.
 *
 * The historical catalogue layers finish styling the pill in wp_footer. Keep
 * the final visual treatment in wp_head so there is no dark first-paint flash.
 * Geometry remains owned by the existing responsive catalogue layer.
 */
function mdo_catalog_control_widths_critical_style_20260828(): void {
	if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
		return;
	}
	?>
	<style id="mdo-catalog-control-widths-critical-20260828">
		html body [data-mdo-destination-open],
		html body [data-mdo-ps-destination-open] {
			background:#f1f6f2 !important;
			background-color:#f1f6f2 !important;
			color:#173f32 !important;
			border:1px solid rgba(23,63,50,.22) !important;
			border-radius:999px !important;
			box-shadow:0 1px 2px rgba(17,42,34,.025) !important;
			-webkit-appearance:none !important;
			appearance:none !important;
		}
		html body [data-mdo-destination-open]:hover,
		html body [data-mdo-destination-open]:focus-visible,
		html body [data-mdo-ps-destination-open]:hover,
		html body [data-mdo-ps-destination-open]:focus-visible {
			background:#eaf2ed !important;
			background-color:#eaf2ed !important;
			border-color:rgba(23,63,50,.34) !important;
			color:#173f32 !important;
		}

		/* A quiet location cue makes destination read as catalogue state rather
		 * than as another sorting control. Purely decorative; behaviour unchanged. */
		html body [data-mdo-destination-open]::before,
		html body [data-mdo-ps-destination-open]::before {
			content:"" !important;
			display:block !important;
			box-sizing:border-box !important;
			width:13px !important;
			height:13px !important;
			min-width:13px !important;
			margin:0 !important;
			padding:0 !important;
			border:0 !important;
			background-color:currentColor !important;
			-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			opacity:.72 !important;
			pointer-events:none !important;
	}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_catalog_control_widths_critical_style_20260828', PHP_INT_MAX );

function mdo_catalog_control_widths_output_20260828(): void {
	if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
		return;
	}
	?>
	<script id="mdo-catalog-control-widths-20260828">
	(() => {
		'use strict';

		const set = (el, props) => {
			if (!el) return;
			Object.entries(props).forEach(([name, value]) => el.style.setProperty(name, value, 'important'));
		};
		const directFlexItem = (root, node) => {
			if (!root || !node || !root.contains(node)) return null;
			let item = node;
			while (item && item.parentElement && item.parentElement !== root) item = item.parentElement;
			return item && item.parentElement === root ? item : null;
		};

		const apply = () => {
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			if (!toolbar) return false;

			const left = toolbar.querySelector('.woostify-toolbar-left');
			const count = toolbar.querySelector('.woocommerce-result-count');
			const destTrigger = toolbar.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destWrap = destTrigger?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destTrigger?.parentElement || null;
			const destItem = directFlexItem(left, destTrigger);
			const countItem = directFlexItem(left, count) || count;
			const orderForm = toolbar.querySelector('.woocommerce-ordering');
			const order = orderForm?.querySelector('select[name="orderby"]') || null;
			if (!left || !count || !destTrigger || !destWrap || !destItem || !orderForm || !order) return false;

			const mobile = window.matchMedia('(max-width:640px)').matches;
			const desktop = window.matchMedia('(min-width:901px)').matches;
			const text = destTrigger.querySelector('span');

			/* Copy reads from the left on both catalogue surfaces. */
			set(destTrigger, {'text-align':'left'});
			set(text, {'text-align':'left'});

			/* Desktop/tablet hierarchy: result count first, destination immediately
			 * after it. Ordering remains the isolated control on the far right. */
			if (!mobile) {
				set(countItem, {'order':'-1'});
				set(destItem, {'order':'0'});
			}

			/* Destination gets a third grid cell for the location cue while retaining
			 * the exact same trailing chevron geometry as ordering. */
			set(destTrigger, {
				'grid-template-columns':'13px minmax(0,1fr) 16px',
				'column-gap':'8px',
				'background':'#f1f6f2',
				'background-color':'#f1f6f2',
				'border-color':'rgba(23,63,50,.22)',
				'box-shadow':'0 1px 2px rgba(17,42,34,.025)'
			});

			/* On desktop the destination control sizes to its content instead of
			 * inheriting the historical fixed 248px control width. */
			if (desktop) {
				set(destItem, {
					'flex':'0 1 auto',
					'width':'auto',
					'min-width':'0',
					'max-width':'320px',
					'margin':'0'
				});
				set(destWrap, {
					'flex':'0 1 auto',
					'width':'auto',
					'min-width':'0',
					'max-width':'320px',
					'margin':'0',
					'justify-self':'start',
					'align-self':'center'
				});
				set(destTrigger, {
					'width':'auto',
					'min-width':'0',
					'max-width':'320px',
					'justify-self':'start'
				});
			}

			/* Once the toolbar enters the mobile stacked layout, both controls own
			 * the complete inner width: count, destination, ordering. */
			if (mobile) {
				set(destItem, {
					'order':'initial',
					'width':'100%',
					'min-width':'0',
					'max-width':'100%',
					'margin':'0'
				});
				set(countItem, {'order':'initial'});
				set(destWrap, {
					'order':'initial',
					'flex':'0 0 100%',
					'width':'100%',
					'min-width':'0',
					'max-width':'100%',
					'margin':'0',
					'justify-self':'stretch',
					'align-self':'stretch'
				});
				set(destTrigger, {
					'width':'100%',
					'min-width':'0',
					'max-width':'100%',
					'justify-self':'stretch'
				});
				set(orderForm, {
					'flex':'0 0 100%',
					'width':'100%',
					'min-width':'0',
					'max-width':'100%',
					'margin':'0',
					'justify-self':'stretch',
					'align-self':'stretch'
				});
				set(order, {
					'width':'100%',
					'min-width':'0',
					'max-width':'100%'
				});
			}
			return true;
		};

		let raf = 0;
		const schedule = () => {
			if (raf) cancelAnimationFrame(raf);
			raf = requestAnimationFrame(() => {
				raf = requestAnimationFrame(() => {
					raf = 0;
					apply();
				});
			});
		};

		/* The historical parity owner also writes inline-important styles. Run
		 * just after it on initial load and after each responsive reflow. */
		setTimeout(apply, 0);
		setTimeout(apply, 150);
		window.addEventListener('load', schedule, {passive:true});
		window.addEventListener('pageshow', schedule, {passive:true});
		window.addEventListener('resize', schedule, {passive:true});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_catalog_control_widths_output_20260828', PHP_INT_MAX );
