<?php
/**
 * Plugin Name: MDO Catalog Top Controls Parity
 * Description: Gives the global shop and producer stores one shared visual contract for destination and native ordering controls without changing catalogue queries.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_top_controls_parity_is_store_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	return (bool) get_query_var( 'store' );
}

function mdo_catalog_top_controls_parity_is_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	return mdo_catalog_top_controls_parity_is_store_20260824();
}

/**
 * This is deliberately a late presentation layer. It never changes product
 * queries, destination data or WooCommerce ordering values.
 */
function mdo_catalog_top_controls_parity_output_20260824(): void {
	if ( ! mdo_catalog_top_controls_parity_is_surface_20260824() ) {
		return;
	}
	?>
	<style id="mdo-catalog-top-controls-parity-20260824">
		/* One toolbar contract for the global shop and every producer store. */
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 {
			display:flex !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:auto !important;
			min-height:68px !important;
			max-height:none !important;
			align-items:center !important;
			justify-content:space-between !important;
			gap:18px !important;
			overflow:visible !important;
			margin:0 0 16px !important;
			padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;
			border-radius:16px !important;
			background:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:1 1 auto !important;
			width:auto !important;
			min-width:0 !important;
			max-width:none !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			gap:15px !important;
			overflow:visible !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
			display:inline-flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 0 auto !important;
			width:auto !important;
			min-width:0 !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			overflow:visible !important;
			margin:0 !important;
			padding:0 2px !important;
			float:none !important;
			color:#53665f !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			line-height:1.35 !important;
			white-space:nowrap !important;
		}

		/* Destination wrappers have the same geometry on both catalogue types. */
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 1 248px !important;
			width:clamp(190px,22vw,248px) !important;
			min-width:170px !important;
			max-width:248px !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}

		/* Destination and ordering are intentionally the same pill. */
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger {
			display:grid !important;
			grid-template-columns:minmax(0,1fr) 16px !important;
			column-gap:9px !important;
			visibility:visible !important;
			opacity:1 !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 13px !important;
			border:1px solid rgba(23,63,50,.15) !important;
			border-radius:999px !important;
			background:#f8faf8 !important;
			box-shadow:none !important;
			color:#173f32 !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			line-height:1 !important;
			white-space:nowrap !important;
			cursor:pointer !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger:hover,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger:focus-visible {
			background:#f2f6f3 !important;
			border-color:rgba(23,63,50,.30) !important;
			box-shadow:0 0 0 3px rgba(23,63,50,.055) !important;
			color:#173f32 !important;
			outline:none !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger > svg:first-child,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger > svg:first-child,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__pin {
			display:none !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger > span,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger > span {
			display:block !important;
			min-width:0 !important;
			overflow:hidden !important;
			text-overflow:ellipsis !important;
			white-space:nowrap !important;
			line-height:1.2 !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger strong,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger strong {
			color:inherit !important;
			font-weight:760 !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger > svg:last-child {
			display:block !important;
			align-self:center !important;
			justify-self:center !important;
			width:12px !important;
			height:8px !important;
			min-width:12px !important;
			max-width:12px !important;
			margin:0 !important;
			padding:0 !important;
			opacity:.72 !important;
			pointer-events:none !important;
		}

		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:0 1 248px !important;
			width:clamp(190px,22vw,248px) !important;
			min-width:170px !important;
			max-width:248px !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			margin:0 0 0 auto !important;
			padding:0 !important;
			border:0 !important;
			border-radius:0 !important;
			background:transparent !important;
			box-shadow:none !important;
			float:none !important;
			clear:none !important;
			transform:none !important;
			overflow:visible !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::before,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after {
			content:none !important;
			display:none !important;
		}
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
		html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby {
			display:block !important;
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			max-width:100% !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			margin:0 !important;
			padding:0 36px 0 13px !important;
			border:1px solid rgba(23,63,50,.15) !important;
			border-radius:999px !important;
			-webkit-appearance:none !important;
			appearance:none !important;
			background-color:#f8faf8 !important;
			background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
			background-repeat:no-repeat !important;
			background-position:right 13px center !important;
			background-size:12px 8px !important;
			box-shadow:none !important;
			color:#173f32 !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:700 !important;
			letter-spacing:0 !important;
			line-height:1 !important;
			cursor:pointer !important;
			pointer-events:auto !important;
		}

		/* Phones: same two-dropdown row in shop and producer stores. */
		@media (max-width:640px) {
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;
				grid-template-rows:auto 40px !important;
				align-items:center !important;
				justify-items:stretch !important;
				column-gap:8px !important;
				row-gap:9px !important;
				width:100% !important;
				height:auto !important;
				min-height:0 !important;
				margin:0 0 12px !important;
				padding:11px !important;
				border-radius:15px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:contents !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
				grid-column:1 / -1 !important;
				grid-row:1 !important;
				width:100% !important;
				height:18px !important;
				min-height:18px !important;
				max-height:18px !important;
				padding:0 2px !important;
				font-size:11px !important;
				line-height:1.25 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
				grid-column:1 !important;
				grid-row:2 !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination__trigger {
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 10px 0 11px !important;
				font-size:11.25px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:2 !important;
				grid-row:2 !important;
				justify-self:stretch !important;
				flex:none !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby {
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 28px 0 10px !important;
				font-size:11.25px !important;
				background-position:right 10px center !important;
			}
		}

		@media (max-width:350px) {
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 {
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto 40px 40px !important;
				row-gap:8px !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
				grid-column:1 !important;
				grid-row:2 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:1 !important;
				grid-row:3 !important;
			}
		}

		/* Producer destination dialog uses the same close-control scale as shop. */
		html body > .mdo-ps-modal {
			z-index:2147483646 !important;
		}
		html body > .mdo-ps-modal .mdo-ps-modal__close {
			width:42px !important;
			height:42px !important;
			min-width:42px !important;
			min-height:42px !important;
			top:10px !important;
			right:10px !important;
		}
	</style>
	<script id="mdo-catalog-top-controls-parity-20260824-js">
	(() => {
		'use strict';
		const store = document.querySelector('#wcfmmp-store');
		if (!store) return;

		const mount = () => {
			const toolbar = store.querySelector('.elmercado-vendor-toolbar,.woostify-sorting');
			const destination = document.querySelector('[data-mdo-ps-destination-trigger-wrap]');
			if (!toolbar || !destination) return false;

			toolbar.classList.add('emo-catalog-toolbar-shared-010229');

			let left = null;
			try {
				left = toolbar.querySelector(':scope > .woostify-toolbar-left');
			} catch (error) {
				left = toolbar.querySelector('.woostify-toolbar-left');
			}
			if (!left) {
				left = document.createElement('div');
				left.className = 'woostify-toolbar-left elmercado-vendor-filter-hidden';
				toolbar.insertBefore(left, toolbar.firstChild);
			}

			const counts = [...toolbar.querySelectorAll('.woocommerce-result-count')];
			const count = counts.find((node) => !node.closest('[hidden]')) || counts[0] || null;
			if (count && count.parentElement !== left) left.insertBefore(count, left.firstChild);
			if (destination.parentElement !== left) left.appendChild(destination);

			const ordering = toolbar.querySelector('.woocommerce-ordering') || store.querySelector('.woocommerce-ordering');
			if (ordering && ordering.parentElement !== toolbar) toolbar.appendChild(ordering);
			return !!ordering;
		};

		if (!mount()) {
			const observer = new MutationObserver(() => {
				if (mount()) observer.disconnect();
			});
			observer.observe(store, {childList:true, subtree:true});
			window.setTimeout(() => observer.disconnect(), 5000);
		}
		window.addEventListener('pageshow', mount, {passive:true});
	})();
	</script>
	<?php
}

/* Register after historical PHP_INT_MAX footer layers so this is the final owner. */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_top_controls_parity_is_surface_20260824() ) {
			return;
		}
		add_action( 'wp_footer', 'mdo_catalog_top_controls_parity_output_20260824', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
