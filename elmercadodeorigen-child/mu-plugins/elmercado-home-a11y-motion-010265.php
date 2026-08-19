<?php
/**
 * Home-only accessibility, motion and initial mobile drawer stability 0.10.267.
 *
 * Keeps the review proof legible, limits custom Home card transitions to
 * compositor-friendly properties and guarantees that the mobile navigation
 * drawer/backdrop can only become visible after a real menu interaction.
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
		<style id="elmercado-home-a11y-motion-010267">
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

			/*
			 * Avoid broad transition:all behaviour on the custom Home cards.
			 * Hover movement/fades remain smooth without animating layout/paint props.
			 */
			body.home .emo-home .emo-hero-card,
			body.home .emo-home .emo-category-card,
			body.home .emo-home .products .product,
			body.home .emo-home .emo-story__values article {
				transition-property: transform, opacity !important;
			}

			/*
			 * The editorial mobile backdrop is created by theme.js. refinement.css
			 * intentionally reveals it whenever html.sidebar-menu-open is present.
			 * On a cold load a legacy/native menu class can exist for a brief frame,
			 * which produces the dark curtain reported on hard refresh and delays the
			 * apparent hero paint. Until a user actually activates the menu, neither
			 * the backdrop nor the drawer may be visible.
			 */
			body.home .emo-mobile-menu-overlay[aria-hidden="true"],
			html:not([data-emo-menu-intent="1"]) body.home .emo-mobile-menu-overlay {
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
				transition: none !important;
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
		<script id="elmercado-home-menu-intent-010267">
		(() => {
			'use strict';
			const root = document.documentElement;

			/* A fresh navigation must always begin with the drawer closed. */
			root.classList.remove('sidebar-menu-open');
			root.removeAttribute('data-emo-menu-intent');

			/*
			 * Capture runs before the existing Home click handler toggles the class,
			 * so the normal menu continues to open on the very first interaction.
			 */
			document.addEventListener('click', (event) => {
				const target = event.target instanceof Element
					? event.target.closest('.toggle-sidebar-menu-btn')
					: null;
				if (target) root.setAttribute('data-emo-menu-intent', '1');
			}, true);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
