<?php
/**
 * Home-only native product stars.
 *
 * Avoids downloading WooCommerce star.woff on the front page while preserving
 * the visual rating and the width clipping WooCommerce uses for partial stars.
 * No scripts, search, cart, tracking or other assets are changed here.
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
		<style id="elmercado-home-native-stars-010264">
		body.home .star-rating,
		body.home .star-rating::before,
		body.home .star-rating span,
		body.home .star-rating span::before {
			font-family: Arial, Helvetica, sans-serif !important;
			letter-spacing: .04em !important;
		}
		body.home .star-rating::before {
			content: "★★★★★" !important;
			color: #d8d7d0 !important;
		}
		body.home .star-rating span::before {
			content: "★★★★★" !important;
			color: #d7a84f !important;
		}
		</style>
		<?php
	},
	PHP_INT_MIN
);
