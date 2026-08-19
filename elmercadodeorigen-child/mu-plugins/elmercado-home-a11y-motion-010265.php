<?php
/**
 * Home-only accessibility and motion refinements 0.10.265.
 *
 * Keeps the review proof legible and limits custom Home card transitions to
 * compositor-friendly properties. Deliberately leaves Woostify search runtime
 * untouched because changing its loading contract can break the first search
 * interaction.
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
		<style id="elmercado-home-a11y-motion-010265">
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
		</style>
		<?php
	},
	PHP_INT_MAX
);
