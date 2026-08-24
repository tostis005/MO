<?php
/**
 * Plugin Name: MDO Catalog Top Controls Arrow Final Owner
 * Description: Renders non-blocking catalogue chevrons and keeps the producer mobile catalogue aligned with the global shop without changing behaviour.
 * Version: 1.3.7
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

			/* WCFM adds a second mobile gutter. Only the producer catalogue surfaces
			 * escape that gutter so they use the same 16px viewport margins as /tienda/. */
			body.wcfmmp-store-page #mdo-catalog-parity-final-20260824,
			body.wcfm-store-page #mdo-catalog-parity-final-20260824,
			body.wcfmmp-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			body.wcfm-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			body.wcfmmp-store-page #wcfmmp-store ul.products,
			body.wcfm-store-page #wcfmmp-store ul.products {
				position:relative !important;
				left:50% !important;
				box-sizing:border-box !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				margin-left:0 !important;
				margin-right:0 !important;
				transform:translateX(-50%) !important;
			}

			/* The live producer product list receives transform:none!important from an
			 * inline WCFM rule. Position it with left alone so the result stays CSS-only:
			 * 50% of the WCFM content width minus 50vw plus the shop's 16px gutter. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store ul.products {
				position:relative !important;
				left:calc(50% - 50vw + 16px) !important;
				box-sizing:border-box !important;
				width:calc(100vw - 32px) !important;
				min-width:calc(100vw - 32px) !important;
				max-width:calc(100vw - 32px) !important;
				margin-left:0 !important;
				margin-right:0 !important;
				transform:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products > li.product,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store ul.products > li.product {
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				margin-left:0 !important;
				margin-right:0 !important;
			}

			/* Producer controls keep their internal trigger/select at full wrapper width. */
			body.wcfmmp-store-page #mdo-catalog-parity-final-20260824 .mdo-ps-destination__trigger,
			body.wcfm-store-page #mdo-catalog-parity-final-20260824 .mdo-ps-destination__trigger,
			body.wcfmmp-store-page #mdo-catalog-parity-final-20260824 select[name="orderby"],
			body.wcfm-store-page #mdo-catalog-parity-final-20260824 select[name="orderby"] {
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
			}

			/* The ordering arrow remains decorative and centred. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store #mdo-catalog-parity-final-20260824 .woocommerce-ordering::after,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store#wcfmmp-store #mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
				top:50% !important;
				right:15px !important;
				width:8px !important;
				height:8px !important;
				margin:0 !important;
				transform:translateY(-65%) rotate(45deg) !important;
				pointer-events:none !important;
			}
		}
	</style>
	<script id="mdo-catalog-top-controls-arrow-final-20260824-js">
	(() => {
		'use strict';
		let observedSelect = null;
		let observer = null;

		const isMobile = () => window.matchMedia('(max-width:640px)').matches;
		const isProducerMobile = (toolbar) => !!toolbar?.querySelector('[data-mdo-ps-destination-open]') && isMobile();
		const setImportant = (el, name, value) => el?.style?.setProperty(name, value, 'important');

		const enforceProducerOrderPadding = (toolbar, select) => {
			if (!toolbar || !select || !isProducerMobile(toolbar)) return;
			if (getComputedStyle(select).paddingRight !== '36px' || select.style.getPropertyPriority('padding-right') !== 'important') {
				select.style.setProperty('padding-right', '36px', 'important');
			}
		};

		const enforceMobileFullWidthControls = (toolbar, form, select) => {
			if (!toolbar || !form || !select || !isMobile()) return;
			const destination = toolbar.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destinationWrap = destination?.closest('.mdo-catalog-destination,.mdo-ps-destination') || destination?.parentElement;
			const width = Math.max(0, window.innerWidth - 32);
			const pin = (el) => {
				if (!el) return;
				setImportant(el, 'position', 'relative');
				setImportant(el, 'box-sizing', 'border-box');
				setImportant(el, 'transform', 'none');
				setImportant(el, 'width', `${width}px`);
				setImportant(el, 'min-width', `${width}px`);
				setImportant(el, 'max-width', `${width}px`);
				setImportant(el, 'margin-left', '0');
				setImportant(el, 'margin-right', '0');
				setImportant(el, 'left', '0px');
				const rect = el.getBoundingClientRect();
				setImportant(el, 'left', `${16 - rect.left}px`);
			};
			pin(destinationWrap);
			pin(form);
			setImportant(destination, 'box-sizing', 'border-box');
			setImportant(destination, 'width', '100%');
			setImportant(destination, 'min-width', '0');
			setImportant(destination, 'max-width', '100%');
			setImportant(select, 'box-sizing', 'border-box');
			setImportant(select, 'width', '100%');
			setImportant(select, 'min-width', '0');
			setImportant(select, 'max-width', '100%');
			toolbar.dataset.mdoMobileControlsFullWidth = '20260824-v1';
		};

		const styleProducerDestination = (toolbar) => {
			if (!isProducerMobile(toolbar)) return;
			const trigger = toolbar.querySelector('[data-mdo-ps-destination-open]');
			if (!trigger) return;
			const svgs = [...trigger.querySelectorAll(':scope > svg')];
			const pin = svgs[0] || null;
			const chevron = svgs[svgs.length - 1] || null;
			const text = trigger.querySelector(':scope > span');

			setImportant(trigger, 'position', 'relative');
			setImportant(trigger, 'display', 'flex');
			setImportant(trigger, 'align-items', 'center');
			setImportant(trigger, 'justify-content', 'center');
			setImportant(trigger, 'width', '100%');
			setImportant(trigger, 'min-width', '0');
			setImportant(trigger, 'max-width', '100%');
			setImportant(trigger, 'padding', '0 13px');
			setImportant(pin, 'display', 'none');
			setImportant(text, 'display', 'block');
			setImportant(text, 'width', '100%');
			setImportant(text, 'min-width', '0');
			setImportant(text, 'text-align', 'center');
			if (chevron && chevron !== pin) {
				setImportant(chevron, 'display', 'block');
				setImportant(chevron, 'position', 'absolute');
				setImportant(chevron, 'top', '50%');
				setImportant(chevron, 'right', '9px');
				setImportant(chevron, 'left', 'auto');
				setImportant(chevron, 'width', '20px');
				setImportant(chevron, 'height', '20px');
				setImportant(chevron, 'min-width', '20px');
				setImportant(chevron, 'max-width', '20px');
				setImportant(chevron, 'margin', '0');
				setImportant(chevron, 'padding', '0');
				setImportant(chevron, 'opacity', '.72');
				setImportant(chevron, 'transform', 'translateY(-50%)');
				setImportant(chevron, 'transform-origin', 'center');
				setImportant(chevron, 'pointer-events', 'none');
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
			const mobile = isMobile();
			if (producer && mobile) {
				toolbar.style.setProperty('position', 'relative', 'important');
				toolbar.style.setProperty('left', '50%', 'important');
				toolbar.style.setProperty('width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('min-width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('max-width', 'calc(100vw - 32px)', 'important');
				toolbar.style.setProperty('transform', 'translateX(-50%)', 'important');
				toolbar.dataset.mdoProducerMobileWidthParity = '20260824-v4';
				enforceProducerOrderPadding(toolbar, select);
				styleProducerDestination(toolbar);
				watchProducerOrder(toolbar, select);
			} else {
				toolbar.style.removeProperty('left');
				toolbar.style.removeProperty('transform');
				delete toolbar.dataset.mdoProducerMobileWidthParity;
				watchProducerOrder(null, null);
			}

			if (mobile) {
				enforceMobileFullWidthControls(toolbar, form, select);
			} else {
				delete toolbar.dataset.mdoMobileControlsFullWidth;
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
