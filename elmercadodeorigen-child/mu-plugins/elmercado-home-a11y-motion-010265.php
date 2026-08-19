<?php
/**
 * Home-only accessibility, motion and initial mobile drawer stability 0.10.266.
 *
 * Keeps the review proof legible, limits custom Home card transitions to
 * compositor-friendly properties and gives Woostify's mobile drawer a stable
 * off-canvas state before the deferred theme stylesheet arrives.
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
		<style id="elmercado-home-a11y-motion-010266">
			/* Lighthouse: review-count copy must pass normal-text contrast. */
			body.home .emo-home .mdo-review-proof small {
				color: #666 !important;
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
			 * Critical mobile drawer geometry.
			 * The full Woostify stylesheet is intentionally deferred on Home. Without
			 * these initial rules #mobile-navigation briefly participates in normal
			 * document layout on slow connections, producing a CLS close to 1.0.
			 */
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

				body.home #mobile-navigation.sidebar-menu[aria-hidden="true"] {
					visibility: hidden !important;
					opacity: 0 !important;
					pointer-events: none !important;
					transform: translate3d(-105%, 0, 0) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
