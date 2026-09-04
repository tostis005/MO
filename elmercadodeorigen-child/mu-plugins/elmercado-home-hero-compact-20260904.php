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
			@media (min-width: 992px) {
				body.home.elmercado-child-theme .emo-home > .emo-hero {
					min-height: min(640px, calc(100svh - 108px)) !important;
					padding-bottom: clamp(2.75rem, 3.5vw, 4rem) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
