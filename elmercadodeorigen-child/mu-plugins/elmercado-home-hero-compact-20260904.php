<?php
/**
 * Slightly compacts the Home hero on desktop only.
 *
 * Keeps tablet/mobile untouched and only reduces the excess vertical space
 * between the hero content/producer collage and the following white section.
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
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
