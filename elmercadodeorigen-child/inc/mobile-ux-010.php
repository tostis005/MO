<?php
/** Mobile UX corrections for release 0.10.0. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', static function (): void {
	if ( is_admin() ) { return; }
	?>
	<style id="elmercado-mobile-ux-010">
		.elmercado-child-theme ul.products li.product .star-rating,
		.elmercado-child-theme ul.products li.product .woocommerce-product-rating { display:none!important; }

		@media (max-width:991px) {
			.elmercado-child-theme .site-header-inner>.woostify-container {
				display:grid!important;grid-template-columns:44px minmax(0,1fr) auto!important;
				align-items:center!important;gap:6px!important;padding-inline:12px!important;
			}
			.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
				grid-column:1!important;width:44px!important;height:44px!important;min-width:44px!important;
				margin:0!important;padding:0!important;align-items:center!important;justify-content:center!important;
			}
			.elmercado-child-theme .site-header .site-branding {grid-column:2!important;min-width:0!important;margin:0!important;}
			.elmercado-child-theme .site-header .site-branding a {max-width:100%!important;white-space:nowrap!important;}
			.elmercado-child-theme .site-header .site-tools {
				grid-column:3!important;display:grid!important;grid-auto-flow:column!important;
				grid-auto-columns:44px!important;align-items:center!important;justify-content:end!important;
				gap:6px!important;margin:0!important;
			}
			.elmercado-child-theme .site-header .site-tools>.header-search-icon,
			.elmercado-child-theme .site-header .site-tools>a.tools-icon,
			.elmercado-child-theme .site-header .site-tools>.my-account,
			.elmercado-child-theme .site-header .site-tools>.my-account>a.tools-icon {
				display:grid!important;width:44px!important;height:44px!important;min-width:44px!important;
				margin:0!important;padding:0!important;place-items:center!important;
			}

			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"]) {
				position:absolute!important;top:14px!important;right:14px!important;display:grid!important;
				width:44px!important;height:44px!important;min-width:44px!important;margin:0!important;padding:0!important;
				place-items:center!important;border:0!important;border-radius:50%!important;background:#173f32!important;
				color:transparent!important;font-size:0!important;line-height:0!important;z-index:20!important;
			}
			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"])>* {display:none!important;}
			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"])::before,
			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"])::after {
				content:""!important;position:absolute!important;width:20px!important;height:2px!important;
				border:0!important;background:#fff!important;transform-origin:center!important;
			}
			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"])::before {transform:rotate(45deg)!important;}
			.elmercado-child-theme .sidebar-menu :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"])::after {transform:rotate(-45deg)!important;}

			.elmercado-premium-home .emo-featured-products ul.products {
				display:flex!important;grid-template-columns:none!important;gap:14px!important;width:auto!important;
				margin-inline:-16px!important;padding:4px 16px 18px!important;overflow-x:auto!important;
				overflow-y:visible!important;scroll-snap-type:x mandatory!important;scroll-padding-inline:16px!important;
				-webkit-overflow-scrolling:touch!important;scrollbar-width:none!important;
			}
			.elmercado-premium-home .emo-featured-products ul.products::-webkit-scrollbar {display:none!important;}
			.elmercado-premium-home .emo-featured-products ul.products>li.product {
				flex:0 0 min(82vw,330px)!important;width:min(82vw,330px)!important;max-width:min(82vw,330px)!important;
				margin:0!important;scroll-snap-align:start!important;scroll-snap-stop:always!important;
			}
		}

		.elmercado-child-theme .site-header :is(.shopping-cart,.shopping-bag-button) {position:relative!important;}
		.elmercado-child-theme .site-header :is(.shopping-cart-count,.cart-count,.mini-cart-count) {
			position:absolute!important;top:0!important;right:-1px!important;display:grid!important;min-width:17px!important;
			height:17px!important;padding:0 4px!important;place-items:center!important;border:2px solid #fff!important;
			border-radius:999px!important;background:#d6a738!important;color:#173f32!important;font-size:10px!important;
			font-weight:800!important;line-height:1!important;visibility:visible!important;opacity:1!important;z-index:4!important;
		}
	</style>
	<?php
}, PHP_INT_MAX );

add_action( 'wp_footer', static function (): void {
	if ( is_admin() ) { return; }
	?>
	<script id="elmercado-cart-toast-010">
	(() => {
		'use strict';
		let cartActionAt = 0;
		const recent = () => Date.now() - cartActionAt < 5000;
		const matches = (el) => {
			if (!(el instanceof HTMLElement)) return false;
			const text = (el.textContent || '').toLowerCase();
			return text.includes('producto añadido al carrito') &&
				(el.matches('.woocommerce-message,.woocommerce-notice,[class*="toast"],[class*="snackbar"]') || ['fixed','sticky'].includes(getComputedStyle(el).position));
		};
		const clear = (root = document) => {
			if (recent()) return;
			root.querySelectorAll?.('.woocommerce-message,.woocommerce-notice,[class*="toast"],[class*="snackbar"]').forEach((el) => {
				if (matches(el)) el.remove();
			});
		};
		if (window.jQuery) window.jQuery(document.body).on('adding_to_cart added_to_cart', () => { cartActionAt = Date.now(); });
		const observer = new MutationObserver((records) => {
			if (recent()) return;
			records.forEach((record) => record.addedNodes.forEach((node) => {
				if (!(node instanceof HTMLElement)) return;
				if (matches(node)) node.remove(); else clear(node);
			}));
		});
		document.addEventListener('DOMContentLoaded', () => {
			setTimeout(clear, 50); setTimeout(clear, 800);
			observer.observe(document.body, {childList:true,subtree:true});
		});
	})();
	</script>
	<?php
}, PHP_INT_MAX );
