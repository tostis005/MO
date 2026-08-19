<?php
/**
 * Home-only accessibility, motion and drawer stability 0.10.269.
 *
 * Keeps the review proof legible, limits custom Home card transitions to
 * compositor-friendly properties and prevents Woostify navigation/cart drawers
 * and their dark overlays from flashing during a hard refresh.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_home_a11y_motion_is_front_010265(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_home_a11y_motion_is_front_010265() ) {
			return;
		}
		?>
		<style id="elmercado-home-a11y-motion-010269">
			/* Lighthouse: review-count copy must pass normal-text contrast. */
			body.home .emo-home .mdo-review-proof small {
				color: #666 !important;
			}

			/* Keep the above-the-fold LCP copy immediately paintable. */
			body.home .emo-hero,
			body.home .emo-hero__grid,
			body.home .emo-hero__copy,
			body.home .emo-hero__copy > p {
				content-visibility: visible !important;
			}

			/* Avoid broad transition:all behaviour on the custom Home cards. */
			body.home .emo-home .emo-hero-card,
			body.home .emo-home .emo-category-card,
			body.home .emo-home .products .product,
			body.home .emo-home .emo-story__values article {
				transition-property: transform, opacity !important;
			}

			/* Editorial mobile menu backdrop: hidden until an actual menu click. */
			body.home .emo-mobile-menu-overlay[aria-hidden="true"],
			html:not([data-emo-menu-intent="1"]) body.home .emo-mobile-menu-overlay {
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
				transition: none !important;
			}

			/*
			 * Woostify cart drawer and shared #woostify-overlay.
			 * The parent stylesheet is deferred on Home, so both elements need a
			 * closed critical state before that stylesheet arrives. The open state is
			 * only allowed after a real cart/add-to-cart interaction.
			 */
			body.home #shop-cart-sidebar {
				position: fixed !important;
				top: 0 !important;
				right: 0 !important;
				bottom: 0 !important;
				left: auto !important;
				width: min(400px, 100vw) !important;
				max-width: 100vw !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
				transform: translate3d(105%, 0, 0) !important;
				transition: none !important;
				z-index: 200 !important;
			}

			body.home #woostify-overlay,
			html:not([data-emo-cart-intent="1"]) body.home #woostify-overlay {
				position: fixed !important;
				inset: 0 !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
				transition: none !important;
				z-index: 199 !important;
			}

			html[data-emo-cart-intent="1"].cart-sidebar-open body.home #shop-cart-sidebar {
				visibility: visible !important;
				opacity: 1 !important;
				pointer-events: auto !important;
				transform: translate3d(0, 0, 0) !important;
			}

			html[data-emo-cart-intent="1"].cart-sidebar-open body.home #woostify-overlay {
				visibility: visible !important;
				opacity: 1 !important;
				pointer-events: auto !important;
			}

			/*
			 * Desktop never uses Woostify's off-canvas mobile navigation. Its markup
			 * still exists in the document and, while the parent CSS is deferred, can
			 * briefly render in normal flow. That transient .site-navigation is the
			 * secondary layout-shift element reported by Lighthouse and can also look
			 * like a panel/curtain during a hard refresh.
			 */
			@media (min-width: 992px) {
				body.home #mobile-navigation.sidebar-menu,
				body.home .sidebar-menu {
					display: none !important;
					position: fixed !important;
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
					transform: translate3d(-105%, 0, 0) !important;
					transition: none !important;
				}
			}

			@media (max-width: 991px) {
				body.home #mobile-navigation.sidebar-menu {
					position: fixed !important;
					top: 0 !important;
					bottom: 0 !important;
					left: 0 !important;
					width: min(88vw, 360px) !important;
					height: 100vh !important;
					height: 100dvh !important;
					max-height: 100vh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
					overflow-y: auto !important;
					z-index: 99999 !important;
					contain: layout paint;
				}

				body.home #mobile-navigation.sidebar-menu[aria-hidden="true"],
				html:not([data-emo-menu-intent="1"]) body.home #mobile-navigation.sidebar-menu {
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
					transform: translate3d(-105%, 0, 0) !important;
					transition: none !important;
				}
			}
		</style>
		<script id="elmercado-home-drawer-intent-010269">
		(() => {
			'use strict';
			const root = document.documentElement;

			/* A fresh navigation must always begin with both drawers closed. */
			root.classList.remove('sidebar-menu-open', 'cart-sidebar-open');
			root.removeAttribute('data-emo-menu-intent');
			root.removeAttribute('data-emo-cart-intent');

			document.addEventListener('click', (event) => {
				if (!(event.target instanceof Element)) return;

				if (event.target.closest('.toggle-sidebar-menu-btn')) {
					root.setAttribute('data-emo-menu-intent', '1');
				}

				if (event.target.closest('.shopping-cart, .shopping-bag-button, a.shopping-cart, a.shopping-bag-button, .add_to_cart_button, .single_add_to_cart_button')) {
					root.setAttribute('data-emo-cart-intent', '1');
				}
			}, true);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
