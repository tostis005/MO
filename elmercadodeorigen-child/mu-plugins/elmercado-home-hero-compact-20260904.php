<?php
/**
 * Desktop-only refinements for the Home hero and featured Special.
 *
 * Keeps tablet/mobile untouched, reduces the excess vertical space below the
 * hero and makes the featured Special media fill its card without leaving an
 * empty strip below square product imagery.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-hero-compact-20260904">
			@media (min-width: 1101px) {
				body.home .emo-home > .emo-hero {
					padding-bottom: clamp(3.25rem, 4.4vw, 4.25rem) !important;
				}

				body.home .mdo-home-featured-special__card.has-media {
					align-items: stretch;
				}

				body.home .mdo-home-featured-special__media {
					display: flex;
					height: 100%;
					min-height: 100%;
					overflow: hidden;
				}

				body.home .mdo-home-featured-special__media img {
					display: block;
					width: 100% !important;
					height: 100% !important;
					min-height: 100% !important;
					max-height: none !important;
					object-fit: cover;
					object-position: center center;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
