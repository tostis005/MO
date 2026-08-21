<?php
/**
 * Plugin Name: MDO Producer Toolbar Mobile Polish
 * Description: Final mobile parity for producer toolbar controls and destination modal.
 * Version: 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_toolbar_mobile_polish_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_producer_toolbar_static_guard_is_store_20260821' ) ) {
		return mdo_producer_toolbar_static_guard_is_store_20260821();
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) ) {
		return mdo_ps_toolbar_ux_is_store_20260821();
	}
	return function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page();
}

/**
 * The static guard's broad MutationObserver can move/clean the ordering form while
 * a native mobile select is opening. Keep its CSS/legacy cleanup, but disable only
 * that repeated footer DOM observer on producer stores. The toolbar UX has already
 * mounted the canonical structure before footer output.
 */
function mdo_producer_toolbar_mobile_polish_disable_observer_20260821(): void {
	if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
		return;
	}
	remove_action(
		'wp_footer',
		'mdo_producer_toolbar_static_guard_structure_script_20260821',
		PHP_INT_MAX
	);
}
add_action( 'wp', 'mdo_producer_toolbar_mobile_polish_disable_observer_20260821', PHP_INT_MAX );

function mdo_producer_toolbar_mobile_polish_css_20260821(): void {
	if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
		return;
	}
	?>
	<style id="mdo-producer-toolbar-mobile-polish-20260821">
		/*
		 * Producer destination modal: mirror the final main-shop root modal, not the
		 * older generic destination popup. This keeps backdrop, sheet geometry and
		 * close control identical on both catalogue surfaces.
		 */
		html body > .mdo-ps-modal[hidden] { display:none !important; }
		html body > .mdo-ps-modal {
			position:fixed !important;
			inset:0 !important;
			z-index:2147483646 !important;
			display:flex !important;
			align-items:center !important;
			justify-content:center !important;
			box-sizing:border-box !important;
			width:100vw !important;
			height:100dvh !important;
			max-width:none !important;
			max-height:none !important;
			margin:0 !important;
			padding:20px !important;
			isolation:isolate !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__backdrop {
			position:absolute !important;
			inset:0 !important;
			z-index:0 !important;
			background:rgba(13,26,21,.52) !important;
			backdrop-filter:blur(3px) !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__panel {
			position:relative !important;
			z-index:1 !important;
			box-sizing:border-box !important;
			width:min(100%,448px) !important;
			max-height:calc(100dvh - 40px) !important;
			overflow:auto !important;
			padding:30px !important;
			border-radius:18px !important;
			background:#fff !important;
			color:#173f32 !important;
			box-shadow:0 28px 90px rgba(0,0,0,.28) !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__panel h2 {
			margin-right:48px !important;
			color:#173f32 !important;
		}
		html body > .mdo-ps-modal form { margin:0 !important; }
		html body > .mdo-ps-modal select,
		html body > .mdo-ps-modal input { box-shadow:none !important; }
		html body > .mdo-ps-modal select:focus,
		html body > .mdo-ps-modal input:focus {
			border-color:#658a7d !important;
			outline:2px solid rgba(23,63,50,.08) !important;
			outline-offset:1px !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__error { line-height:1.4 !important; }

		/* Exact final main-shop close treatment: transparent hit area + drawn X. */
		html body > .mdo-ps-modal .mdo-ps-modal__close {
			position:absolute !important;
			top:10px !important;
			right:10px !important;
			z-index:2 !important;
			display:grid !important;
			place-items:center !important;
			box-sizing:border-box !important;
			width:42px !important;
			height:42px !important;
			min-width:42px !important;
			min-height:42px !important;
			margin:0 !important;
			padding:0 !important;
			border:0 !important;
			border-radius:999px !important;
			background:transparent !important;
			box-shadow:none !important;
			color:#173f32 !important;
			font-size:0 !important;
			line-height:1 !important;
			cursor:pointer !important;
			transition:background-color .15s ease !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__close:hover,
		html body > .mdo-ps-modal .mdo-ps-modal__close:focus-visible {
			background:rgba(23,63,50,.075) !important;
			outline:none !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__close::before,
		html body > .mdo-ps-modal .mdo-ps-modal__close::after {
			content:"" !important;
			position:absolute !important;
			left:50% !important;
			top:50% !important;
			width:18px !important;
			height:1.6px !important;
			border-radius:999px !important;
			background:#173f32 !important;
			transform-origin:center !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__close::before {
			transform:translate(-50%,-50%) rotate(45deg) !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__close::after {
			transform:translate(-50%,-50%) rotate(-45deg) !important;
		}

		@media (max-width:600px) {
			html body > .mdo-ps-modal {
				align-items:flex-end !important;
				padding:0 !important;
			}
			html body > .mdo-ps-modal .mdo-ps-modal__panel {
				width:100% !important;
				max-height:min(86dvh,760px) !important;
				padding:28px 20px 22px !important;
				border-radius:20px 20px 0 0 !important;
			}
			html body > .mdo-ps-modal .mdo-ps-modal__panel h2 {
				margin-right:46px !important;
				font-size:20px !important;
			}
		}

		@media (max-width:640px) {
			/* Same 40px destination control as the global shop. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger {
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 12px 0 13px !important;
				font-size:11.75px !important;
				line-height:1.2 !important;
				overflow:visible !important;
				pointer-events:auto !important;
				clip:auto !important;
				clip-path:none !important;
				transform:none !important;
			}

			/* Never clip glyph descenders inside Envío a / Shipping to. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__label,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__value,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
				position:relative !important;
				box-sizing:border-box !important;
				display:block !important;
				height:auto !important;
				min-height:0 !important;
				max-height:none !important;
				margin:0 !important;
				padding:1px 0 2px !important;
				line-height:1.3 !important;
				overflow:visible !important;
				clip:auto !important;
				clip-path:none !important;
				vertical-align:middle !important;
				transform:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__chevron,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:last-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:last-child {
				pointer-events:none !important;
			}

			/* One border and one arrow only; mirror the global shop's actual select. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering {
				position:relative !important;
				z-index:20 !important;
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
				isolation:isolate !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::before,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::after {
				content:none !important;
				display:none !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > .select2,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > .select2-container,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > span.select2 {
				display:none !important;
				visibility:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select.orderby,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select.select2-hidden-accessible {
				position:relative !important;
				inset:auto !important;
				z-index:21 !important;
				display:block !important;
				visibility:visible !important;
				opacity:1 !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				padding:0 36px 0 12px !important;
				clip:auto !important;
				clip-path:none !important;
				overflow:visible !important;
				white-space:nowrap !important;
				-webkit-appearance:none !important;
				appearance:none !important;
				background-color:#f8faf8 !important;
				background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
				background-repeat:no-repeat !important;
				background-position:right 12px center !important;
				background-size:12px 8px !important;
				border:1px solid rgba(23,63,50,.15) !important;
				border-radius:999px !important;
				outline:0 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:11.75px !important;
				font-weight:700 !important;
				line-height:1.2 !important;
				pointer-events:auto !important;
				cursor:pointer !important;
				touch-action:manipulation !important;
			}
		}
	</style>
	<?php
}

add_action( 'wp_head', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );
add_action( 'wp_footer', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );

/**
 * Select2 may leave WooCommerce's native ordering select with its accessibility
 * hiding class even after the generated Select2 control has been visually removed.
 * On touch devices that means the pill is visible but the real select is still
 * clipped to 1px. Restore the native control once, without observing/rebuilding it
 * while the browser picker is open.
 */
function mdo_producer_toolbar_mobile_polish_script_20260821(): void {
	if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
		return;
	}
	?>
	<script id="mdo-producer-toolbar-mobile-polish-js-20260821">
	(() => {
		'use strict';
		const store=document.querySelector('#wcfmmp-store');
		if(!store) return;
		const mobile=()=>window.matchMedia('(max-width:640px)').matches;

		const restoreNativeOrdering=()=>{
			if(!mobile()) return;
			const form=store.querySelector('.woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering');
			if(!form) return;
			const select=form.querySelector(':scope > select.orderby, :scope > select');
			if(!select) return;

			select.classList.remove('select2-hidden-accessible');
			select.removeAttribute('aria-hidden');
			if(select.getAttribute('tabindex')==='-1') select.removeAttribute('tabindex');
			if(select.disabled) select.disabled=false;
			select.style.removeProperty('clip');
			select.style.removeProperty('clip-path');

			form.querySelectorAll(':scope > .select2, :scope > .select2-container, :scope > span.select2').forEach(node=>{
				node.setAttribute('aria-hidden','true');
				node.style.setProperty('display','none','important');
				node.style.setProperty('pointer-events','none','important');
			});
		};

		restoreNativeOrdering();
		document.addEventListener('DOMContentLoaded',restoreNativeOrdering,{once:true});
		window.addEventListener('pageshow',restoreNativeOrdering,{passive:true});
		window.addEventListener('resize',restoreNativeOrdering,{passive:true});
		requestAnimationFrame(restoreNativeOrdering);
		setTimeout(restoreNativeOrdering,250);
		setTimeout(restoreNativeOrdering,900);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_producer_toolbar_mobile_polish_script_20260821', PHP_INT_MAX );
