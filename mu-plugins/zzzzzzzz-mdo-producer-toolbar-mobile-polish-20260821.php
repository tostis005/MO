<?php
/**
 * Plugin Name: MDO Producer Toolbar Mobile Polish
 * Description: Final mobile parity for producer toolbar controls and destination modal.
 * Version: 2.1.0
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
		/* Producer destination modal: exact final main-shop close treatment. */
		html body > .mdo-ps-modal { z-index:2147483646 !important; }
		html body > .mdo-ps-modal .mdo-ps-modal__close {
			top:10px !important;
			right:10px !important;
			box-sizing:border-box !important;
			width:42px !important;
			height:42px !important;
			min-width:42px !important;
			min-height:42px !important;
			margin:0 !important;
			padding:0 !important;
			border:0 !important;
			border-radius:50% !important;
			background:#f3f6f4 !important;
			color:#173f32 !important;
			box-shadow:none !important;
			font-family:inherit !important;
			font-size:25px !important;
			font-weight:400 !important;
			line-height:40px !important;
			text-align:center !important;
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

		@media (max-width:600px) {
			html body > .mdo-ps-modal .mdo-ps-modal__panel h2 { font-size:20px !important; }
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
			}

			/* Never clip glyph descenders inside Enviar a / Shipping to. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__label,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__value,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
				box-sizing:border-box !important;
				height:auto !important;
				min-height:0 !important;
				max-height:none !important;
				margin:0 !important;
				padding:0 !important;
				line-height:1.25 !important;
				overflow:visible !important;
				vertical-align:middle !important;
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
				background:transparent !important;
				box-shadow:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::before,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::after {
				content:none !important;
				display:none !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > span,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > .select2,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > .select2-container {
				display:none !important;
				visibility:hidden !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select.orderby {
				position:relative !important;
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
				-webkit-appearance:none !important;
				appearance:none !important;
				background-color:#f8faf8 !important;
				background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
				background-repeat:no-repeat !important;
				background-position:right 12px center !important;
				background-size:12px 8px !important;
				border:1px solid rgba(23,63,50,.15) !important;
				border-radius:999px !important;
				box-shadow:none !important;
				font-size:11.75px !important;
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
