<?php
/**
 * Plugin Name: MDO Producer Ordering Shop Parity
 * Description: Makes the producer-store native WooCommerce ordering control use the exact final mobile geometry of the main shop.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_ordering_shop_parity_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_producer_toolbar_mobile_polish_is_store_20260821' ) ) {
		return mdo_producer_toolbar_mobile_polish_is_store_20260821();
	}
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) ) {
		return mdo_ps_safe_is_store_20260821();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) ) {
		return wcfmmp_is_store_page();
	}
	return (bool) get_query_var( 'store' );
}

function mdo_producer_ordering_shop_parity_css_20260821(): void {
	if ( ! mdo_producer_ordering_shop_parity_is_store_20260821() ) {
		return;
	}
	?>
	<style id="mdo-producer-ordering-shop-parity-20260821">
		@media (max-width:640px) {
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woocommerce-ordering,
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering {
				position:relative !important;
				inset:auto !important;
				display:flex !important;
				box-sizing:border-box !important;
				justify-self:center !important;
				width:calc(100vw - 58px) !important;
				min-width:calc(100vw - 58px) !important;
				max-width:calc(100vw - 58px) !important;
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
				transform:none !important;
				pointer-events:auto !important;
				transition:none !important;
				animation:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::before,
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering::after {
				content:none !important;
				display:none !important;
				pointer-events:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > :is(.select2,.select2-container,.chosen-container,.nice-select,.selectize-control) {
				display:none !important;
				visibility:hidden !important;
				opacity:0 !important;
				pointer-events:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select,
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select.orderby,
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering > select.select2-hidden-accessible {
				position:relative !important;
				inset:auto !important;
				z-index:3 !important;
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
				padding-top:0 !important;
				padding-bottom:0 !important;
				padding-left:12px !important;
				padding-right:42px !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				outline:0 !important;
				background-color:#f8faf8 !important;
				background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
				background-repeat:no-repeat !important;
				background-position:right 12px center !important;
				background-size:12px 8px !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:11.75px !important;
				font-weight:700 !important;
				letter-spacing:0 !important;
				line-height:1 !important;
				-webkit-appearance:none !important;
				appearance:none !important;
				clip:auto !important;
				clip-path:none !important;
				pointer-events:auto !important;
				touch-action:manipulation !important;
				cursor:pointer !important;
				transition:none !important;
				animation:none !important;
			}
		}
	</style>
	<?php
}

/* Loaded lexically after every producer toolbar compatibility layer. Output at
 * head and footer so first paint and the final cascade use the same contract. */
add_action( 'wp_head', 'mdo_producer_ordering_shop_parity_css_20260821', PHP_INT_MAX );
add_action( 'wp_footer', 'mdo_producer_ordering_shop_parity_css_20260821', PHP_INT_MAX );
