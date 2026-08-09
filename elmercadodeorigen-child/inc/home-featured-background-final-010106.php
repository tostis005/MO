<?php
/**
 * Fondo continuo para la selección de productos de la portada 0.10.106.
 *
 * La zona de productos debe integrarse con el fondo blanco de la portada y no
 * mostrar una banda verde/salvia distinta bajo las fichas.
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
		<style id="elmercado-home-featured-background-final-010106">
			body.home.elmercado-child-theme .emo-featured-products {
				background: #ffffff !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
