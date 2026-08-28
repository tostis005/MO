<?php
/**
 * Plugin Name: MDO Catalogue Control Widths 2026-08-28
 * Description: Presentation-only refinement for destination alignment/desktop sizing and full-width mobile catalogue controls.
 * Version: 1.0.1
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

		const apply = () => {
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			if (!toolbar) return false;

			const count = toolbar.querySelector('.woocommerce-result-count');
			const destTrigger = toolbar.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destWrap = destTrigger?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destTrigger?.parentElement || null;
			const orderForm = toolbar.querySelector('.woocommerce-ordering');
			const order = orderForm?.querySelector('select[name="orderby"]') || null;
			if (!count || !destTrigger || !destWrap || !orderForm || !order) return false;

			const mobile = window.matchMedia('(max-width:640px)').matches;
			const desktop = window.matchMedia('(min-width:901px)').matches;
			const text = destTrigger.querySelector('span');

			/* Destination copy always reads from the left in both catalogue surfaces. */
			set(destTrigger, {'text-align':'left'});
			set(text, {'text-align':'left'});

			/* In every non-stacked layout, destination is the first control in the
			 * left group. That makes its left edge identical on global/vendor shops,
			 * regardless of the different result-count text lengths. */
			if (!mobile) {
				set(destWrap, {'order':'-1'});
				set(count, {'order':'0'});
			}

			/* On desktop the destination control should size to its content rather
			 * than inheriting the historical fixed 248px control width. */
			if (desktop) {
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
			 * the complete inner width. This removes the narrow ordering state. */
			if (mobile) {
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
				set(count, {'order':'initial'});
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
