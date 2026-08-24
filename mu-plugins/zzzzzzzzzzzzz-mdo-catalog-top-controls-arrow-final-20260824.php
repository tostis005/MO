<?php
/**
 * Plugin Name: MDO Catalog Top Controls Arrow Final Owner
 * Description: Renders the ordering chevron as a non-blocking CSS pseudo-element so the native select remains fully clickable on every browser, and keeps the producer mobile toolbar aligned with the global shop.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_arrow_final_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_is_surface_20260824' ) ) {
		return mdo_catalog_top_controls_parity_is_surface_20260824();
	}
	return function_exists( 'is_shop' ) && is_shop();
}

function mdo_catalog_top_controls_arrow_final_output_20260824(): void {
	if ( ! mdo_catalog_top_controls_arrow_final_surface_20260824() ) {
		return;
	}
	?>
	<style id="mdo-catalog-top-controls-arrow-final-20260824">
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering {
			position:relative !important;
		}
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering::before {
			content:none !important;
			display:none !important;
		}
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
			content:"" !important;
			display:block !important;
			position:absolute !important;
			top:50% !important;
			right:15px !important;
			left:auto !important;
			width:7px !important;
			height:7px !important;
			margin:-5px 0 0 !important;
			padding:0 !important;
			border:0 !important;
			border-right:1.5px solid #173f32 !important;
			border-bottom:1.5px solid #173f32 !important;
			background:transparent !important;
			box-shadow:none !important;
			opacity:.72 !important;
			transform:rotate(45deg) !important;
			transform-origin:center !important;
			pointer-events:none !important;
			z-index:2 !important;
		}
		@media (max-width:640px) {
			#mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
				right:14px !important;
			}
			/* Producer store pages inherit a narrower WCFM content column. Expand only
			 * this controls card to the exact 16px viewport gutters used by /tienda/. */
			body.wcfmmp-store-page #mdo-catalog-parity-final-20260824,
			body.wcfm-store-page #mdo-catalog-parity-final-20260824 {
				position:relative !important;
				left:50% !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				transform:translateX(-50%) !important;
			}
		}
	</style>
	<script id="mdo-catalog-top-controls-arrow-final-20260824-js">
	(() => {
		'use strict';
		let observedSelect = null;
		let observer = null;

		const isProducerMobile = (toolbar) => !!toolbar?.querySelector('[data-mdo-ps-destination-open]') && window.matchMedia('(max-width:640px)').matches;

		const enforceProducerOrderPadding = (toolbar, select) => {
			if (!toolbar || !select || !isProducerMobile(toolbar)) return;
			if (getComputedStyle(select).paddingRight !== '36px' || select.style.getPropertyPriority('padding-right') !== 'important') {
				select.style.setProperty('padding-right', '36px', 'important');
			}
		};

		const watchProducerOrder = (toolbar, select) => {
			if (!toolbar || !select || !isProducerMobile(toolbar)) {
				observer?.disconnect();
				observer = null;
				observedSelect = null;
				return;
			}
			if (observedSelect === select && observer) return;
			observer?.disconnect();
			observedSelect = select;
			observer = new MutationObserver(() => enforceProducerOrderPadding(toolbar, select));
			observer.observe(select, {attributes:true, attributeFilter:['style','class']});
		};

		const fix = () => {
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			const form = toolbar?.querySelector('.woocommerce-ordering');
			const select = form?.querySelector('select[name="orderby"]');
			if (!toolbar || !form || !select) return false;
			form.style.setProperty('position', 'relative', 'important');
			select.style.setProperty('background-image', 'none', 'important');
			select.style.setProperty('background-repeat', 'no-repeat', 'important');
			select.style.setProperty('padding-right', '36px', 'important');
			select.style.setProperty('pointer-events', 'auto', 'important');
			select.style.setProperty('-webkit-appearance', 'none', 'important');
			select.style.setProperty('appearance', 'none', 'important');

			const producer = !!toolbar.querySelector('[data-mdo-ps-destination-open]');
			const mobile = window.matchMedia('(max-width:640px)').matches;
			if (producer && mobile) {
				toolbar.style.setProperty('position', 'relative', 'important');
				toolbar.style.setProperty('left', '50%', 'important');
				toolbar.style.setProperty('width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('min-width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('max-width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('transform', 'translateX(-50%)', 'important');
				toolbar.dataset.mdoProducerMobileWidthParity = '20260824-v4';
				enforceProducerOrderPadding(toolbar, select);
				watchProducerOrder(toolbar, select);
			} else {
				toolbar.style.removeProperty('left');
				toolbar.style.removeProperty('transform');
				delete toolbar.dataset.mdoProducerMobileWidthParity;
				watchProducerOrder(null, null);
			}

			toolbar.dataset.mdoCatalogArrowFinal = '20260824-v3';
			return true;
		};

		const schedule = () => {
			fix();
			requestAnimationFrame(() => requestAnimationFrame(fix));
			window.setTimeout(fix, 250);
			window.setTimeout(fix, 1200);
			window.setTimeout(fix, 2100);
			window.setTimeout(fix, 3200);
		};
		schedule();
		window.addEventListener('DOMContentLoaded', schedule, {once:true});
		window.addEventListener('load', schedule, {once:true});
		window.addEventListener('pageshow', schedule, {passive:true});
		window.addEventListener('resize', fix, {passive:true});
	})();
	</script>
	<?php
}

add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_top_controls_arrow_final_surface_20260824() ) {
			return;
		}
		add_action( 'wp_footer', 'mdo_catalog_top_controls_arrow_final_output_20260824', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
