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

/**
 * Starts an outer output buffer early so the native-star override is injected
 * into the final Home HTML after other cache/style optimizers have finished.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				if ( '' === $html || false === stripos( $html, '</head>' ) ) {
					return $html;
				}

				$style = '<style id="elmercado-home-native-stars-010264">'
					. 'body.home .star-rating,body.home .star-rating::before,body.home .star-rating span,body.home .star-rating span::before{font-family:Arial,Helvetica,sans-serif!important;letter-spacing:.04em!important}'
					. 'body.home .star-rating::before{content:"★★★★★"!important;color:#d8d7d0!important}'
					. 'body.home .star-rating span::before{content:"★★★★★"!important;color:#d7a84f!important}'
					. '</style>';

				if ( false !== strpos( $html, 'id="elmercado-home-native-stars-010264"' ) ) {
					return $html;
				}

				return preg_replace( '~</head>~i', $style . '</head>', $html, 1 ) ?? $html;
			}
		);
	},
	PHP_INT_MIN
);
